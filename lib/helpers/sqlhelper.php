<?php

namespace Sprint\Migration\Helpers;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Result;
use Bitrix\Main\Db\SqlQueryException;
use Exception;
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

            $this->throwApplicationExceptionIfExists(__METHOD__);

            if ($ok === false) {
                $this->throwException(__METHOD__, 'transaction return false');
            }

            $connection->commitTransaction();
        } catch (Exception $ex) {
            $connection->rollbackTransaction();
            $this->throwException(__METHOD__, $ex->getMessage());
        } catch (Throwable $ex) {
            $connection->rollbackTransaction();
            $this->throwException(__METHOD__, $ex->getMessage());
        }
    }

    /**
     * @param $query
     *
     * @throws SqlQueryException
     * @return Result
     */
    public function query($query)
    {
        return Application::getConnection()->query($query);
    }

    /**
     * @param     $value
     * @param int $maxLength
     *
     * @return string
     */
    public function forSql($value, $maxLength = 0)
    {
        return Application::getConnection()->getSqlHelper()->forSql($value, $maxLength);
    }

    /**
     * @param $table
     * @param $name
     *
     * @throws SqlQueryException
     * @return array|false
     */
    public function getColumn($table, $name)
    {
        return $this->query(sprintf('SHOW COLUMNS FROM `%s` WHERE Field="%s"', $table, $name))->Fetch();
    }

    /**
     * @param string $table
     * @param string $name
     * @param string $attributes
     *
     * @throws SqlQueryException
     */
    public function addColumn($table, $name, $attributes)
    {
        //$attributes = 'int(11) unsigned DEFAULT NULL AFTER `ID`';

        $this->query(sprintf('ALTER TABLE `%s` ADD COLUMN %s %s', $table, $name, $attributes));
    }

    /**
     * @param string $table
     * @param string $name
     * @param        $attributes
     *
     * @throws SqlQueryException
     */
    public function addColumnIfNotExists($table, $name, $attributes)
    {
        $column = $this->getColumn($table, $name);

        if (empty($column)) {
            $this->addColumn($table, $name, $attributes);
        }
    }

    /**
     * @param string $table
     * @param string $name
     *
     * @throws SqlQueryException
     * @return array|false
     */
    public function getIndex($table, $name)
    {
        return $this->query(sprintf('SHOW INDEX FROM `%s` WHERE Key_name="%s"', $table, $name))->Fetch();
    }

    /**
     * @param string       $table
     * @param string       $name
     * @param string|array $columns
     *
     * @throws SqlQueryException
     */
    public function addIndex($table, $name, $columns)
    {
        $columns = $this->prepareColumnsForIndex($columns);

        $this->query(sprintf('ALTER TABLE `%s` ADD INDEX `%s` (%s)', $table, $name, $columns));
    }

    /**
     * @param string       $table
     * @param string       $name
     * @param string|array $columns
     *
     * @throws SqlQueryException
     */
    public function addIndexIfNotExists($table, $name, $columns)
    {
        $index = $this->getIndex($table, $name);

        if (empty($index)) {
            $this->addIndex($table, $name, $columns);
        }
    }

    private function prepareColumnsForIndex($columns)
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
}
