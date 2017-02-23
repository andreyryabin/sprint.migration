<?php

namespace Sprint\Migration;

class VersionTable extends AbstractTable
{ 

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
    public function getRecord($versionName) {
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

    protected function createTable() {
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

}
