<?php
/** @var $versionManager \Sprint\Migration\VersionManager */
$configValues = $versionManager->getVersionConfig()->getCurrent('values');
?>
<table class="sp-config">
    <tbody>
    <? foreach ($configValues as $key => $val) :

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