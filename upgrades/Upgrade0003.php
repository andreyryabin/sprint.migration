<?php

namespace Sprint\Migration;

class Upgrade0003 extends Upgrade {


    public function doUpgradeMssql() {
        //
    }

    public function doUpgradeMysql() {

        $this->query('CREATE TABLE IF NOT EXISTS `#TABLE2#` (
                `id` mediumint(9) NOT NULL AUTO_INCREMENT,
                `version` varchar(255) COLLATE #COLLATE# NOT NULL,
                `filehash` varchar(255) COLLATE #COLLATE# NOT NULL DEFAULT "",
                `filecode` blob NOT NULL DEFAULT "",
                PRIMARY KEY (`id`),
                UNIQUE KEY `version` (`version`)
            ) ENGINE=InnoDB DEFAULT CHARSET=#CHARSET# AUTO_INCREMENT=1 ;'
        );


    }

}
