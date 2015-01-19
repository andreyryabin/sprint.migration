<?php

if (is_file($_SERVER["DOCUMENT_ROOT"] . "/local/modules/sprint.migration/admin/sprint_migrations.php")) {
    require($_SERVER["DOCUMENT_ROOT"] . "/local/modules/sprint.migration/admin/sprint_migrations.php");
} else {
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/sprint.migration/admin/sprint_migrations.php");
}
