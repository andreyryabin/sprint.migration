<?php

namespace Sprint\Migration;

class Upgrade0002 extends Upgrade {


    public function doUpgradeMssql() {
        //
    }

    public function doUpgradeMysql() {
        $dbRes = $this->query('SELECT * FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_NAME = "%s"
                AND TABLE_SCHEMA = "%s"
                AND COLUMN_NAME = "description"',
            $this->versionsTable,
            $this->dbName
        );

        if ($dbRes->Fetch()) {
            return true;
        }

        $this->query('ALTER TABLE `%s` ADD `description` varchar(255)
                CHARACTER SET %s
                COLLATE %s NOT NULL
                DEFAULT ""',
            $this->versionsTable,
            $this->charset,
            $this->collate
        );

    }

}