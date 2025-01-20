<?php

namespace Sprint\Migration\Tables;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Result;
use Bitrix\Main\DB\SqlQueryException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;

abstract class AbstractTable
{
    private string $tableName;
    private string $tableUid;
    private string $dbName;
    protected int  $tableVersion = 1;
    protected      $connection;

    abstract protected function createTable();

    abstract protected function dropTable();

    public function __construct($tableName)
    {
        $this->connection = Application::getConnection();

        $this->tableName = $tableName;
        $this->dbName = $this->connection->getDatabase();

        $this->tableUid = strtolower('table_' . $this->tableName);

        $version = (int)Module::getDbOption($this->tableUid);
        if ($version !== $this->tableVersion) {
            $this->createTable();
            Module::setDbOption($this->tableUid, $this->tableVersion);
        }
    }

    public function deleteTable()
    {
        $this->dropTable();

        Module::removeDbOption($this->tableUid);
    }

    /**
     * @param string $query
     * @param string ...$vars
     *
     * @throws MigrationException
     * @return Result
     */
    protected function query(string $query, ...$vars): Result
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $query = call_user_func_array('sprintf', $params);
        }

        $search = [
            '#TABLE1#' => $this->tableName,
            '#DBNAME#' => $this->dbName,
        ];

        if (Locale::isWin1251()) {
            $search['#CHARSET#'] = 'cp1251';
            $search['#COLLATE#'] = 'cp1251_general_ci';
        } else {
            $search['#CHARSET#'] = 'utf8';
            $search['#COLLATE#'] = 'utf8_general_ci';
        }

        $querySearch = array_keys($search);
        $queryReplace = array_values($search);

        $query = str_replace($querySearch, $queryReplace, $query);
        try {
            return $this->connection->query($query);
        } catch (SqlQueryException $e) {
            throw new MigrationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function forSql($query): string
    {
        return $this->connection->getSqlHelper()->forSql($query);
    }
}



