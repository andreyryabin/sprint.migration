<?php

use Sprint\Migration\Builder;
use Sprint\Migration\Locale;

/**
 * @var $fieldCode string
 * @var $fieldItem array
 * @var $builder   Builder
 */
?>
<div class="sp-optgroup">
    <div class="sp-optgroup-head">
        <input class="sp-optgroup-search" data-attrs="<?= $fieldCode ?>" size="20" type="text" placeholder="Search"/>
    </div>
    <?php foreach ($fieldItem['items'] as $group) { ?>
        <?php if (!empty($group['items'])) { ?>
            <div class="sp-optgroup-group">
                <?php if (!empty($group['title'])) { ?>
                    <div class="sp-optgroup-title"><?= $group['title'] ?></div>
                <?php } ?>
                <?php foreach ($group['items'] as $item) { ?>
                    <label class="sp-optgroup-item">
                        <input name="<?= $fieldCode ?>"
                               value="<?= htmlspecialchars($item['value']) ?>"
                            <?php if ($item['value'] == $fieldItem['value']) { ?>
                                checked="checked"
                            <?php } ?>
                               type="radio"
                        ><?= $item['title'] ?>
                    </label>
                <?php } ?>
            </div>
        <?php } ?>
    <?php } ?>
</div>
