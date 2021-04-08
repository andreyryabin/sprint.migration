<?php

namespace Sprint\Migration;

use Sprint\Migration\Enum\VersionEnum;

abstract class VersionBuilder extends AbstractBuilder
{
    protected function addVersionFields()
    {
        $this->addField(
            'prefix', [
                'title' => Locale::getMessage('FORM_PREFIX'),
                'value' => $this->getVersionConfig()->getVal('version_prefix'),
                'width' => 250,
            ]
        );

        $this->addField(
            'description', [
                'title'  => Locale::getMessage('FORM_DESCR'),
                'width'  => 350,
                'height' => 40,
            ]
        );
    }

    protected function purifyPrefix($prefix = '')
    {
        $prefix = trim($prefix);
        if (empty($prefix)) {
            $prefix = $this->getVersionConfig()->getVal('version_prefix');
            $prefix = trim($prefix);
        }

        $default = 'Version';
        if (empty($prefix)) {
            return $default;
        }

        $prefix = preg_replace("/[^a-z0-9_]/i", '', $prefix);
        if (empty($prefix)) {
            return $default;
        }

        if (preg_match('/^\d/', $prefix)) {
            return $default;
        }

        return $prefix;
    }

    protected function purifyDescription($descr = '')
    {
        $descr = strval($descr);
        $descr = str_replace(["\n\r", "\r\n", "\n", "\r"], ' ', $descr);
        $descr = strip_tags($descr);
        $descr = addslashes($descr);
        return $descr;
    }

    protected function getVersionFile($versionName)
    {
        return $this->getVersionConfig()->getVal('migration_dir') . '/' . $versionName . '.php';
    }

    protected function getVersionResourceFile($versionName, $name)
    {
        return $this->getVersionConfig()->getVal('migration_dir') . '/' . $versionName . '_files/' . $name;
    }

    protected function getVersionName()
    {
        if (!isset($this->params['~version_name'])) {
            $this->params['~version_name'] = $this->createVersionName();
            $versionName = $this->params['~version_name'];
        } else {
            $versionName = $this->params['~version_name'];
        }
        return $versionName;
    }

    protected function createVersionName()
    {
        return strtr(
            $this->getVersionConfig()->getVal('version_name_template'),
            [
                '#NAME#'      => $this->purifyPrefix($this->getFieldValue('prefix')),
                '#TIMESTAMP#' => $this->getTimestamp(),
            ]
        );
    }

    /**
     * @param string $templateFile
     * @param array  $templateVars
     * @param bool   $markAsInstalled
     *
     * @throws Exceptions\MigrationException
     * @return bool|string
     */
    protected function createVersionFile($templateFile = '', $templateVars = [], $markAsInstalled = true)
    {
        $templateVars['description'] = $this->purifyDescription(
            $this->getFieldValue('description')
        );

        if (empty($templateVars['version'])) {
            $templateVars['version'] = $this->getVersionName();
        }

        list($extendUse, $extendClass) = explode(' as ', $this->getVersionConfig()->getVal('migration_extend_class'));
        $extendUse = trim($extendUse);
        $extendClass = trim($extendClass);

        if (!empty($extendClass)) {
            $extendUse = 'use ' . $extendUse . ' as ' . $extendClass . ';' . PHP_EOL;
        } else {
            $extendClass = $extendUse;
            $extendUse = '';
        }

        $tplVars = array_merge(
            [
                'extendUse'     => $extendUse,
                'extendClass'   => $extendClass,
                'moduleVersion' => Module::getVersion(),
            ], $templateVars
        );

        if (!is_file($templateFile)) {
            $templateFile = Module::getModuleDir() . '/templates/version.php';
        }

        $fileName = $this->getVersionFile($templateVars['version']);
        $fileContent = $this->renderFile($templateFile, $tplVars);

        file_put_contents($fileName, $fileContent);

        if (!is_file($fileName)) {
            Out::outError(
                Locale::getMessage(
                    'ERR_CANT_CREATE_FILE', [
                        '#NAME#' => $fileName,
                    ]
                )
            );
            return false;
        }

        Out::outSuccess(
            Locale::getMessage(
                'CREATED_SUCCESS',
                [
                    '#VERSION#' => $templateVars['version'],
                ]
            )
        );

        if ($markAsInstalled) {
            $vm = new VersionManager($this->getVersionConfig());
            $vm->markMigration($templateVars['version'], VersionEnum::STATUS_INSTALLED);
        }

        return $templateVars['version'];
    }

    protected function getTimestamp()
    {
        $originTz = date_default_timezone_get();
        date_default_timezone_set('Europe/Moscow');
        $ts = date('YmdHis');
        date_default_timezone_set($originTz);
        return $ts;
    }
}
