<?php
/** @var $versionConfig VersionConfig */

use Sprint\Migration\Locale;
use Sprint\Migration\VersionConfig;

$versionConfig = new VersionConfig();
$configList = $versionConfig->getList();

?>

<? foreach ($configList as $configItem): ?><?

    $configValues = $versionConfig->humanValues($configItem['values']);

    ?>
    <div class="sp-group">
        <div class="sp-group-row">
            <div class="sp-block sp-white">
                <h3><?= Locale::getMessage('CONFIG') ?>: <?= $configItem['title'] ?></h3>
                <table class="sp-config">
                    <? foreach ($configValues as $key => $val) : ?>
                        <tr>
                            <td><?= Locale::getMessage('CONFIG_' . $key) ?></td>
                            <td><?= $key ?></td>
                            <td><?= nl2br($val) ?></td>
                        </tr>
                    <? endforeach; ?>
                </table>
            </div>
        </div>
    </div>
<? endforeach; ?>