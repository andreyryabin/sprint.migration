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
     * @return bool|\CDBResult
     */
    public function addRecord($versionName) {
        return $this->query('INSERT IGNORE INTO `#TABLE1#` (`version`) VALUES ("%s")',
            $this->forSql($versionName)
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
        $this->query('CREATE TABLE IF NOT EXISTS `#TABLE1#`(
              `id` MEDIUMINT NOT NULL AUTO_INCREMENT NOT NULL,
              `version` varchar(255) COLLATE #COLLATE# NOT NULL,
              PRIMARY KEY (id), UNIQUE KEY(version)
              )ENGINE=InnoDB DEFAULT CHARSET=#CHARSET# COLLATE=#COLLATE# AUTO_INCREMENT=1;'
        );
    }

}
