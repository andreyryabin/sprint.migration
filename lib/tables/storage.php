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
        $this->query('DROP TABLE IF EXISTS `#TABLE1#`;');
    }
}



