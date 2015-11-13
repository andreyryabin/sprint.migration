<?php

namespace Sprint\Migration;

class Db
{
    protected $isMssql = false;
    protected $bitrixDb = null;

    private $querySearch = array();
    private $queryReplace = array();

    public function __construct(){
        $this->isMssql = Env::isMssql();
        $this->bitrixDb = Env::getDb();

        $search = array(
            '#TABLE1#' => Env::getMigrationTable(),
            '#DBNAME#' => Env::getDbName(),
        );

        if (Env::isWin1251()){
            $search['#CHARSET#'] = 'cp1251';
            $search['#COLLATE#'] = 'cp1251_general_ci';
        } else {
            $search['#CHARSET#'] = 'utf8';
            $search['#COLLATE#'] = 'utf8_general_ci';
        }

        $this->querySearch = array_keys($search);
        $this->queryReplace = array_values($search);

    }

    public function forSql($val){
        return $this->bitrixDb->ForSql($val);
    }

    /**
     * @param $query
     * @param null $var1
     * @param null $var2
     * @return bool|\CDBResult
     */
    public function query($query, $var1 = null, $var2 = null) {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $query = call_user_func_array('sprintf', $params);
        }

        $query = str_replace($this->querySearch, $this->queryReplace, $query);

        return $this->bitrixDb->Query($query);
    }

    /**
     * @return bool|\CDBResult
     */
    public function getRecords() {
        if ($this->isMssql) {
            return $this->query('SELECT * FROM #TABLE1#');
        } else {
            return $this->query('SELECT * FROM `#TABLE1#`');
        }
    }

    /**
     * @param $versionName
     * @return bool|\CDBResult
     */
    public function getRecordByName($versionName) {
        $versionName = $this->forSql($versionName);

        if ($this->isMssql) {
            return $this->query('SELECT * FROM #TABLE1# WHERE version = \'%s\'',
                $versionName
            );

        } else {
            return $this->query('SELECT * FROM `#TABLE1#` WHERE `version` = "%s"',
                $versionName
            );
        }
    }

    /**
     * @param $versionName
     * @return bool|\CDBResult
     */
    public function addRecord($versionName) {
        $versionName = $this->forSql($versionName);

        if ($this->isMssql) {
            return $this->query('if not exists(select version from #TABLE1# where version=\'%s\')
                    begin
                        INSERT INTO #TABLE1# (version) VALUES (\'%s\')
                    end',
                $versionName,
                $versionName
            );

        } else {
            return $this->query('INSERT IGNORE INTO `#TABLE1#` (`version`) VALUES ("%s")',
                $versionName
            );
        }

    }

    /**
     * @param $versionName
     * @return bool|\CDBResult
     */
    public function removeRecord($versionName) {
        $versionName = $this->forSql($versionName);

        if ($this->isMssql) {
            return $this->query('DELETE FROM #TABLE1# WHERE version = \'%s\'',
                $versionName
            );
        } else {
            return $this->query('DELETE FROM `#TABLE1#` WHERE `version` = "%s"',
                $versionName
            );
        }
    }
}
