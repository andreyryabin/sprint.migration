<?php

use Sprint\Migration\Locale;
use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;

/** @var $versionConfig VersionConfig */
$versionManager = new VersionManager($versionConfig);

$builderList = $versionConfig->getVal('version_builders', []);

$builderTree = [];
foreach ($builderList as $builderName => $builderClass) {
    $builder = $versionManager->createBuilder($builderName);
    if ($builder) {
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
<div class="sp-group">
    <div class="sp-group-row2">
        <div class="sp-block sp-block_builders">
            <?php foreach ($builderTree as $groupName => $groupItems) { ?>
                <div class="sp-builder_group">
                    <?= Locale::getMessage('BUILDER_GROUP_' . $groupName) ?>
                </div>
                <?php foreach ($groupItems as $item) { ?>
                    <div class="sp-builder_title" data-builder="<?= $item['NAME'] ?>"><?= $item['TITLE'] ?></div>
                <?php } ?>
            <?php } ?>
        </div>
        <div class="sp-block" style="position: relative">
            <div class="sp-builder_body" style="position: sticky;top: 10px"></div>
        </div>
    </div>
</div>
