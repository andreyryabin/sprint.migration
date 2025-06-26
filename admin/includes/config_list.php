<?php
/** @var $versionConfig VersionConfig */

use Sprint\Migration\Locale;
use Sprint\Migration\VersionConfig;
use Sprint\Migration\ConfigManager;

?><?php foreach (ConfigManager::getInstance()->getList() as $configItem) { ?>
    <div class="sp-table">
        <div class="sp-row">
            <div class="sp-col sp-white">
                <h3><?= Locale::getMessage('CONFIG') ?>: <?= $configItem->getTitle() ?></h3>
                <table class="sp-config">
                    <?php foreach ($configItem->humanValues() as $key => $val) { ?>
                        <tr>
                            <td><?= Locale::getMessage('CONFIG_' . $key) ?></td>
                            <td><?= $key ?></td>
                            <td><?= nl2br($val) ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
    </div>
<?php } ?>
