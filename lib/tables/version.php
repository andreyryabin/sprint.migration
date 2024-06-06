<?php

namespace Sprint\Migration\Tables;

use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Traits\CurrentUserTrait;

class VersionTable extends AbstractTable
{
    use CurrentUserTrait;

    const DATE_FORMAT = 'Y-m-d H:i:s';
    protected int $tableVersion = 4;

    /**
     * @throws MigrationException
     */
    public function getRecords(): array
    {
        $records = $this->query('SELECT * FROM `#TABLE1#`')->fetchAll();

        return array_map(function ($record) {
            return $this->prepareFetch($record);
        }, $records);
    }

    /**
     * @throws MigrationException
     */
    public function getRecord(string $versionName): array
    {
        $record = $this->query(
            'SELECT * FROM `#TABLE1#` WHERE `version` = "%s" LIMIT 1',
            $this->forSql($versionName)
        )->fetch();

        return $record ? $this->prepareFetch($record) : [];
    }

    /**
     * @throws MigrationException
     */
    public function addRecord(array $record)
    {
        $version = $this->forSql($record['version']);
        $hash = $this->forSql($record['hash']);
        $tag = $this->forSql($record['tag']);

        $meta = $this->forSql(serialize([
            'created_by' => $this->getCurrentUserLogin(),
            'created_at' => date(VersionTable::DATE_FORMAT),
        ]));

        $this->query(
            'INSERT INTO `#TABLE1#` (`version`, `hash`, `tag`, `meta`) ' .
            'VALUES ("%s", "%s", "%s", "%s") ' .
            'ON DUPLICATE KEY UPDATE `hash` = "%s", `tag` = "%s"',
            $version, $hash, $tag, $meta, $hash, $tag
        );
    }

    /**
     * @throws MigrationException
     */
    public function removeRecord(array $meta)
    {
        $version = $this->forSql($meta['version']);

        $this->query('DELETE FROM `#TABLE1#` WHERE `version` = "%s"', $version);
    }

    /**
     * @throws MigrationException
     */
    public function updateTag(string $versionName, string $tag = '')
    {
        $versionName = $this->forSql($versionName);
        $tag = $this->forSql($tag);

        $this->query('UPDATE `#TABLE1#` SET `tag` = "%s" WHERE `version` = "%s"', $tag, $versionName);
    }

    /**
     * @throws MigrationException
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

        //tableVersion 4
        $this->query('ALTER TABLE `#TABLE1#` MODIFY `hash` VARCHAR(255)');
        if (empty($this->query('SHOW COLUMNS FROM `#TABLE1#` LIKE "meta"')->fetch())) {
            $this->query('ALTER TABLE `#TABLE1#` ADD COLUMN `meta` TEXT NULL AFTER `tag`');
        }
    }

    /**
     * @throws MigrationException
     */
    protected function dropTable()
    {
        $this->query('DROP TABLE IF EXISTS `#TABLE1#`;');
    }

    private function prepareFetch(array $record): array
    {
        $record['meta'] = unserialize($record['meta']);
        return $record;
    }
}
