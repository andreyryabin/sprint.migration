<?php

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

        include(__DIR__ . "/version.php");

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

        include(__DIR__ .'/../loader.php');
        include(__DIR__ .'/../locale/ru.php');

        $this->MODULE_NAME = GetMessage("SPRINT_MIGRATION_MODULE_NAME");
        $this->MODULE_DESCRIPTION = GetMessage("SPRINT_MIGRATION_MODULE_DESCRIPTION");
        $this->PARTNER_NAME = GetMessage("SPRINT_MIGRATION_PARTNER_NAME");
        $this->PARTNER_URI = GetMessage("SPRINT_MIGRATION_PARTNER_URI");
    }

    function DoInstall() {
        RegisterModule($this->MODULE_ID);
        CopyDirFiles(__DIR__ . "/admin", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin");
    }

    function DoUninstall() {
        //launch upgrade when reinstalled module
        DeleteDirFiles(__DIR__ . "/admin", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin");
        UnRegisterModule($this->MODULE_ID);
    }

    function GetModuleRightList(){
        $arr = array(
            "reference_id" => array("D","W"),
            "reference" => array(
                "[D] ".GetMessage("SPRINT_MIGRATION_RIGHT_D"),
                "[W] ".GetMessage("SPRINT_MIGRATION_RIGHT_W"))
        );
        return $arr;
    }
}
