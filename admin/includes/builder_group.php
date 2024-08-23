<?php

use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;

/** @var $versionConfig VersionConfig */
$versionManager = new VersionManager($versionConfig);

$builderList = $versionConfig->getVal('version_builders', []);

$builderTree = [];
foreach ($builderList as $builderName => $builderClass) {
    $builder = $versionManager->createBuilder($builderName);
    if ($builder->isEnabled()) {
        $builderGroup = $builder->getGroup();

        if (!isset($builderTree[$builderGroup])) {
            $builderTree[$builderGroup] = [];
        }
        $builderTree[$builderGroup][] = [
            'NAME'  => $builder->getName(),
            'TITLE' => $builder->getTitle(),
        ];
    }
}

?>
<div class="sp-table">
    <div class="sp-row2">
        <div class="sp-col sp-col_builders">
            <?php foreach ($builderTree as $groupName => $groupItems) { ?>
                <div class="sp-builder_group">
                    <?=$groupName?>
                </div>
                <?php foreach ($groupItems as $item) { ?>
                    <div class="sp-builder_title" data-builder="<?= $item['NAME'] ?>"><?= $item['TITLE'] ?></div>
                <?php } ?>
            <?php } ?>
        </div>
        <div class="sp-col" style="position: relative">
            <div id="migration_builder" style="position: sticky;top: 10px"></div>
        </div>
    </div>
</div>
