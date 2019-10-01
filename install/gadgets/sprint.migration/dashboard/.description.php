<?

use Bitrix\Main\Loader;
use Sprint\Migration\Locale;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (!Loader::includeModule('sprint.migration')) {
    return false;
}

$arDescription = [
    'NAME' => Locale::getMessage('GD_INFO_NAME'),
    'DESCRIPTION' => Locale::getMessage('GD_INFO_DESC'),
    'ICON' => '',
    'TITLE_ICON_CLASS' => 'bx-gadgets-no-padding',
    'GROUP' => ['ID' => 'admin_settings'],
    'NOPARAMS' => 'N',
    'AI_ONLY' => true,
];