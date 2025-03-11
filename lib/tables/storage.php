<?php

namespace Sprint\Migration\Tables;

use Sprint\Migration\Exceptions\MigrationException;

class StorageTable extends AbstractTable
{
    protected string $category;

    /**
     * @throws MigrationException
     */
    public function __construct(string $storageName, string $category)
    {
        parent::__construct('sprint_storage_' . $storageName);
        $this->category = $category;

        if (empty($category)) {
            throw new MigrationException('Need storage category');
        }
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @throws MigrationException
     */
    public function saveData(string $name, $value = '')
    {
        match ($this->getDataBaseType()) {
            'pgsql' => $this->saveDataForPGSQL($name, $value),
            default => $this->saveDataForOtherDatabases($name, $value),
        };
    }

    /**
     * @param string $name
     * @param $value
     * @return void
     * @throws MigrationException
     */
    protected function saveDataForPGSQL(string $name, $value = '') : void
    {
        if (empty($name)) {
            throw new MigrationException('Need name kedy for saved data');
        }

        $value = $this->forSql(serialize($value));
        $this->query(
            'INSERT INTO #TABLE1# (category,name, data) VALUES (\'%s\', \'%s\', \'%s\') 
                    ON CONFLICT (category, name) DO UPDATE SET data = \'%s\'',
            $this->forSql($this->category),
            $this->forSql($name),
            $value,
            $value
        );
    }

    /**
     * @param string $name
     * @param $value
     * @return void
     * @throws MigrationException
     */
    protected function saveDataForOtherDatabases(string $name, $value = '') : void
    {
        if (empty($name)) {
            throw new MigrationException('Need name kedy for saved data');
        }

        $value = $this->forSql(serialize($value));
        $this->query(
            'INSERT INTO `#TABLE1#` (`category`,`name`, `data`) VALUES ("%s", "%s", "%s") 
                    ON DUPLICATE KEY UPDATE data = "%s"',
            $this->forSql($this->category),
            $this->forSql($name),
            $value,
            $value
        );
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @throws MigrationException
     * @return mixed|string
     */
    public function getSavedData(string $name, $default = '')
    {
        return match ($this->getDataBaseType()) {
            'pgsql' => $this->getSavedDataForPGSQL($name, $default),
            default => $this->getSavedDataForOtherDatabases($name, $default),
        };
    }


    /**
     * @param string $name
     * @param $default
     * @return array|string
     * @throws MigrationException
     */
    protected function getSavedDataForPGSQL(string $name, $default = '') : array
    {
        if (empty($name)) {
            throw new MigrationException('Need name kedy for saved data');
        }

        $value = $this->query(
            'SELECT name, data FROM #TABLE1# WHERE category = \'%s\' AND `name` = \'%s\'',
            $this->forSql($this->category),
            $this->forSql($name)
        )->Fetch();
        if ($value && $value['data']) {
            return unserialize($value['data']);
        }

        return $default;
    }


    /**
     * @param string $name
     * @param $default
     * @return array|string
     * @throws MigrationException
     */
    protected function getSavedDataForOtherDatabases(string $name, $default = '') : array
    {
        if (empty($name)) {
            throw new MigrationException('Need name kedy for saved data');
        }

        $value = $this->query(
            'SELECT name, data FROM #TABLE1# WHERE `category` = "%s" AND `name` = "%s"',
            $this->forSql($this->category),
            $this->forSql($name)
        )->Fetch();
        if ($value && $value['data']) {
            return unserialize($value['data']);
        }

        return $default;
    }

    /**
     * @throws MigrationException
     */
    public function deleteSavedData(string $name = '')
    {
        match ($this->getDataBaseType()) {
            'pgsql' => $this->deleteSavedDataForPGSQL($name),
            default => $this->deleteSavedDataForOtherDatabases($name),
        };
    }

    /**
     * @param string $name
     * @return void
     * @throws MigrationException
     */
    protected function deleteSavedDataForPGSQL(string $name = '') : void
    {
        if ($name) {
            $this->query(
                'DELETE FROM #TABLE1# WHERE category = \'%s\' AND `name` = \'%s\'',
                $this->forSql($this->category),
                $this->forSql($name)
            );
        } else {
            $this->query(
                'DELETE FROM #TABLE1# WHERE category = "%s',
                $this->forSql($this->category)
            );
        }
    }

    /**
     * @param string $name
     * @return void
     * @throws MigrationException
     */
    protected function deleteSavedDataForOtherDatabases(string $name = '') : void
    {
        if ($name) {
            $this->query(
                'DELETE FROM `#TABLE1#` WHERE `category` = "%s" AND `name` = "%s"',
                $this->forSql($this->category),
                $this->forSql($name)
            );
        } else {
            $this->query(
                'DELETE FROM `#TABLE1#` WHERE `category` = "%s"',
                $this->forSql($this->category)
            );
        }
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
        $this->query(
            'CREATE TABLE IF NOT EXISTS #TABLE1# (
                id SERIAL PRIMARY KEY,
                category VARCHAR(255) COLLATE "#COLLATE#" NOT NULL,
                name VARCHAR(255) COLLATE "#COLLATE#" NOT NULL,
                data TEXT COLLATE "#COLLATE#" NOT NULL,
                CONSTRAINT fullname UNIQUE (category, name)
              )'
        );
    }

    /**
     * @return void
     * @throws MigrationException
     */
    protected function createTableForOtherDatabases() : void
    {
        $this->query(
            'CREATE TABLE IF NOT EXISTS `#TABLE1#`(
              `id` INT NOT NULL AUTO_INCREMENT NOT NULL,
              `category` varchar(255) COLLATE #COLLATE# NOT NULL,
              `name` varchar(255) COLLATE #COLLATE# NOT NULL,
              `data` longtext COLLATE #COLLATE# NOT NULL, 
              PRIMARY KEY (id), UNIQUE KEY `fullname` (`category`,`name`)
              )ENGINE=InnoDB DEFAULT CHARSET=#CHARSET# COLLATE=#COLLATE# AUTO_INCREMENT=1;'
        );
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

    /**
     * @return void
     * @throws MigrationException
     */
    protected function dropTableForPGSQL() : void
    {
        $this->query('DROP TABLE IF EXISTS #TABLE1#;');
    }

    /**
     * @return void
     * @throws MigrationException
     */
    protected function dropTableForOtherDatabases() : void
    {
        $this->query('DROP TABLE IF EXISTS `#TABLE1#`;');
    }
}



