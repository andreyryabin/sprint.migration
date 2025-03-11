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
        return match ($this->getDataBaseType()) {
            'pgsql' => $this->getRecordsForPGSQL(),
            default => $this->getRecordsForOtherDatabases(),
        };
    }

    /**
     * @return array
     * @throws MigrationException
     */
    protected function getRecordsForPGSQL() : array
    {
        $records = $this->query('SELECT * FROM #TABLE1#')->fetchAll();

        return array_map(function ($record) {
            return $this->prepareFetch(array_change_key_case($record));
        }, $records);
    }

    /**
     * @return array|array[]
     * @throws MigrationException
     */
    protected function getRecordsForOtherDatabases() : array
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
        return match ($this->getDataBaseType()) {
            'pgsql' => $this->getRecordForPGSQL($versionName),
            default => $this->getRecordForOtherDatabases($versionName),
        };
    }

    /**
     * @param string $versionName
     * @return array
     * @throws MigrationException
     */
    protected function getRecordForPGSQL(string $versionName) : array
    {
        $record = $this->query(
            'SELECT * FROM #TABLE1# WHERE version = \'%s\' LIMIT 1',
            $this->forSql($versionName)
        )->fetch();

        return $record ? $this->prepareFetch($record) : [];
    }

    /**
     * @param string $versionName
     * @return array
     * @throws MigrationException
     */
    protected function getRecordForOtherDatabases(string $versionName) : array
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
        match ($this->getDataBaseType()) {
            'pgsql' => $this->addRecordForPGSQL($record),
            default => $this->addRecordForOtherDatabases($record),
        };
    }

    /**
     * @param array $record
     * @return void
     * @throws MigrationException
     */
    protected function addRecordForPGSQL(array $record) : void
    {
        $version = $this->forSql($record['version']);
        $hash = $this->forSql($record['hash']);
        $tag = $this->forSql($record['tag']);

        $meta = $this->forSql(serialize([
            'created_by' => $this->getCurrentUserLogin(),
            'created_at' => date(VersionTable::DATE_FORMAT),
        ]));

        $this->query(
            'INSERT INTO #TABLE1# (version, hash, tag, meta) ' .
            'VALUES (\'%s\', \'%s\', \'%s\', \'%s\') ' .
            'ON CONFLICT (version) DO UPDATE SET hash = \'%s\', tag = \'%s\';',
            $version, $hash, $tag, $meta, $hash, $tag
        );
    }

    /**
     * @param array $record
     * @return void
     * @throws MigrationException
     */
    protected function addRecordForOtherDatabases(array $record) : void
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
        match ($this->getDataBaseType()) {
            'pgsql' => $this->removeRecordForPGSQL($meta),
            default => $this->removeRecordForOtherDatabases($meta),
        };
    }

    /**
     * @param array $meta
     * @return void
     * @throws MigrationException
     */
    protected function removeRecordForPGSQL(array $meta) : void
    {
        $version = $this->forSql($meta['version']);

        $this->query('DELETE FROM #TABLE1# WHERE version = \'%s\'', $version);
    }

    /**
     * @param array $meta
     * @return void
     * @throws MigrationException
     */
    protected function removeRecordForOtherDatabases(array $meta) : void
    {
        $version = $this->forSql($meta['version']);

        $this->query('DELETE FROM `#TABLE1#` WHERE `version` = "%s"', $version);
    }

    /**
     * @throws MigrationException
     */
    public function updateTag(string $versionName, string $tag = '')
    {
        match ($this->getDataBaseType()) {
            'pgsql' => $this->updateTagForPGSQL($versionName, $tag),
            default => $this->updateTagForOtherDatabases($versionName, $tag),
        };
    }

    protected function updateTagForPGSQL(string $versionName, string $tag = '') : void
    {
        $versionName = $this->forSql($versionName);
        $tag = $this->forSql($tag);

        $this->query('UPDATE #TABLE1# SET tag = \'%s\' WHERE version = \'%s\'', $tag, $versionName);
    }

    /**
     * @param string $versionName
     * @param string $tag
     * @return void
     * @throws MigrationException
     */
    protected function updateTagForOtherDatabases(string $versionName, string $tag = '') : void
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
        match ($this->getDataBaseType()) {
            'pgsql' => $this->createTableForPGSQL(),
            default => $this->createTableForOtherDatabases(),
        };
    }

    /**
     * @return void
     * @throws MigrationException
     */
    protected function createTableForPGSQL() : void
    {
        //tableVersion 1
        $this->query(
            'CREATE TABLE IF NOT EXISTS #TABLE1# (
              id SERIAL PRIMARY KEY,
              version VARCHAR(255) NOT NULL,
            CONSTRAINT version_unique UNIQUE (version)
              );'
        );

        //tableVersion 2
        if (empty($this->query("SELECT column_name FROM information_schema.columns WHERE table_name = '#TABLE1#' AND column_name = 'hash'")->fetch())) {
            $this->query('ALTER TABLE #TABLE1# ADD COLUMN hash VARCHAR(50)');
        }

        //tableVersion 3
        if (empty($this->query("SELECT column_name FROM information_schema.columns WHERE table_name = '#TABLE1#' AND column_name = 'tag'")->fetch())) {
            $this->query('ALTER TABLE #TABLE1# ADD COLUMN tag VARCHAR(50)');
        }

        //tableVersion 4
        $this->query('ALTER TABLE #TABLE1# ALTER COLUMN hash TYPE VARCHAR(255)');
        if (empty($this->query("SELECT column_name FROM information_schema.columns WHERE table_name = '#TABLE1#' AND column_name = 'meta'")->fetch())) {
            $this->query('ALTER TABLE #TABLE1# ADD COLUMN meta TEXT');
        }
    }

    /**
     * @return void
     * @throws MigrationException
     */
    protected function createTableForOtherDatabases() : void
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
        match ($this->getDataBaseType()) {
            'pgsql' => $this->dropTableForPGSQL(),
            default => $this->dropTableForOtherDatabases(),
        };
    }

    protected function dropTableForPGSQL()
    {
        $this->query('DROP TABLE IF EXISTS #TABLE1#;');
    }

    protected function dropTableForOtherDatabases()
    {
        $this->query('DROP TABLE IF EXISTS `#TABLE1#`;');
    }

    private function prepareFetch(array $record): array
    {
        $record['meta'] = unserialize($record['meta']);
        return $record;
    }
}
