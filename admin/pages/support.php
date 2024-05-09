<?php

use Sprint\Migration\Locale;
use Sprint\Migration\Module;

global $APPLICATION;
$APPLICATION->SetTitle(Locale::getMessage('MENU_SUPPORT'));

$request = Bitrix\Main\Context::getCurrent()->getRequest();

if ($request->isPost() && check_bitrix_sessid()) {
    if ($request->getPost("confirm_support")) {
        Module::checkDbOption('confirm_support', true);
        LocalRedirect($request->getRequestUri());
    }

    if ($request->getPost("disable_support")) {
        Module::checkDbOption('confirm_support', false);
        LocalRedirect($request->getRequestUri());
    }
}

?><?php if (Module::isDbOptionChecked('confirm_support')) { ?>
    <?php echo file_get_contents(
        'https://andreyryabin.github.io/sprint_migration/support.html',
        false,
        stream_context_create(['http' => ['timeout' => 10]])
    ); ?>
<?php } else { ?>
    <div class="sp-support">
        <?= Locale::getMessage('PAGE_SUPPORT_DESC') ?>
    </div>
<?php } ?>
    <div class="sp-support">
        <form method="post" action="">
            <?= bitrix_sessid_post(); ?>
            <?php if (Module::isDbOptionChecked('confirm_support')) { ?>
                <input name="disable_support" type="submit" value="<?= Locale::getMessage('SUPPORT_DISABLE') ?>" class="adm-btn"/>
            <?php } else { ?>
                <input name="confirm_support" type="submit" value="<?= Locale::getMessage('SUPPORT_CONFIRM') ?>" class="adm-btn"/>
            <?php } ?>
        </form>
    </div>
<?php

include __DIR__ . '/../includes/errors.php';
//include __DIR__ . '/../includes/help.php';
include __DIR__ . '/../assets/support.php';
include __DIR__ . '/../assets/style.php';
