<?php

use Sprint\Migration\Builder;

/**
 * @var $fieldCode string
 * @var $fieldItem array
 * @var $builder   Builder
 */
?>
<div class="sp-optgroup">
    <?php if (count($fieldItem['select']) > 8) { ?>
        <div class="sp-optgroup-head">
            <input class="sp-optgroup-search" data-attrs="<?= htmlspecialcharsbx($fieldCode) ?>" size="20" type="text" placeholder="Search"/>
        </div>
    <?php } ?>
    <div class="sp-optgroup-group">
        <?php foreach ($fieldItem['select'] as $item) { ?>
            <label class="sp-optgroup-item">
                <input name="<?= htmlspecialcharsbx($fieldCode) ?>"
                       value="<?= htmlspecialcharsbx($item['value']) ?>"
                    <?php if ($item['value'] == $fieldItem['value']) { ?>
                        checked="checked"
                    <?php } ?>
                       type="radio"
                ><?= htmlspecialcharsbx($item['title']) ?>
            </label>
        <?php } ?>
    </div>
</div>
