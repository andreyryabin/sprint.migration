<?php

use Sprint\Migration\AbstractBuilder;

/**
 * @var $fieldCode string
 * @var $fieldItem array
 * @var $builder   AbstractBuilder
 */
?>
<div class="sp-optgroup">
    <?php if (count($fieldItem['select']) > 8) { ?>
        <div class="sp-optgroup-head">
            <input class="sp-optgroup-search" data-attrs="<?= $fieldCode ?>" size="20" type="text" placeholder="Search"/>
        </div>
    <?php } ?>
    <div class="sp-optgroup-group">
        <?php foreach ($fieldItem['select'] as $item) { ?>
            <label class="sp-optgroup-item">
                <input name="<?= $fieldCode ?>"
                       value="<?= $item['value'] ?>"
                    <?php if ($item['value'] == $fieldItem['value']) { ?>
                        checked="checked"
                    <?php } ?>
                       type="radio"
                ><?= $item['title'] ?>
            </label>
        <?php } ?>
    </div>
</div>
