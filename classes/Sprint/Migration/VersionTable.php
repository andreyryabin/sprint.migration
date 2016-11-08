<?php

namespace Sprint\Migration;

class VersionTable
{

    private $isMssql = false;
    private $dbName = false;

    /** @var \CDatabase */
    private $bitrixDb = null;

    private $tableName = '';

    public function __construct($tableName) {
        $this->tableName = $tableName;

        $this->isMssql = ($GLOBALS['DBType'] == 'mssql');
        $this->bitrixDb = $GLOBALS['DB'];
        $this->dbName = $GLOBALS['DBName'];

        $opt = 'table_' . md5($this->tableName);
        if (!Module::getDbOption($opt)) {
            $this->createTables();
            Module::setDbOption($opt, 1);
        }
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
        $versionName = $this->bitrixDb->ForSql($versionName);

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
        $versionName = $this->bitrixDb->ForSql($versionName);

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
        $versionName = $this->bitrixDb->ForSql($versionName);

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

    protected function createTables() {
        if ($this->isMssql) {
            $this->query('if not exists (SELECT * FROM sysobjects WHERE name=\'#TABLE1#\' AND xtype=\'U\')
                begin
                    CREATE TABLE #TABLE1#
                    (id int IDENTITY (1,1) NOT NULL,
                    version varchar(255) NOT NULL,
                    PRIMARY KEY (id),
                    UNIQUE (version))
                end'
            );
        } else {
            $this->query('CREATE TABLE IF NOT EXISTS `#TABLE1#`(
              `id` MEDIUMINT NOT NULL AUTO_INCREMENT NOT NULL,
              `version` varchar(255) COLLATE #COLLATE# NOT NULL,
              PRIMARY KEY (id), UNIQUE KEY(version)
              )ENGINE=InnoDB DEFAULT CHARSET=#CHARSET# COLLATE=#COLLATE# AUTO_INCREMENT=1;'
            );
        }
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
}



