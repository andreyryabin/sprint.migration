<?php

namespace Sprint\Migration;

class Db
{


    /* @return \CDatabase */
    public static function getDb() {
        return $GLOBALS['DB'];
    }

    public static function isMssql() {
        return ($GLOBALS['DBType'] == 'mssql');
    }

    /* @return \CDBResult */
    public static function findAll(){
        if (self::isMssql()) {
            return self::getDb()->Query('SELECT * FROM sprint_migration_versions');
        } else {
            return self::getDb()->Query('SELECT * FROM `sprint_migration_versions`');
        }
    }

    /* @return \CDBResult */
    public static function findByName($versionName){
        if (self::isMssql()) {
            return self::getDb()->Query(sprintf('SELECT * FROM sprint_migration_versions WHERE version = \'%s\'', $versionName));
        } else {
            return self::getDb()->Query(sprintf('SELECT * FROM `sprint_migration_versions` WHERE `version` = "%s"', $versionName));
        }
    }

    /* @return \CDBResult */
    public static function addRecord($versionName){
        if (self::isMssql()) {
            return self::getDb()->Query(sprintf('if not exists(select version from sprint_migration_versions where version=\'%s\')
                    begin
                        INSERT INTO sprint_migration_versions (version) VALUES	(\'%s\')
                    end', $versionName, $versionName));

        } else {
            return self::getDb()->Query(sprintf('INSERT IGNORE INTO `sprint_migration_versions` SET `version` = "%s"', $versionName));
        }

    }

    /* @return \CDBResult */
    public static function removeRecord($versionName){
        if (self::isMssql()) {
            return self::getDb()->Query(sprintf('DELETE FROM sprint_migration_versions WHERE version = \'%s\'', $versionName));
        } else {
            return self::getDb()->Query(sprintf('DELETE FROM `sprint_migration_versions` WHERE `version` = "%s"', $versionName));
        }

    }


    public static function createTablesIfNotExists() {
        if (self::isMssql()) {
            self::getDb()->Query('if not exists (SELECT * FROM sysobjects WHERE name=\'sprint_migration_versions\' AND xtype=\'U\')
                begin
                    CREATE TABLE sprint_migration_versions
                    (id int IDENTITY (1,1) NOT NULL,
                    version varchar(255) NOT NULL,
                    PRIMARY KEY (id),
                    UNIQUE (version))
                end');

        } else {
            self::getDb()->Query('CREATE TABLE IF NOT EXISTS `sprint_migration_versions`(
			  `id` MEDIUMINT NOT NULL AUTO_INCREMENT NOT NULL,
			  `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			  PRIMARY KEY (id), UNIQUE KEY(version)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;');
        }
    }
}



