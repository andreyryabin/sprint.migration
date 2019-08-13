<?php

namespace Sprint\Migration;

class VersionBuilder extends AbstractBuilder
{

    protected function addVersionFields()
    {
        $this->addField('prefix', [
            'title' => GetMessage('SPRINT_MIGRATION_FORM_PREFIX'),
            'value' => $this->getVersionConfig()->getVal('version_prefix'),
            'width' => 250,
        ]);

        $this->addField('description', [
            'title' => GetMessage('SPRINT_MIGRATION_FORM_DESCR'),
            'width' => 350,
            'height' => 40,
        ]);
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

    protected function getVersionResources($versionName)
    {
        return $this->getVersionConfig()->getVal('migration_dir') . '/' . $versionName . '_files';
    }

    protected function getVersionName()
    {
        $prefix = $this->purifyPrefix(
            $this->getFieldValue('prefix')
        );

        return $prefix . $this->getTimestamp();
    }

    protected function createVersionFile($templateFile = '', $templateVars = [])
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

        $tplVars = array_merge([
            'extendUse' => $extendUse,
            'extendClass' => $extendClass,
        ], $templateVars);

        if (!is_file($templateFile)) {
            $templateFile = Module::getModuleDir() . '/templates/version.php';
        }

        $fileName = $this->getVersionFile($templateVars['version']);
        $fileContent = $this->renderFile($templateFile, $tplVars);

        file_put_contents($fileName, $fileContent);

        if (!is_file($fileName)) {
            Out::outError('%s, error: can\'t create a file "%s"', $templateVars['version'], $fileName);
            return false;
        }

        Out::outSuccess(GetMessage('SPRINT_MIGRATION_CREATED_SUCCESS', [
            '#VERSION#' => $templateVars['version'],
        ]));

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
