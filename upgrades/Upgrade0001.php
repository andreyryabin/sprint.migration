<?php

namespace Sprint\Migration;

class Upgrade0001 extends Upgrade {

    public function doUpgrade(){

        $db = new Db();

        if ($this->isMssql()){
            $db->query('if not exists (SELECT * FROM sysobjects WHERE name=\'#TABLE1#\' AND xtype=\'U\')
                begin
                    CREATE TABLE #TABLE1#
                    (id int IDENTITY (1,1) NOT NULL,
                    version varchar(255) NOT NULL,
                    PRIMARY KEY (id),
                    UNIQUE (version))
                end'
            );
        } else {
            $db->query('CREATE TABLE IF NOT EXISTS `#TABLE1#`(
              `id` MEDIUMINT NOT NULL AUTO_INCREMENT NOT NULL,
              `version` varchar(255) COLLATE #COLLATE# NOT NULL,
              PRIMARY KEY (id), UNIQUE KEY(version)
              )ENGINE=InnoDB DEFAULT CHARSET=#CHARSET# COLLATE=#COLLATE# AUTO_INCREMENT=1;'
            );
        }
    }

}