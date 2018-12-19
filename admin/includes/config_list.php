<?php
/** @var $versionConfig \Sprint\Migration\VersionConfig */

$versionConfig = new \Sprint\Migration\VersionConfig();
$configList = $versionConfig->getList();

?>

<? foreach ($configList as $configItem): ?><?

    $configValues = $versionConfig->humanValues($configItem['values']);

    ?>
    <div class="sp-group">
        <div class="sp-group-row">
            <div class="sp-block sp-white">
                <h3><?= GetMessage('SPRINT_MIGRATION_CONFIG') ?>: <?= $configItem['title'] ?></h3>
                <table class="sp-config">
                    <? foreach ($configValues as $key => $val) : ?>
                        <tr>
                            <td><?= GetMessage('SPRINT_MIGRATION_CONFIG_' . $key) ?></td>
                            <td><?= $key ?></td>
                            <td><?= nl2br($val) ?></td>
                        </tr>
                    <? endforeach; ?>
                </table>
            </div>
        </div>
    </div>
<? endforeach; ?>