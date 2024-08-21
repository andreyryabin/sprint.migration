<?php
use Sprint\Migration\Locale;
?>
<input placeholder="<?= Locale::getMessage('SEARCH') ?>"
       style=""
       type="text"
       value=""
       class="adm-input"
       id="migration_search"/>
<select id="migration_view">
    <option value="migration_view_actual"><?= Locale::getMessage('TOGGLE_ACTUAL') ?></option>
    <option value="migration_view_all"><?= Locale::getMessage('TOGGLE_LIST') ?></option>
    <option value="migration_view_new"><?= Locale::getMessage('TOGGLE_NEW') ?></option>
    <option value="migration_view_installed"><?= Locale::getMessage('TOGGLE_INSTALLED') ?></option>
    <option value="migration_view_unknown"><?= Locale::getMessage('TOGGLE_UNKNOWN') ?></option>
    <option value="migration_view_tag"><?= Locale::getMessage('TOGGLE_TAG') ?></option>
    <option value="migration_view_modified"><?= Locale::getMessage('TOGGLE_MODIFIED') ?></option>
    <option value="migration_view_older"><?= Locale::getMessage('TOGGLE_OLDER') ?></option>
    <option value="migration_view_status"><?= Locale::getMessage('TOGGLE_STATUS') ?></option>
</select>
<input id="migration_refresh" type="button" value="<?= Locale::getMessage('SEARCH') ?>"/>
