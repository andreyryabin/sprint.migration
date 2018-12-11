<?php

namespace Sprint\Migration\Tables;

class VersionTable extends AbstractTable
{

    /**
     * @return array
     */
    public function getRecords() {
        $dbres = $this->query('SELECT * FROM `#TABLE1#`');
        $result = array();
        while ($item = $dbres->Fetch()){
            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param $versionName
     * @return array
     */
    public function getRecord($versionName) {
        return $this->query('SELECT * FROM `#TABLE1#` WHERE `version` = "%s"',
            $this->forSql($versionName)
        )->Fetch();
    }

    /**
     * @param $meta
     * @return bool|\CDBResult
     */
    public function addRecord($meta) {
        return $this->query('INSERT IGNORE INTO `#TABLE1#` (`version`, `hash`) VALUES ("%s", "%s")',
            $this->forSql($meta['version']),
            $this->forSql($meta['hash'])
        );
    }

    /**
     * @param $meta
     * @return bool|\CDBResult
     */
    public function removeRecord($meta) {
        return $this->query('DELETE FROM `#TABLE1#` WHERE `version` = "%s"',
            $this->forSql($meta['version'])
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
            $this->query('ALTER TABLE `#TABLE1#` ADD COLUMN `hash` VARCHAR(50) NULL AFTER `version`');
        }
    }

    protected function dropTable(){
        $this->query('DROP TABLE IF EXISTS `#TABLE1#`;');
    }

}
