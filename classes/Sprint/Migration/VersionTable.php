<?php

namespace Sprint\Migration;

class VersionTable extends AbstractTable
{

    /**
     * @return bool|\CDBResult
     */
    public function getRecords() {
        return $this->query('SELECT * FROM `#TABLE1#`');
    }

    /**
     * @param $versionName
     * @return bool|\CDBResult
     */
    public function getRecord($versionName) {
        return $this->query('SELECT * FROM `#TABLE1#` WHERE `version` = "%s"',
            $this->forSql($versionName)
        );
    }

    /**
     * @param $versionName
     * @param $hash
     * @return bool|\CDBResult
     */
    public function addRecord($versionName,$hash='') {
        return $this->query('INSERT IGNORE INTO `#TABLE1#` (`version`, `hash`) VALUES ("%s", "%s")',
            $this->forSql($versionName),
            $this->forSql($hash)
        );
    }

    /**
     * @param $versionName
     * @return bool|\CDBResult
     */
    public function removeRecord($versionName) {
        return $this->query('DELETE FROM `#TABLE1#` WHERE `version` = "%s"',
            $this->forSql($versionName)
        );
    }

    protected function createTable() {
        //upgrade1
        $this->query('CREATE TABLE IF NOT EXISTS `#TABLE1#`(
              `id` MEDIUMINT NOT NULL AUTO_INCREMENT NOT NULL,
              `version` varchar(255) COLLATE #COLLATE# NOT NULL,
              PRIMARY KEY (id), UNIQUE KEY(version)
              )ENGINE=InnoDB DEFAULT CHARSET=#CHARSET# COLLATE=#COLLATE# AUTO_INCREMENT=1;'
        );

        //upgrade2
        if (empty($this->query('SHOW COLUMNS FROM `#TABLE1#` LIKE "hash"')->Fetch())) {
            $this->query('ALTER TABLE #TABLE1# ADD COLUMN `hash` VARCHAR(50) NULL AFTER `version`');
        }
    }

}
