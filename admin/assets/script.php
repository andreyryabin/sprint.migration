<?php

use Sprint\Migration\VersionConfig;

/** @var VersionConfig $versionConfig */


CJSCore::RegisterExt('sprint_migration_app', include __DIR__ . '/config.php');

CJSCore::Init(['sprint_migration_app']);

?>
<div id="sprint_migration_app" data-config="<?= $versionConfig->getName() ?>">
    intialize ...
</div>
<script type="text/javascript">
    <?php include __DIR__ . '/dist/index.bundle.js' ?>
</script>
