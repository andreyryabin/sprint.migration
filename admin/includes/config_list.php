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
                    <form method="post" action="">
                        <?=bitrix_sessid_post('send_sessid')?>
                        <strong><?= $configItem['title'] ?></strong> &nbsp;
                        <input name="change_config" type="hidden" value="<?= $configItem['name'] ?>">
                        <input type="submit" value="<?= GetMessage('SPRINT_MIGRATION_CONFIG_SWITCH') ?>">
                    </form>
                <? endif ?>
            </td>
        </tr>
        </thead>
        <tbody>
        <? foreach ($configItem['values'] as $key => $val) :

            if (strpos($key,'stop') === 0 || strpos($key,'show') === 0) {
                $val = ($val) ? 'yes' : 'no';
                $val = GetMessage('SPRINT_MIGRATION_CONFIG_'.$val);
            } elseif ($key == 'version_builders') {
                $val = array_keys($val);
                $val = implode('<br/>', $val);
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