<?php
/** @var $versionManager \Sprint\Migration\VersionManager */


$configItem = $versionManager->getVersionConfig()->getCurrent();
?>
<div class="sp-block" style="margin-top: 10px;">
<div class="sp-block_head"><?= GetMessage('SPRINT_MIGRATION_CONFIG') ?>: <?=$configItem['title']?></div>

<table class="sp-config">
    <tbody>
    <? foreach ($configItem['values'] as $key => $val) :

        if ($val === true || $val === false) {
            $val = ($val) ? 'yes' : 'no';
            $val = GetMessage('SPRINT_MIGRATION_CONFIG_' . $val);
        } elseif (is_array($val)) {
            $fres = [];
            foreach ($val as $fkey => $fval) {
                $fres[] = '[' . $fkey . '] => ' . $fval;
            }
            $val = implode('<br/>', $fres);
        }

        ?>
        <tr>
            <td><?= GetMessage('SPRINT_MIGRATION_CONFIG_' . $key) ?></td>
            <td><?= $key ?></td>
            <td><?= $val ?></td>
        </tr>
    <? endforeach; ?>
    </tbody>
</table>
</div>