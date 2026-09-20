<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class OrmTableBuilder extends VersionBuilder
{
    protected function isBuilderEnabled(): bool
    {
        return $this->getHelperManager()->Sql()->isEnabled();
    }

    protected function initialize(): void
    {
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Orm'));

        $this->setTitle(implode(' ', [
            Locale::getMessage('BUILDER_OrmTable'),
            Locale::getMessage('DEVELOPER_LABEL'),
        ]));

        $this->setDescription(implode(PHP_EOL, [
            Locale::getMessage('DEVELOPER_NAME', ['#VALUE#' => '@temi4']),
            Locale::getMessage('BUILDER_OrmTable_Info'),
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
        $mode = $this->addFieldAndReturn('table_mode', [
            'title'  => Locale::getMessage('BUILDER_OrmTable_Mode'),
            'width'  => 250,
            'rebuild_on_change' => 1,
            'select' => [
                [
                    'title' => Locale::getMessage('BUILDER_OrmTable_ModeExists'),
                    'value' => 'exists',
                ],
                [
                    'title' => Locale::getMessage('BUILDER_OrmTable_ModeNew'),
                    'value' => 'new',
                ],
            ],
        ]);

        if ($mode === 'new') {
            $tableName = $this->addFieldAndReturn('new_table_name', [
                'title'       => Locale::getMessage('BUILDER_OrmTable_NewTableName'),
                'placeholder' => 'b_custom_table',
                'width'       => 250,
            ]);
            $createTable = true;
        } else {
            $tableName = $this->addFieldAndReturn('table_name', [
                'type'        => 'autocomplete',
                'title'       => Locale::getMessage('BUILDER_OrmTable_TableName'),
                'placeholder' => Locale::getMessage('BUILDER_OrmTable_TableSearch'),
                'note'        => Locale::getMessage('BUILDER_OrmTable_TableSelectRequired'),
                'width'       => 250,
                'source'      => 'sql_tables',
            ]);
            $createTable = false;

            if (!$this->getHelperManager()->Sql()->hasTable($tableName)) {
                $this->rebuildField('table_name');
            }
        }

        $fieldsJson = $this->addFieldAndReturn('fields_json', [
            'type'                   => 'orm_fields',
            'title'                  => Locale::getMessage('BUILDER_OrmTable_Fields'),
            'value'                  => [],
            'primary_enabled'        => $createTable,
            'autoincrement_enabled'  => $createTable,
        ]);

        $fields = $this->prepareFields($fieldsJson, $createTable);
        if (empty($fields)) {
            $this->rebuildField('fields_json');
        }

        $this->createVersionFile(
            Module::getModuleTemplateFile('OrmTable'),
            [
                'tableName'   => $tableName,
                'createTable' => $createTable,
                'fields'      => $fields,
            ],
            false
        );
    }

    /**
     * @throws HelperException
     */
    private function prepareFields(string|array $fieldsJson, bool $createTable): array
    {
        $fields = is_array($fieldsJson) ? $fieldsJson : json_decode($fieldsJson, true);
        $fields = is_array($fields) ? $fields : [];
        $result = [];
        $fieldNames = [];
        $autoincrementFields = [];

        foreach ($fields as $field) {
            $field = [
                'name'          => trim((string)($field['name'] ?? '')),
                'type'          => trim((string)($field['type'] ?? '')),
                'length'        => (int)($field['length'] ?? 0),
                'nullable'      => !empty($field['nullable']),
                'default'       => (string)($field['default'] ?? ''),
                'primary'       => $createTable && !empty($field['primary']),
                'autoincrement' => $createTable && !empty($field['autoincrement']),
            ];

            if ($field['name'] === '') {
                continue;
            }

            if (isset($fieldNames[$field['name']])) {
                throw new HelperException("Duplicate field \"{$field['name']}\"");
            }
            $fieldNames[$field['name']] = true;

            if ($field['autoincrement']) {
                $autoincrementFields[] = $field['name'];
                if (!$field['primary']) {
                    throw new HelperException('Autoincrement field must be included in primary key');
                }
            }

            $this->getHelperManager()->Sql()->makeScalarField($field);
            $result[] = $field;
        }

        if (count($autoincrementFields) > 1) {
            throw new HelperException('Only one autoincrement field is allowed');
        }

        return $result;
    }
}
