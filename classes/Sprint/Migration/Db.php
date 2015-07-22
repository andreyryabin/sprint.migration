<?php

namespace Sprint\Migration;

class Db
{
    protected $charset = '';
    protected $collate = '';
    protected $versionsTable = '';
    protected $dbName = '';
    protected $isMssql = false;
    protected $isWin1251 = false;

    protected $bitrixDb = null;

    public function __construct(){
        $this->versionsTable = Env::getVersionsTable();
        $this->isMssql = Env::isMssql();
        $this->isWin1251 = Env::isWin1251();
        $this->bitrixDb = Env::getDb();
        $this->dbName = Env::getDbName();

        if ($this->isWin1251){
            $this->charset = 'cp1251';
            $this->collate = 'cp1251_general_ci';
        } else {
            $this->charset = 'utf8';
            $this->collate = 'utf8_general_ci';
        }
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
        return $this->bitrixDb->Query($query);
    }

    /**
     * @return bool|\CDBResult
     */
    public function getRecords() {
        if ($this->isMssql) {
            return $this->query('SELECT * FROM %s',
                $this->versionsTable
            );
        } else {
            return $this->query('SELECT * FROM `%s`',
                $this->versionsTable
            );
        }
    }

    /**
     * @param $versionName
     * @return bool|\CDBResult
     */
    public function getRecordByName($versionName) {
        $versionName = $this->forSql($versionName);

        if ($this->isMssql) {
            return $this->query('SELECT * FROM %s WHERE version = \'%s\'',
                $this->versionsTable,
                $versionName
            );

        } else {
            return $this->query('SELECT * FROM `%s` WHERE `version` = "%s"',
                $this->versionsTable,
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
            return $this->query('if not exists(select version from %s where version=\'%s\')
                    begin
                        INSERT INTO %s (version) VALUES (\'%s\')
                    end',
                $this->versionsTable,
                $versionName,
                $this->versionsTable,
                $versionName
            );

        } else {
            return $this->query('INSERT IGNORE INTO `%s` (`version`) VALUES ("%s")',
                $this->versionsTable,
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
            return $this->query('DELETE FROM %s WHERE version = \'%s\'',
                $this->versionsTable,
                $versionName
            );
        } else {
            return $this->query('DELETE FROM `%s` WHERE `version` = "%s"',
                $this->versionsTable,
                $versionName
            );
        }
    }
}
