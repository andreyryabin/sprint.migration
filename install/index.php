<?php

require_once __DIR__ . '/../classes/Sprint/Migration/Env.php';

Class sprint_migration extends CModule
{
    var $MODULE_ID = "sprint.migration";

    var $MODULE_NAME;
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_DESCRIPTION;
    var $PARTNER_NAME;
    var $PARTNER_URI;

    var $MODULE_GROUP_RIGHTS = "Y";

    function sprint_migration() {
        $arModuleVersion = array();

        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include($path . "/version.php");

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

        \Sprint\Migration\Env::includeLangFile();

        $this->MODULE_NAME = GetMessage("SPRINT_MIGRATION_MODULE_NAME");
        $this->MODULE_DESCRIPTION = GetMessage("SPRINT_MIGRATION_MODULE_DESCRIPTION");
        $this->PARTNER_NAME = GetMessage("SPRINT_MIGRATION_PARTNER_NAME");
        $this->PARTNER_URI = GetMessage("SPRINT_MIGRATION_PARTNER_URI");
    }

    function DoInstall() {
        RegisterModule($this->MODULE_ID);

        if (is_dir($_SERVER["DOCUMENT_ROOT"] . "/local/modules/sprint.migration/install/admin")) {
            CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/local/modules/sprint.migration/install/admin", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin");
        } else {
            CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/sprint.migration/install/admin", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin");
        }
    }

    function DoUninstall() {
        //launch upgrade when reinstalled module
        \Sprint\Migration\Env::setDbOption('upgrade_version', 'unknown');

        if (is_dir($_SERVER["DOCUMENT_ROOT"] . "/local/modules/sprint.migration/install/admin")) {
            DeleteDirFiles($_SERVER["DOCUMENT_ROOT"] . "/local/modules/sprint.migration/install/admin", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin");
        } else {
            DeleteDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/sprint.migration/install/admin", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin");
        }
        UnRegisterModule($this->MODULE_ID);
    }

}
