<?php

namespace Sprint\Migration\Tables;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Result;
use Bitrix\Main\DB\SqlQueryException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;

abstract class AbstractTable
{
    private $tableName = '';
    private $dbName = '';
    protected $tableVersion = 1;
    protected $connection;

    abstract protected function createTable();

    abstract protected function dropTable();

    public function __construct($tableName)
    {
        $this->connection = Application::getConnection();

        $this->tableName = $tableName;
        $this->dbName = $this->connection->getDatabase();

        $uid = $this->getUid();
        if (!Module::getDbOption($uid)) {
            $this->createTable();
            Module::setDbOption($uid, 1);
        }
    }

    public function deleteTable()
    {
        $this->dropTable();

        Module::removeDbOption($this->getUid());
    }

    private function getUid()
    {
        return 'upgrade' . $this->tableVersion . '_' . md5($this->tableName);
    }

    /**
     * @param        $query
     * @param string ...$vars
     *
     * @throws SqlQueryException
     * @return Result
     */
    protected function query($query, ...$vars)
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
        return $this->connection->query($query);
    }

    protected function forSql($query)
    {
        return $this->connection->getSqlHelper()->forSql($query);
    }
}



