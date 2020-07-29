<?php

namespace Sprint\Migration\Tables;

use Bitrix\Main\DB\SqlQueryException;

class VersionTable extends AbstractTable
{
    protected $tableVersion = 3;

    /**
     * @throws SqlQueryException
     * @return array
     */
    public function getRecords()
    {
        return $this->query('SELECT * FROM `#TABLE1#`')->fetchAll();
    }

    /**
     * @param $versionName
     *
     * @throws SqlQueryException
     * @return array|false
     */
    public function getRecord($versionName)
    {
        return $this->query(
            'SELECT * FROM `#TABLE1#` WHERE `version` = "%s"',
            $this->forSql($versionName)
        )->fetch();
    }

    /**
     * @param $meta
     *
     * @throws SqlQueryException
     */
    public function addRecord($meta)
    {
        $version = $this->forSql($meta['version']);
        $hash = $this->forSql($meta['hash']);
        $tag = $this->forSql($meta['tag']);

        $this->query(
            'INSERT INTO `#TABLE1#` (`version`, `hash`, `tag`) VALUES ("%s", "%s", "%s") 
                    ON DUPLICATE KEY UPDATE `hash` = "%s", `tag` = "%s"',
            $version, $hash, $tag, $hash, $tag
        );
    }

    /**
     * @param $meta
     *
     * @throws SqlQueryException
     */
    public function removeRecord($meta)
    {
        $version = $this->forSql($meta['version']);

        $this->query('DELETE FROM `#TABLE1#` WHERE `version` = "%s"', $version);
    }

    /**
     * @param        $version
     * @param string $tag
     *
     * @throws SqlQueryException
     */
    public function updateTag($version, $tag = '')
    {
        $version = $this->forSql($version);
        $tag = $this->forSql($tag);

        $this->query('UPDATE `#TABLE1#` SET `tag` = "%s" WHERE `version` = "%s"', $tag, $version);
    }

    /**
     * @throws SqlQueryException
     */
    protected function createTable()
    {
        //tableVersion 1
        $this->query(
            'CREATE TABLE IF NOT EXISTS `#TABLE1#`(
              `id` MEDIUMINT NOT NULL AUTO_INCREMENT NOT NULL,
              `version` varchar(255) COLLATE #COLLATE# NOT NULL,
              PRIMARY KEY (id), UNIQUE KEY(version)
              )ENGINE=InnoDB DEFAULT CHARSET=#CHARSET# COLLATE=#COLLATE# AUTO_INCREMENT=1;'
        );

        //tableVersion 2
        if (empty($this->query('SHOW COLUMNS FROM `#TABLE1#` LIKE "hash"')->fetch())) {
            $this->query('ALTER TABLE `#TABLE1#` ADD COLUMN `hash` VARCHAR(50) NULL AFTER `version`');
        }

        //tableVersion 3
        if (empty($this->query('SHOW COLUMNS FROM `#TABLE1#` LIKE "tag"')->fetch())) {
            $this->query('ALTER TABLE `#TABLE1#` ADD COLUMN `tag` VARCHAR(50) NULL AFTER `hash`');
        }
    }

    /**
     * @throws SqlQueryException
     */
    protected function dropTable()
    {
        $this->query('DROP TABLE IF EXISTS `#TABLE1#`;');
    }
}
