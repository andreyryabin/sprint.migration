<?php

namespace Sprint\Migration;

use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Traits\CurrentUserTrait;

abstract class VersionBuilder extends Builder
{
    use CurrentUserTrait;

    protected function addVersionFields(): void
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
                'title' => Locale::getMessage('FORM_DESCR'),
                'width' => 350,
                'height' => 40,
            ]
        );
    }

    protected function purifyPrefix(string $prefix = ''): string
    {
        $prefix = trim($prefix);
        if (empty($prefix)) {
            $prefix = $this->getVersionConfig()->getVal('version_prefix');
            $prefix = trim($prefix);
        }

        $prefix = preg_replace("/[^a-z0-9_]/i", '', $prefix);
        if (empty($prefix) || preg_match('/^\d/', $prefix)) {
            return 'Version';
        }

        return $prefix;
    }

    protected function addslashes(string $descr = ''): string
    {
        return addslashes(strip_tags(trim($descr)));
    }

    protected function getVersionFile(string $versionName): string
    {
        $dir = $this->getVersionConfig()->getVal('migration_dir');
        return $dir . '/' . $versionName . '.php';
    }

    protected function getVersionName(): string
    {
        if (!isset($this->params['~version_name'])) {
            $this->params['~version_name'] = $this->createVersionName();
        }
        return $this->params['~version_name'];
    }

    protected function createVersionName(): string
    {
        return strtr(
            $this->getVersionConfig()->getVal('version_name_template'),
            [
                '#NAME#' => $this->purifyPrefix($this->getFieldValue('prefix')),
                '#TIMESTAMP#' => $this->getTimestamp(
                    $this->getVersionConfig()->getVal('version_timestamp_format')
                ),
            ]
        );
    }

    /**
     * @throws MigrationException
     */
    protected function createVersionFile(
        string $templateFile = '',
        array  $templateVars = [],
        bool   $markAsInstalled = true
    ): string
    {
        $templateVars['description'] = $this->addslashes(
            $this->getFieldValue('description')
        );
        $templateVars['author'] = $this->addslashes(
            $this->getCurrentUserLogin()
        );
        if (empty($templateVars['version'])) {
            $templateVars['version'] = $this->getVersionName();
        }

        [$extendUse, $extendClass] = explode(' as ', $this->getVersionConfig()->getVal('migration_extend_class'));
        $extendUse = trim($extendUse);
        $extendClass = trim($extendClass);

        if (!empty($extendClass)) {
            $extendUse = 'use ' . $extendUse . ' as ' . $extendClass . ';' . PHP_EOL;
        } else {
            $extendClass = $extendUse;
            $extendUse = '';
        }

        $templateVars['extendUse'] = $extendUse;
        $templateVars['extendClass'] = $extendClass;
        $templateVars['moduleVersion'] = Module::getVersion();

        if (!is_file($templateFile)) {
            $templateFile = Module::getModuleTemplateFile('version');
        }

        $fileName = $this->getVersionFile($templateVars['version']);
        $fileContent = $this->renderFile($templateFile, $templateVars);

        file_put_contents($fileName, $fileContent);

        if (!is_file($fileName)) {
            throw new MigrationException(
                Locale::getMessage(
                    'ERR_CANT_CREATE_FILE', [
                        '#NAME#' => $fileName,
                    ]
                )
            );
        }

        $this->outSuccess(
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

    protected function getTimestamp($versionTimestampFormat): string
    {
        $originTz = date_default_timezone_get();
        date_default_timezone_set('Europe/Moscow');
        $ts = date($versionTimestampFormat);
        date_default_timezone_set($originTz);
        return $ts;
    }

    protected function getVersionExchangeDir(): string
    {
        return $this->getVersionConfig()->getVersionExchangeDir(
            $this->getVersionName()
        );
    }
}
