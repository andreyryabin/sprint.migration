<?php

namespace Sprint\Migration;

class Upgrade0002 extends Upgrade {


    public function doUpgradeMssql() {
        //
    }

    public function doUpgradeMysql() {
        if (!$this->descrColumnExisist()){
            $this->query('ALTER TABLE `#TABLE1#`
                ADD `description` VARCHAR( 500 ) CHARACTER SET #CHARSET# COLLATE #COLLATE# NOT NULL DEFAULT "";'
            );
        }
    }

    protected function descrColumnExisist(){
        return $this->query('SELECT * FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_NAME = "#TABLE1#"
                AND TABLE_SCHEMA = "#DBNAME#"
                AND COLUMN_NAME = "description"'
        )->Fetch();


    }

}
