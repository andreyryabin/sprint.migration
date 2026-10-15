<?php

namespace Sprint\Migration\Helpers;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Result;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Closure;
use Exception;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Throwable;

class SqlHelper extends Helper
{
    /**
     * @throws HelperException
     * @throws SqlQueryException
     */
    public function transaction(Closure $func): void
    {
        $connection = Application::getConnection();
        $connection->startTransaction();
        try {
            $ok = $func();

            $this->throwApplicationExceptionIfExists();

            if ($ok === false) {
                throw new HelperException('transaction return false');
            }

            $connection->commitTransaction();
        } catch (Throwable $e) {
            $connection->rollbackTransaction();
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function forSql($value, $maxLength = 0): string
    {
        $connection = Application::getConnection();

        return $connection->getSqlHelper()->forSql($value, $maxLength);
    }

    /**
     * @throws HelperException
     */
    public function getColumn(string $tableName, string $columnName): ?ScalarField
    {
        $connection = Application::getConnection();

        try {
            return $connection->getTableField($tableName, $columnName);
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function addIndexIfNotExists(string $tableName, string $indexName, $columnNames)
    {
        if ($this->hasIndex($tableName, $columnNames)) {
            $this->addIndex($tableName, $indexName, $columnNames);
        }
    }

    /**
     * @throws HelperException
     */
    public function hasIndex(string $tableName, array $columnNames): bool
    {
        $connection = Application::getConnection();

        try {
            return $connection->isIndexExists($tableName, $columnNames);
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function addIndex(string $tableName, string $indexName, array $columnNames)
    {
        $connection = Application::getConnection();

        try {
            $connection->createIndex($tableName, $indexName, $columnNames);
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getIndex(string $tableName, array $columnNames): ?string
    {
        $connection = Application::getConnection();

        return $connection->getIndexName($tableName, $columnNames);
    }

    /**
     * @throws HelperException
     */
    public function dropTable(string $tableName)
    {
        $connection = Application::getConnection();

        try {
            $connection->dropTable($tableName);
            $connection->clearCaches($tableName);
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function createTable(Entity $entity)
    {
        try {
            $entity->createDbTable();
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function hasTable(string $tableName): bool
    {
        $this->checkIdentifier($tableName, 'table');

        $connection = Application::getConnection();

        return $connection->isTableExists($tableName);
    }

    /**
     * @throws HelperException
     */
    public function searchTables(string $search = '', int $limit = 20): array
    {
        $connection = Application::getConnection();
        $sqlHelper = $connection->getSqlHelper();
        $search = trim($search);
        $limit = max(1, min($limit, 100));

        try {
            $searchSql = $sqlHelper->forSql($search);
            $dbType = $connection->getType();

            if ($dbType === 'mysql') {
                $filter = ($search === '') ? '' : " AND TABLE_NAME LIKE '%" . $searchSql . "%'";
                $sql = "SELECT TABLE_NAME FROM information_schema.TABLES " .
                    "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'" .
                    $filter .
                    " ORDER BY TABLE_NAME ASC LIMIT " . $limit;
            } elseif ($dbType === 'pgsql') {
                $filter = ($search === '') ? '' : " AND table_name ILIKE '%" . $searchSql . "%'";
                $sql = "SELECT table_name AS TABLE_NAME FROM information_schema.tables " .
                    "WHERE table_schema = 'public' AND table_type = 'BASE TABLE'" .
                    $filter .
                    " ORDER BY table_name ASC LIMIT " . $limit;
            } elseif ($dbType === 'mssql') {
                $filter = ($search === '') ? '' : " AND TABLE_NAME LIKE '%" . $searchSql . "%'";
                $sql = "SELECT TOP " . $limit . " TABLE_NAME FROM INFORMATION_SCHEMA.TABLES " .
                    "WHERE TABLE_TYPE = 'BASE TABLE'" .
                    $filter .
                    " ORDER BY TABLE_NAME ASC";
            } elseif ($dbType === 'oracle') {
                $filter = ($search === '') ? '' : " WHERE TABLE_NAME LIKE '%" . strtoupper($searchSql) . "%'";
                $sql = "SELECT TABLE_NAME FROM (" .
                    "SELECT TABLE_NAME FROM USER_TABLES" .
                    $filter .
                    " ORDER BY TABLE_NAME ASC" .
                    ") WHERE ROWNUM <= " . $limit;
            } else {
                return [];
            }

            return array_map(
                fn($item) => $item['TABLE_NAME'],
                $connection->query($sql)->fetchAll()
            );
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function getTableColumns(string $tableName): array
    {
        $this->checkIdentifier($tableName, 'table');

        $connection = Application::getConnection();

        try {
            return $connection->getTableFields($tableName);
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function createTableIfNotExists(string $tableName, array $fields): void
    {
        $this->checkIdentifier($tableName, 'table');

        if ($this->hasTable($tableName)) {
            return;
        }

        $connection = Application::getConnection();
        $scalarFields = [];
        $primary = [];
        $autoincrement = [];

        foreach ($fields as $field) {
            $fieldName = (string)($field['name'] ?? '');
            $this->checkIdentifier($fieldName, 'column');

            $scalarFields[$fieldName] = $this->makeScalarField($field);
            if (!empty($field['primary'])) {
                $primary[] = $fieldName;
            }
            if (!empty($field['autoincrement'])) {
                $autoincrement[] = $fieldName;
            }
        }

        if (empty($scalarFields)) {
            throw new HelperException('Empty table fields');
        }

        try {
            $connection->createTable($tableName, $scalarFields, $primary, $autoincrement);
            $connection->clearCaches($tableName);
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function addColumnsIfNotExists(string $tableName, array $fields): void
    {
        $this->checkIdentifier($tableName, 'table');

        foreach ($fields as $field) {
            $scalarField = $this->makeScalarField($field);
            $attributes = empty($field['nullable']) ? ' NOT NULL' : '';

            $this->addColumnIfNotExists($tableName, $scalarField, $attributes);
        }
    }

    /**
     * @throws HelperException
     */
    public function dropColumnIfExists(string $tableName, string $columnName): void
    {
        $this->checkIdentifier($tableName, 'table');
        $this->checkIdentifier($columnName, 'column');

        if (!$this->hasTable($tableName) || !$this->hasColumn($tableName, $columnName)) {
            return;
        }

        $columns = $this->getTableColumns($tableName);
        if (count($columns) <= 1) {
            throw new HelperException("Column \"$columnName\" is the last column in \"$tableName\"");
        }

        $connection = Application::getConnection();

        try {
            $connection->dropColumn($tableName, $columnName);
            $connection->clearCaches($tableName);
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function dropTableIfExists(string $tableName): void
    {
        $this->checkIdentifier($tableName, 'table');

        if ($this->hasTable($tableName)) {
            $this->dropTable($tableName);
        }
    }

    /**
     * @throws HelperException
     */
    public function restoreColumns(Entity $entity)
    {
        foreach ($entity->getScalarFields() as $entityField) {
            $this->addColumnIfNotExists($entity->getDBTableName(), $entityField);
        }
    }

    /**
     * @throws HelperException
     */
    public function addColumnIfNotExists(string $tableName, ScalarField $scalarField, string $attributes = '')
    {
        if (!$this->hasColumn($tableName, $scalarField->getName())) {
            $this->addColumn($tableName, $scalarField, $attributes);
        }
    }

    /**
     * @throws HelperException
     */
    public function hasColumn(string $tableName, string $columnName): bool
    {
        $this->checkIdentifier($tableName, 'table');
        $this->checkIdentifier($columnName, 'column');

        $connection = Application::getConnection();
        try {
            $tableFields = $connection->getTableFields($tableName);
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }

        return isset($tableFields[$columnName]);
    }

    /**
     * @throws HelperException
     */
    public function addColumn(string $tableName, ScalarField $scalarField, string $attributes = '')
    {
        $this->checkIdentifier($tableName, 'table');
        $this->checkIdentifier($scalarField->getName(), 'column');

        $connection = Application::getConnection();
        $sqlHelper = $connection->getSqlHelper();

        $rawTableName = $tableName;
        $tableName = $sqlHelper->quote($tableName);
        $columnName = $sqlHelper->quote($scalarField->getColumnName());
        $columnType = $sqlHelper->getColumnTypeByField($scalarField);

        $def = $scalarField->getDefaultValue();
        if ($def !== null && $def !== '' && is_string($def)) {
            $attributes .= " DEFAULT '" . $sqlHelper->forSql($def) . "'";
        } elseif ($def !== null && $def !== '') {
            $attributes .= " DEFAULT $def";
        }

        $this->query("ALTER TABLE $tableName ADD COLUMN $columnName $columnType $attributes;");
        $connection->clearCaches($rawTableName);
    }

    /**
     * @throws HelperException
     */
    public function query($query): Result
    {
        $connection = Application::getConnection();

        try {
            return $connection->query($query);
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function makeScalarField(array $field): ScalarField
    {
        $name = (string)($field['name'] ?? '');
        $type = (string)($field['type'] ?? '');
        $length = (int)($field['length'] ?? 0);

        $this->checkIdentifier($name, 'column');

        $parameters = [
            'nullable' => !empty($field['nullable']),
        ];

        if (array_key_exists('default', $field) && $field['default'] !== '') {
            $parameters['default_value'] = $field['default'];
        }

        if (!empty($field['primary'])) {
            $parameters['primary'] = true;
        }

        return match ($type) {
            'integer' => new IntegerField($name, $parameters),
            'string' => new StringField($name, array_merge($parameters, ['size' => $length > 0 ? $length : 255])),
            'text' => new TextField($name, $parameters),
            'float' => new FloatField($name, $parameters),
            'boolean' => new BooleanField($name, array_merge($parameters, ['values' => [0, 1]])),
            'date' => new DateField($name, $parameters),
            'datetime' => new DatetimeField($name, $parameters),
            default => throw new HelperException("Unknown column type \"$type\""),
        };
    }

    /**
     * @throws HelperException
     */
    protected function checkIdentifier(string $name, string $type): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new HelperException("Invalid $type identifier \"$name\"");
        }
    }
}
