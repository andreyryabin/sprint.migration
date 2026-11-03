<?php

use Sprint\Migration\ConfigManager;
use Sprint\Migration\VersionConfig;

/** @var VersionConfig $versionConfig */


CJSCore::RegisterExt('sprint_migration_app', include __DIR__ . '/config.php');

CJSCore::Init(['sprint_migration_app']);

$otherConfigs = [];
foreach (ConfigManager::getInstance()->getList() as $configItem) {
    if ($configItem->getName() != $versionConfig->getName()) {
        $otherConfigs[] = ['name' => $configItem->getName(), 'title' => $configItem->getTitle()];
    }
}

$props = htmlspecialchars(json_encode([
    'config'       => $versionConfig->getName(),
    'webdir'       => $versionConfig->getWebDir(),
    'lang'         => LANGUAGE_ID,
    'site'         => SITE_ID,
    'otherConfigs' => $otherConfigs,
], JSON_UNESCAPED_UNICODE));


echo '<div id="sprint_migration_app" data-props="' . $props . '">intialize ..</div>';

echo '<script type="text/javascript">';
include __DIR__ . '/dist/index.bundle.js';
echo '</script>';
