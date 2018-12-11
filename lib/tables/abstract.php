<?php

namespace Sprint\Migration\Tables;

use Sprint\Migration\Module;
use Sprint\Migration\Locale;

abstract class AbstractTable
{

    private $dbName = false;

    /** @var \CDatabase */
    private $bitrixDb = null;

    private $tableName = '';

    abstract protected function createTable();
    abstract protected function dropTable();


    public function __construct($tableName) {
        $this->tableName = $tableName;

        $this->bitrixDb = $GLOBALS['DB'];
        $this->dbName = $GLOBALS['DBName'];

        $opt = 'upgrade2_' . md5($this->tableName);
        if (!Module::getDbOption($opt)) {
            $this->createTable();
            Module::setDbOption($opt, 1);
        }

    }

    public function deleteTable(){
        $this->dropTable();

        $opt = 'upgrade2_' . md5($this->tableName);
        Module::removeDbOption($opt);
    }


    /**
     * @param $query
     * @param null $var1
     * @param null $var2
     * @return bool|\CDBResult
     */
    protected function query($query, $var1 = null, $var2 = null) {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $query = call_user_func_array('sprintf', $params);
        }

        $search = array(
            '#TABLE1#' => $this->tableName,
            '#DBNAME#' => $this->dbName,
        );

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

        return $this->bitrixDb->Query($query);
    }

    protected function forSql($query) {
        return $this->bitrixDb->ForSql($query);
    }
}



