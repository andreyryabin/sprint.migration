<?php

namespace Sprint\Migration;

class Db
{

    protected static $versionsTable = 'sprint_migration_versions';

    public static function install() {
        self::createDefaultTables();
    }

    /**
     * @return bool|\CDBResult
     */
    public static function getRecords() {
        if (Env::isMssql()) {
            return self::query('SELECT * FROM %s', self::$versionsTable);
        } else {
            return self::query('SELECT * FROM `%s`', self::$versionsTable);
        }
    }

    /**
     * @param $versionName
     * @return bool|\CDBResult
     */
    public static function getRecordByName($versionName) {
        $versionName = self::getDb()->ForSql($versionName);

        if (Env::isMssql()) {
            return self::query('SELECT * FROM %s WHERE version = \'%s\'',
                self::$versionsTable, $versionName
            );

        } else {
            return self::query('SELECT * FROM `%s` WHERE `version` = "%s"',
                self::$versionsTable,
                $versionName
            );
        }
    }

    /**
     * @param $versionName
     * @return bool|\CDBResult
     */
    public static function addRecord($versionName) {
        $versionName = self::getDb()->ForSql($versionName);

        if (Env::isMssql()) {
            return self::query('if not exists(select version from %s where version=\'%s\')
                    begin
                        INSERT INTO %s (version) VALUES (\'%s\')
                    end',
                self::$versionsTable,
                $versionName,
                self::$versionsTable,
                $versionName
            );

        } else {
            return self::query('INSERT IGNORE INTO `%s` (`version`) VALUES ("%s")',
                self::$versionsTable,
                $versionName
            );
        }

    }

    /**
     * @param $versionName
     * @return bool|\CDBResult
     */
    public static function removeRecord($versionName) {
        $versionName = self::getDb()->ForSql($versionName);

        if (Env::isMssql()) {
            return self::query('DELETE FROM %s WHERE version = \'%s\'',
                self::$versionsTable,
                $versionName
            );
        } else {
            return self::query('DELETE FROM `%s` WHERE `version` = "%s"',
                self::$versionsTable,
                $versionName
            );
        }

    }

    protected static function createDefaultTables() {
        if (Env::isMssql()) {
            self::query('if not exists (SELECT * FROM sysobjects WHERE name=\'%s\' AND xtype=\'U\')
                begin
                    CREATE TABLE %s
                    (id int IDENTITY (1,1) NOT NULL,
                    version varchar(255) NOT NULL,
                    PRIMARY KEY (id),
                    UNIQUE (version))
                end',
                self::$versionsTable,
                self::$versionsTable
            );

        } else {

            self::query('CREATE TABLE IF NOT EXISTS `%s`(
              `id` MEDIUMINT NOT NULL AUTO_INCREMENT NOT NULL,
              `version` varchar(255) COLLATE %s NOT NULL,
              PRIMARY KEY (id), UNIQUE KEY(version)
              )ENGINE=InnoDB DEFAULT CHARSET=%s COLLATE=%s AUTO_INCREMENT=1;',
                self::$versionsTable,
                self::getCollate(),
                self::getCharset(),
                self::getCollate()
            );

        }
    }

    protected static function xxx() {
        //ALTER TABLE `sprint_migration_versions` ADD `description` TEXT CHARACTER SET cp1251 COLLATE cp1251_general_ci NOT NULL DEFAULT '';
        //ALTER TABLE `sprint_migration_versions` ADD `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
    }



    protected static function getCharset() {
        return Env::isWin1251() ? 'cp1251' : 'utf8';
    }

    protected static function getCollate() {
        return Env::isWin1251() ? 'cp1251_general_ci' : 'utf8_general_ci';
    }

    /**
     * @param $query
     * @param null $var1
     * @param null $var2
     * @return bool|\CDBResult
     */
    protected static function query($query, $var1 = null, $var2 = null) {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $query = call_user_func_array('sprintf', $params);
        }
        return Env::getDb()->Query($query);
    }

}
