<?php

if (is_file($_SERVER["DOCUMENT_ROOT"] . "/local/modules/sprint.migration/admin/sprint_migrations.php")) {
    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/local/modules/sprint.migration/admin/sprint_migrations.php");
} else {
    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/sprint.migration/admin/sprint_migrations.php");
}
