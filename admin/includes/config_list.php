<?php
/** @var $versionManager \Sprint\Migration\VersionManager */

$configList = $versionManager->getConfigList();
$configName = $versionManager->getConfigName();
?><?php foreach ($configList as $configItem) : ?>
    <table class="sp-config">
        <thead>
        <tr>
            <td colspan="3">
                <? if ($configItem['name'] == $configName): ?>
                    <strong><?= $configItem['title'] ?> *</strong>
                <? else: ?>
                    <form method="get" action="">
                        <strong><?= $configItem['title'] ?></strong> &nbsp;
                        <input name="config" type="hidden" value="<?= $configItem['name'] ?>">
                        <input name="lang" type="hidden" value="<?= LANGUAGE_ID ?>">
                        <input type="submit" value="<?= GetMessage('SPRINT_MIGRATION_CONFIG_SWITCH') ?>">
                    </form>
                <? endif ?>
            </td>
        </tr>
        </thead>
        <tbody>
        <? foreach ($configItem['values'] as $key => $val) :

            if ($key == 'version_builders'){
                $val = array_keys($val);
                $val = implode('<br/>', $val);
            } elseif ($key == 'show_other_solutions') {
                $val = ($val) ? 'yes' : 'no';
                $val = GetMessage('SPRINT_MIGRATION_CONFIG_'.$val);
            } elseif ($key == 'stop_on_errors') {
                $val = ($val) ? 'yes' : 'no';
                $val = GetMessage('SPRINT_MIGRATION_CONFIG_'.$val);
            }

            ?><tr>
                <td><?= GetMessage('SPRINT_MIGRATION_CONFIG_' . $key) ?></td>
                <td><?= $key ?></td>
                <td><?= $val ?></td>
            </tr>
            <?endforeach; ?>
        </tbody>
    </table>
<? endforeach; ?>