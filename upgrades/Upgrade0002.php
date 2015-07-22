<?php

namespace Sprint\Migration;

class Upgrade0002 extends Upgrade {


    public function doUpgrade(){

        if ($this->isMssql){


        } else {

            $dbRes = $this->query('SELECT * FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_NAME = "%s"
                AND TABLE_SCHEMA = "%s"
                AND COLUMN_NAME = "d1escription"',
                $this->versionsTable,
                $this->dbName
            );

            if ($dbRes->Fetch()){
                $this->query('ALTER TABLE `%s` ADD `description` TEXT CHARACTER SET %s COLLATE %s NOT NULL DEFAULT ""',
                    $this->versionsTable,
                    $this->charset,
                    $this->collate
                );
            }

        }

    }

}