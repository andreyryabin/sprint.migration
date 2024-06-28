<?php

namespace Sprint\Migration\Helpers;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Result;
use Bitrix\Main\Db\SqlQueryException;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Throwable;

/**
 * Class SqlHelper
 *
 * @package Sprint\Migration\Helpers
 */
class SqlHelper extends Helper
{
    /**
     * @param callable $func
     *
     * @throws HelperException
     * @throws SqlQueryException
     */
    public function transaction(callable $func)
    {
        $connection = Application::getConnection();
        $connection->startTransaction();
        try {
            $ok = call_user_func($func);

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

    /**
     * @param $query
     *
     * @throws SqlQueryException
     * @return Result
     */
    public function query($query): Result
    {
        return Application::getConnection()->query($query);
    }

    public function forSql($value, $maxLength = 0): string
    {
        return Application::getConnection()->getSqlHelper()->forSql($value, $maxLength);
    }

    /**
     * @throws SqlQueryException
     * @return array|false
     */
    public function getColumn(string $table, string $name)
    {
        return $this->query("SHOW COLUMNS FROM `$table` WHERE Field=\"$name\"")->Fetch();
    }

    /**
     * @throws SqlQueryException
     */
    public function addColumn(string $table, string $name, string $attributes = '')
    {
        //$attributes = 'int(11) unsigned DEFAULT NULL AFTER `ID`';

        $this->query("ALTER TABLE `$table` ADD COLUMN $name $attributes");
    }

    /**
     * @throws SqlQueryException
     */
    public function addColumnIfNotExists(string $table, string $name, string $attributes = '')
    {
        $column = $this->getColumn($table, $name);

        if (empty($column)) {
            $this->addColumn($table, $name, $attributes);
        }
    }

    /**
     * @throws SqlQueryException
     * @return array|false
     */
    public function getIndex(string $table, string $name)
    {
        return $this->query("SHOW INDEX FROM `$table` WHERE Key_name=\"$name\"")->Fetch();
    }

    /**
     * @param string|array $columns
     *
     * @throws SqlQueryException
     */
    public function addIndex(string $table, string $name, $columns)
    {
        $columns = $this->prepareColumnsForIndex($columns);

        $this->query("ALTER TABLE `$table` ADD INDEX `$name` ($columns)");
    }

    /**
     * @param string|array $columns
     *
     * @throws SqlQueryException
     */
    public function addIndexIfNotExists(string $table, string $name, $columns)
    {
        $index = $this->getIndex($table, $name);

        if (empty($index)) {
            $this->addIndex($table, $name, $columns);
        }
    }

    /**
     * @param string|array $columns
     */
    private function prepareColumnsForIndex($columns): string
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $columns = array_map(
            function ($name) {
                return "`$name`";
            },
            $columns
        );

        return implode(',', $columns);
    }

    /**
     * @throws SqlQueryException
     */
    public function dropTable(string $table): Result
    {
        return $this->query("DROP TABLE `$table`");
    }
}
