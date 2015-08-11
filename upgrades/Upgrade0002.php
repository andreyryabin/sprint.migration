<?php

namespace Sprint\Migration;

class Upgrade0002 extends Upgrade {


    public function doUpgradeMssql() {
    }

    public function doUpgradeMysql() {
        if (!$this->columnExisistFilecode()){

            $ok1 = $this->query('ALTER TABLE `#TABLE1#`
                ADD `description` VARCHAR( 500 ) CHARACTER SET #CHARSET# COLLATE #COLLATE# NOT NULL DEFAULT "";'
            );

            $ok2 = $this->query('ALTER TABLE `#TABLE1#`
                ADD `filecode` blob NOT NULL DEFAULT "";'
            );

            if ($ok1 && $ok2){
                $this->updateNewColumns();
            }
        }
    }

    protected function columnExisistFilecode(){
        return $this->query('SELECT * FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_NAME = "#TABLE1#"
                AND TABLE_SCHEMA = "#DBNAME#"
                AND COLUMN_NAME = "filecode"'
        )->Fetch();
    }

    protected function updateNewColumns(){
        $vmanager = new VersionManager();

        $versions = $vmanager->getVersions('all');
        foreach ($versions as $version){

            if ($version['type'] != 'is_success'){
                continue;
            }

            $descr = $vmanager->getVersionDescription($version['version']);
            $descr = ($descr) ? $this->forSql($descr) : '';

            $filecode = $vmanager->getVersionFileCode($version['version']);
            $filecode = ($filecode) ? $this->forSql($filecode) : '';

            $this->query('UPDATE `#TABLE1#` SET `description`="%s", `filecode`="%s" WHERE `version`="%s"',
                $descr,
                $filecode,
                $version['version']
            );
        }
    }

}
