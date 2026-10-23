<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class OrmFieldDeleteBuilder extends VersionBuilder
{
    protected function isBuilderEnabled(): bool
    {
        return $this->getHelperManager()->Sql()->isEnabled();
    }

    protected function initialize(): void
    {
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Orm'));

        $this->setTitle(implode(' ', [
            Locale::getMessage('BUILDER_OrmFieldDelete'),
            Locale::getMessage('DEVELOPER_LABEL'),
        ]));

        $this->setDescription(implode(PHP_EOL, [
            Locale::getMessage('DEVELOPER_NAME', ['#VALUE#' => '@temi4']),
            Locale::getMessage('BUILDER_OrmFieldDelete_Info'),
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
        $helper = $this->getHelperManager()->Sql();

        $tableName = $this->addFieldAndReturn('table_name', [
            'type'        => 'autocomplete',
            'title'       => Locale::getMessage('BUILDER_OrmTable_TableName'),
            'placeholder' => Locale::getMessage('BUILDER_OrmTable_TableSearch'),
            'note'        => Locale::getMessage('BUILDER_OrmTable_TableSelectRequired'),
            'width'       => 250,
            'source'      => 'sql_tables',
        ]);

        if (!$helper->hasTable($tableName)) {
            $this->rebuildField('table_name');
        }

        $columns = $this->prepareColumns($helper->getTableColumns($tableName));
        $columnNames = $this->addFieldAndReturn('column_names', [
            'title'    => Locale::getMessage('BUILDER_OrmFieldDelete_Fields'),
            'multiple' => 1,
            'value'    => [],
            'select'   => $this->createSelect($columns, 'NAME', 'TITLE'),
        ]);

        $columnNames = array_values(array_filter(array_map('strval', $columnNames)));
        if (empty($columnNames)) {
            $this->rebuildField('column_names');
        }

        $this->createVersionFile(
            Module::getModuleTemplateFile('OrmFieldDelete'),
            [
                'tableName'   => $tableName,
                'columnNames' => $columnNames,
            ],
            false
        );
    }

    private function prepareColumns(array $columns): array
    {
        return array_map(
            fn($column) => [
                'NAME'  => $column->getName(),
                'TITLE' => $column->getName(),
            ],
            $columns
        );
    }
}
