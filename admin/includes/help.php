<?php
global $APPLICATION;
$isSettinsPage = strpos($APPLICATION->GetCurPage(), 'settings.php');
?>
<div class="sp-group">
    <div class="sp-group-row2">
        <div class="sp-block">
            <? if ($isSettinsPage): ?>
                <a href="/bitrix/admin/sprint_migrations.php?config=cfg&lang=<?= LANGUAGE_ID ?>"><?= GetMessage('SPRINT_MIGRATION_GOTO_MIGRATION') ?></a>
            <? else: ?>
                <a href="/bitrix/admin/settings.php?mid=sprint.migration&mid_menu=1&lang=<?= LANGUAGE_ID ?>"><?= GetMessage('SPRINT_MIGRATION_GOTO_OPTIONS') ?></a>
            <? endif; ?>
        </div>
        <div class="sp-block">
            <?= GetMessage('SPRINT_MIGRATION_HELP_DOC') ?>:
            <a href="https://github.com/andreyryabin/sprint.migration" target="_blank">https://github.com/andreyryabin/sprint.migration</a>
        </div>
    </div>
</div>