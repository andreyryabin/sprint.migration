<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class OrmTableDeleteBuilder extends VersionBuilder
{
    protected function isBuilderEnabled(): bool
    {
        return $this->getHelperManager()->Sql()->isEnabled();
    }

    protected function initialize(): void
    {
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Orm'));

        $this->setTitle(implode(' ', [
            Locale::getMessage('BUILDER_OrmTableDelete'),
            Locale::getMessage('DEVELOPER_LABEL'),
        ]));

        $this->setDescription(implode(PHP_EOL, [
            Locale::getMessage('DEVELOPER_NAME', ['#VALUE#' => '@temi4']),
            Locale::getMessage('BUILDER_OrmTableDelete_Info'),
        ]));

        $this->addVersionFields();
    }

    /**
     * @throws HelperException
     * @throws MigrationException
     * @throws RebuildException
     */
    protected function execute(): void
    {
        $tableName = $this->addFieldAndReturn('table_name', [
            'type'        => 'autocomplete',
            'title'       => Locale::getMessage('BUILDER_OrmTable_TableName'),
            'placeholder' => Locale::getMessage('BUILDER_OrmTable_TableSearch'),
            'note'        => Locale::getMessage('BUILDER_OrmTable_TableSelectRequired'),
            'width'       => 250,
            'source'      => 'sql_tables',
        ]);

        if (!$this->getHelperManager()->Sql()->hasTable($tableName)) {
            $this->rebuildField('table_name');
        }

        $this->createVersionFile(
            Module::getModuleTemplateFile('OrmTableDelete'),
            [
                'tableName' => $tableName,
            ],
            false
        );
    }
}
