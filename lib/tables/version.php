<?php

namespace Sprint\Migration\Tables;

use \Bitrix\Main\Db\SqlQueryException;
use \Bitrix\Main\Db\Result;

class VersionTable extends AbstractTable
{

    protected $tableVersion = 3;

    /**
     * @return array
     * @throws SqlQueryException
     */
    public function getRecords()
    {
        return $this->query('SELECT * FROM `#TABLE1#`')->fetchAll();
    }

    /**
     * @param $versionName
     * @return array|false
     * @throws SqlQueryException
     */
    public function getRecord($versionName)
    {
        return $this->query('SELECT * FROM `#TABLE1#` WHERE `version` = "%s"',
            $this->forSql($versionName)
        )->fetch();
    }

    /**
     * @param $meta
     * @throws SqlQueryException
     */
    public function addRecord($meta)
    {
        $this->query('INSERT IGNORE INTO `#TABLE1#` (`version`, `hash`) VALUES ("%s", "%s")',
            $this->forSql($meta['version']),
            $this->forSql($meta['hash'])
        );
    }

    /**
     * @param $meta
     * @throws SqlQueryException
     */
    public function removeRecord($meta)
    {
        $this->query('DELETE FROM `#TABLE1#` WHERE `version` = "%s"',
            $this->forSql($meta['version'])
        );
    }

    protected function createTable()
    {
        //tableVersion 1
        $this->query('CREATE TABLE IF NOT EXISTS `#TABLE1#`(
              `id` MEDIUMINT NOT NULL AUTO_INCREMENT NOT NULL,
              `version` varchar(255) COLLATE #COLLATE# NOT NULL,
              PRIMARY KEY (id), UNIQUE KEY(version)
              )ENGINE=InnoDB DEFAULT CHARSET=#CHARSET# COLLATE=#COLLATE# AUTO_INCREMENT=1;'
        );

        //tableVersion 2
        if (empty($this->query('SHOW COLUMNS FROM `#TABLE1#` LIKE "hash"')->Fetch())) {
            $this->query('ALTER TABLE `#TABLE1#` ADD COLUMN `hash` VARCHAR(50) NULL AFTER `version`');
        }

        //tableVersion 3
        if (empty($this->query('SHOW COLUMNS FROM `#TABLE1#` LIKE "tag"')->Fetch())) {
            $this->query('ALTER TABLE `#TABLE1#` ADD COLUMN `tag` VARCHAR(50) NULL AFTER `hash`');
        }
    }

    protected function dropTable()
    {
        $this->query('DROP TABLE IF EXISTS `#TABLE1#`;');
    }

}
