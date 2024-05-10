<?php
/** @var $versionConfig VersionConfig */

use Sprint\Migration\Locale;
use Sprint\Migration\VersionConfig;

$versionConfig = new VersionConfig();
$configList = $versionConfig->getList();

?>

<?php foreach ($configList as $configItem) { ?><?php

    $configValues = $versionConfig->humanValues($configItem['values']);

    ?>
    <div class="sp-table">
        <div class="sp-row">
            <div class="sp-col sp-white">
                <h3><?= Locale::getMessage('CONFIG') ?>: <?= $configItem['title'] ?></h3>
                <table class="sp-config">
                    <?php foreach ($configValues as $key => $val) { ?>
                        <tr>
                            <td><?= Locale::getMessage('CONFIG_' . $key) ?></td>
                            <td><?= $key ?></td>
                            <td><?= nl2br($val) ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
    </div>
<?php } ?>
