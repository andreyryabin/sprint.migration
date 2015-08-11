<?php

namespace Sprint\Migration;

class Upgrade0002 extends Upgrade {


    public function doUpgradeMssql() {
    }

    public function doUpgradeMysql() {
        if (!$this->columnExisistFilecode()){

            $this->query('ALTER TABLE `#TABLE1#`
                ADD `description` VARCHAR( 500 ) CHARACTER SET #CHARSET# COLLATE #COLLATE# NOT NULL DEFAULT "";'
            );

            $this->query('ALTER TABLE `#TABLE1#`
                ADD `filecode` blob NOT NULL DEFAULT "";'
            );
        }
    }

    protected function columnExisistFilecode(){
        return $this->query('SELECT * FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_NAME = "#TABLE1#"
                AND TABLE_SCHEMA = "#DBNAME#"
                AND COLUMN_NAME = "filecode"'
        )->Fetch();


    }

}
