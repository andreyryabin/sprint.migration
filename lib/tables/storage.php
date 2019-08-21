<?php

namespace Sprint\Migration\Tables;

class StorageTable extends AbstractTable
{

    public function __construct($name = 'default')
    {
        parent::__construct('sprint_storage_' . $name);
    }

    protected function createTable()
    {
        $this->query('CREATE TABLE IF NOT EXISTS `#TABLE1#`(
              `id` INT NOT NULL AUTO_INCREMENT NOT NULL,
              `category` varchar(255) COLLATE #COLLATE# NOT NULL,
              `name` varchar(255) COLLATE #COLLATE# NOT NULL,
              `data` longtext COLLATE #COLLATE# NOT NULL, 
              PRIMARY KEY (id), UNIQUE KEY `fullname` (`category`,`name`)
              )ENGINE=InnoDB DEFAULT CHARSET=#CHARSET# COLLATE=#COLLATE# AUTO_INCREMENT=1;'
        );
    }

    protected function dropTable()
    {
        $this->query('DROP TABLE IF EXISTS `#TABLE1#`;');
    }

}



