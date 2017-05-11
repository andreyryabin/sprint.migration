<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\MigrationException;

class StorageManager extends AbstractTable
{

    public function __construct($name = 'default') {
        if ($this->isMssql) {
            Throw new MigrationException('StorageManager doesn\'t work in mssql mode');
        }
        parent::__construct('sprint_storage_' . $name);
    }

    protected function createTable() {
        $this->query('CREATE TABLE IF NOT EXISTS `#TABLE1#`(
              `id` INT NOT NULL AUTO_INCREMENT NOT NULL,
              `category` varchar(255) COLLATE #COLLATE# NOT NULL,
              `name` varchar(255) COLLATE #COLLATE# NOT NULL,
              `data` longtext COLLATE #COLLATE# NOT NULL, 
              PRIMARY KEY (id), UNIQUE KEY `fullname` (`category`,`name`)
              )ENGINE=InnoDB DEFAULT CHARSET=#CHARSET# COLLATE=#COLLATE# AUTO_INCREMENT=1;'
        );
    }

    public function saveData($category, $name, $value = '') {
        $category = $this->forSql($category);
        $name = $this->forSql($name);

        if (!empty($category) && !empty($name)) {
            if (!empty($value)) {
                $value = $this->forSql(serialize($value));
                $this->query('INSERT INTO `#TABLE1#` (`category`,`name`, `data`) VALUES ("%s", "%s", "%s") 
                    ON DUPLICATE KEY UPDATE data = "%s"',
                    $category,
                    $name,
                    $value,
                    $value
                );
            }
        }
    }

    public function getSavedData($category, $name, $default = '') {
        $category = $this->forSql($category);
        $name = $this->forSql($name);

        if (!empty($category) && !empty($name)) {
            $value = $this->query('SELECT name, data FROM #TABLE1# WHERE `category` = "%s" AND `name` = "%s"',
                $category,
                $name
            )->Fetch();
            if ($value && $value['data']) {
                return unserialize($value['data']);
            }
        }
        return $default;
    }

    public function deleteSavedData($category, $name = false){
        $category = $this->forSql($category);

        if ($category && $name) {
            $name = $this->forSql($name);
            $this->query('DELETE FROM `#TABLE1#` WHERE `category` = "%s" AND `name` = "%s"',
                $category,
                $name
            );
        } elseif ($category){
            $this->query('DELETE FROM `#TABLE1#` WHERE `category` = "%s"',
                $category
            );
        }
    }

}



