<?php
/** @var $versionManager \Sprint\Migration\VersionManager */


$configItem = $versionManager->getConfigCurrent();
?>
<table class="sp-config">
    <thead>
    <tr>
        <td colspan="3">
            <strong><?= $configItem['title'] ?></strong>
        </td>
    </tr>
    </thead>
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
