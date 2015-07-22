<?php

namespace Sprint\Migration;

class Upgrade0001 extends Upgrade {

    public function doUpgradeMssql(){
        $this->query('if not exists (SELECT * FROM sysobjects WHERE name=\'%s\' AND xtype=\'U\')
                begin
                    CREATE TABLE %s
                    (id int IDENTITY (1,1) NOT NULL,
                    version varchar(255) NOT NULL,
                    PRIMARY KEY (id),
                    UNIQUE (version))
                end',
            $this->versionsTable,
            $this->versionsTable
        );
    }

    public function doUpgradeMysql(){
        $this->query('CREATE TABLE IF NOT EXISTS `%s`(
              `id` MEDIUMINT NOT NULL AUTO_INCREMENT NOT NULL,
              `version` varchar(255) COLLATE %s NOT NULL,
              PRIMARY KEY (id), UNIQUE KEY(version)
              )ENGINE=InnoDB DEFAULT CHARSET=%s COLLATE=%s AUTO_INCREMENT=1;',
            $this->versionsTable,
            $this->collate,
            $this->charset,
            $this->collate
        );
    }

}