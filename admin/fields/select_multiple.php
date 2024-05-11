<?php

use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\Locale;

/**
 * @var $fieldCode string
 * @var $fieldItem array
 * @var $builder   AbstractBuilder
 */
?>
<div class="sp-optgroup">
    <div style="padding: 5px 0;">
        <input class="sp-optgroup-search" data-attrs="<?= $fieldCode ?>" size="20" type="text" placeholder="Search"/>
        <button class="adm-btn sp-optgroup-check"><?= Locale::getMessage('SELECT_ALL') ?></button>
    </div>
    <div class="sp-optgroup-group">
        <?php foreach ($fieldItem['select'] as $item) { ?>
            <label class="sp-optgroup-item">
                <input name="<?= $fieldCode ?>[]"
                       value="<?= $item['value'] ?>"
                    <?php if (in_array($item['value'], $fieldItem['value'])) { ?>
                        checked="checked"
                    <?php } ?>
                       type="checkbox"
                ><?= $item['title'] ?>
            </label>
        <?php } ?>
    </div>
</div>
