<?php
/** @var $builderGroup */

use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;

?>
<div class="sp-group"><?php
    /** @var $versionConfig VersionConfig */
    $versionManager = new VersionManager($versionConfig);

    $colIndex = 0;
    $builders = $versionManager->createBuilders(['group' => $builderGroup]); ?>
    <?php foreach ($builders as $bIndex => $builder): ?>
        <?php if ($colIndex == 0): ?>
            <div class="sp-group-row2">
        <?php endif; ?>
        <div class="sp-block">
            <div class="sp-block_title">
                <?= $builder->getTitle() ?>
            </div>
            <div class="sp-block_body" data-builder="<?= $builder->getName() ?>">
                <?php $builder->renderHtml() ?>
            </div>
        </div>

        <?php if ($colIndex == 0 && empty($builders[$bIndex + 1])):$colIndex = 1 ?>
            <div class="sp-block"></div>
        <?php endif; ?>

        <?php if ($colIndex == 1): $colIndex = 0; ?>
            </div>
        <?php else: $colIndex++; ?>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
