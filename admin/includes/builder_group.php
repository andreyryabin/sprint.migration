<?
/** @var $builderGroup */

use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;

?>
<div class="sp-group"><?
    /** @var $versionConfig VersionConfig */
    $versionManager = new VersionManager($versionConfig);

    $colIndex = 0;
    $builders = $versionManager->createBuilders(['group' => $builderGroup]); ?>
    <? foreach ($builders as $bIndex => $builder): ?>
        <? if ($colIndex == 0): ?>
            <div class="sp-group-row2">
        <? endif; ?>
        <div class="sp-block">
            <div class="sp-block_title">
                <?= $builder->getTitle() ?>
            </div>
            <div class="sp-block_body" data-builder="<?= $builder->getName() ?>">
                <? $builder->renderHtml() ?>
            </div>
        </div>

        <? if ($colIndex == 0 && empty($builders[$bIndex + 1])):$colIndex = 1 ?>
            <div class="sp-block"></div>
        <? endif; ?>

        <? if ($colIndex == 1): $colIndex = 0; ?>
            </div>
        <? else: $colIndex++; ?>
        <? endif; ?>
    <? endforeach; ?>
</div>