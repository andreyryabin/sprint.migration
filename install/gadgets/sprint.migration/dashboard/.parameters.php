<?

use Sprint\Migration\SchemaManager;
use Bitrix\Main\Loader;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

if (!Loader::includeModule('sprint.migration')) {
    return false;
}

/** @var $versionConfig VersionConfig */
$schemaManager = new SchemaManager($versionConfig);

$schemaClasses = ['' => 'Нет'];
foreach ($schemaManager->getEnabledSchemas() as $schema) {
    $schemaClasses[get_class($schema)] = $schema->getTitle();
}

$arParameters = [
    "USER_PARAMETERS" => [
        "SCHEMA_CLASS_TO_TRACK_LIST" => [
            "NAME"     => GetMessage("SCHEMA_CLASS_TO_TRACK_LIST"),
            "TYPE"     => "LIST",
            "SIZE"     => 10,
            "VALUES"   => $schemaClasses,
            "MULTIPLE" => "Y",
            "DEFAULT"  => [
                'Sprint\Migration\Schema\IblockSchema',
                'Sprint\Migration\Schema\HlblockSchema',
                'Sprint\Migration\Schema\UserTypeEntitiesSchema',
//                'Sprint\Migration\Schema\AgentSchema',
                'Sprint\Migration\Schema\GroupSchema',
//                'Sprint\Migration\Schema\OptionSchema',
                'Sprint\Migration\Schema\EventSchema',
            ],
        ]
    ]
];
