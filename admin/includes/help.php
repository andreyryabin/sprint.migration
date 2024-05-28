<?php

use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;

/**
 * @var string $showpage
 */

global $APPLICATION;
$isSettinsPage = strpos($APPLICATION->GetCurPage(), 'settings.php');

?>
<div class="sp-table <?= $showpage ? 'sp-table-' . $showpage : '' ?>">
    <div class="sp-row2">
        <div class="sp-col">
            <div style="margin-bottom: 10px;">
                <?= Locale::getMessage('MODULE_VERSION') ?>: <?= Module::getVersion() ?>
            </div>
            <div style="margin-bottom: 10px;">
                <?php if ($isSettinsPage): ?>
                    <a href="/bitrix/admin/sprint_migrations.php?config=<?= VersionEnum::CONFIG_DEFAULT ?>&lang=<?= LANGUAGE_ID ?>"><?= Locale::getMessage('GOTO_MIGRATION') ?></a>
                <?php else: ?>
                    <a href="/bitrix/admin/settings.php?mid=<?= Module::ID ?>&mid_menu=1&lang=<?= LANGUAGE_ID ?>"><?= Locale::getMessage('GOTO_OPTIONS') ?></a>
                <?php endif; ?>
            </div>
        </div>
        <div class="sp-col">
            <div style="margin-bottom: 10px;">
                <?= Locale::getMessage('LINK_MP') ?> <br/>
                <a href="http://marketplace.1c-bitrix.ru/solutions/sprint.migration/" target="_blank">http://marketplace.1c-bitrix.ru/solutions/sprint.migration/</a>
            </div>
            <div style="margin-bottom: 10px;">
                <?= Locale::getMessage('LINK_COMPOSER') ?>
                <br/>
                <a href="https://packagist.org/packages/andreyryabin/sprint.migration" target="_blank">https://packagist.org/packages/andreyryabin/sprint.migration</a>
            </div>
            <div style="margin-bottom: 10px;">
                <?= Locale::getMessage('LINK_DOC') ?>
                <br/>
                <a href="https://github.com/andreyryabin/sprint.migration/wiki" target="_blank">https://github.com/andreyryabin/sprint.migration/wiki</a>
            </div>
            <div style="margin-bottom: 10px;">
                <?= Locale::getMessage('LINK_ARTICLES') ?>
                <br/>
                <a href="https://dev.1c-bitrix.ru/community/webdev/user/39653/blog/" target="_blank">https://dev.1c-bitrix.ru/community/webdev/user/39653/blog/</a>
            </div>
            <div style="margin-bottom: 10px;">
                <?= Locale::getMessage('LINK_TELEGRAM') ?>
                <br/>
                <a href="https://t.me/sprint_migration_bitrix">https://t.me/sprint_migration_bitrix</a>
            </div>
        </div>
    </div>
</div>
