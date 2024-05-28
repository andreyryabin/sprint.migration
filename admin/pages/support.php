<?php
/** @global $APPLICATION CMain */

use Sprint\Migration\Locale;

global $APPLICATION;
$APPLICATION->SetTitle(Locale::getMessage('MENU_SUPPORT'));
$ts = date('YmdH');

?>
<div id="support_page"></div>

<link href="https://andreyryabin.github.io/sprint_migration/support.css?<?= $ts ?>" rel="stylesheet" type="text/css">
<script src="https://andreyryabin.github.io/sprint_migration/support.js?<?= $ts ?>"></script>

<?php
include __DIR__ . '/../includes/help.php';
include __DIR__ . '/../assets/style.php';
?>
