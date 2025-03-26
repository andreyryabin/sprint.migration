<?php

namespace Sprint\Migration\Helpers;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Result;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Fields\ScalarField;
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
        $connection = Application::getConnection();

        return $connection->isTableExists($tableName);
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
        $connection = Application::getConnection();
        $sqlHelper = $connection->getSqlHelper();

        $columnName = $scalarField->getName();
        $columnType = $sqlHelper->getColumnTypeByField($scalarField);

        $this->query("ALTER TABLE $tableName ADD COLUMN $columnName $columnType $attributes");
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
}
