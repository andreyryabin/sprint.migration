<?php

namespace Sprint\Migration;

use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Fields\RadioListField;
use Sprint\Migration\Fields\StringField;
use Sprint\Migration\Fields\TextareaField;
use Sprint\Migration\Traits\CurrentUserTrait;

abstract class VersionBuilder extends Builder
{
    use CurrentUserTrait;

    protected function addVersionFields(): void
    {
        $this->showField('prefix',
            (new StringField())
                ->setTitle(Locale::getMessage('FORM_PREFIX'))
                ->setDefaultValue($this->getVersionConfig()->getVal('version_prefix'))
                ->setSize(35)
                ->setInPlan(false),
        );

        $this->showField('description',
            (new TextareaField())
                ->setTitle(Locale::getMessage('FORM_DESCR'))
                ->setCols(35)
                ->setRows(4)
                ->setInPlan(false),
        );
    }

    /**
     * @throws RebuildException
     */
    protected function confirmVersionPlan(): void
    {
        $confirm = $this->askField('confirm',
            (new RadioListField())
                ->setTitle(Locale::getMessage('CONFIRM_PLAN'))
                ->addOption('y', Locale::getMessage('CONFIRM_PLAN_YES'))
                ->addOption('n', Locale::getMessage('CONFIRM_PLAN_NO'))
                ->setInPlan(false),
        );

        if (strtolower($confirm) !== 'y') {
            $this->resetField('confirm');
        }
    }

    protected function getVersionPlan(): string
    {
        $lines = [$this->getTitle()];
        foreach ($this->getFields() as $code => $field) {
            if ($field->isInPlan()) {
                $lines[] = $field->makePlanText(
                    $this->getFieldValue($code),
                );
            }
        }
        return implode(PHP_EOL, array_filter($lines));
    }

    /**
     * @throws MigrationException
     */
    protected function createVersionFile(
        string $templateFile,
        array $templateVars = [],
        bool $markAsInstalled = true,
    ): string {
        $desc = array_filter([
            $this->getFieldValue('description'),
            $this->getVersionPlan(),
        ]);

        $templateVars['description'] = $this->addslashes(implode(PHP_EOL, $desc));

        $templateVars['author'] = $this->addslashes(
            $this->getCurrentUserLogin(),
        );

        $templateVars['version'] ??= $this->getVersionName();

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
            throw new MigrationException(
                Locale::getMessage(
                    'ERR_TEMPLATE_NOT_FOUND',
                    [
                        '#NAME#' => $templateFile,
                    ],
                ),
            );
        }

        $fileName = $this->getVersionConfig()->getVersionFile($templateVars['version']);
        $fileContent = Module::templater($templateFile, $templateVars);

        file_put_contents($fileName, $fileContent);

        if (!is_file($fileName)) {
            throw new MigrationException(
                Locale::getMessage('ERR_CANT_CREATE_FILE', ['#NAME#' => $fileName,]),
            );
        }

        $this->outSuccess(
            Locale::getMessage('CREATED_SUCCESS', ['#VERSION#' => $templateVars['version'],]),
        );

        if ($markAsInstalled) {
            $entity = $this->getVersionManager()->getVersionByName($templateVars['version']);
            $this->getVersionManager()->markMigration($entity, VersionEnum::STATUS_INSTALLED);
        }

        return $templateVars['version'];
    }

    protected function addslashes(string $descr = ''): string
    {
        return addslashes(strip_tags(trim($descr)));
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
                '#NAME#'      => $this->purifyPrefix($this->getFieldValue('prefix')),
                '#TIMESTAMP#' => $this->getTimestamp(
                    $this->getVersionConfig()->getVal('version_timestamp_format'),
                ),
            ],
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

    protected function getTimestamp($versionTimestampFormat): string
    {
        $originTz = date_default_timezone_get();
        date_default_timezone_set('Europe/Moscow');
        $ts = date($versionTimestampFormat);
        date_default_timezone_set($originTz);
        return $ts;
    }

    /**
     * @throws MigrationException
     */
    protected function getVersionExchangeDir(): string
    {
        return $this->getVersionConfig()->getVersionExchangeDir(
            $this->getVersionName(),
        );
    }
}
