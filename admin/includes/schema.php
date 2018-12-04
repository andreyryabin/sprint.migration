<?php
/** @var $versionManager \Sprint\Migration\VersionManager */

$schemaManager = new \Sprint\Migration\SchemaManager();

?>
<div id="migration-container">
    <div class="sp-group">
        <div class="sp-group-row2">
            <div class="sp-block sp-block-scroll sp-white">
                <div id="migration_schema_list" class="sp-scroll">

                    <?

                    $schemaManager->export()

                    ?>


                </div>
            </div>
            <div class="sp-block sp-block-scroll">
                <div id="migration_schema_progress" class="sp-scroll"></div>
            </div>
        </div>
    </div>
</div>

<? include __DIR__ . '/../assets/schema.php'; ?>
