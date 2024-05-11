<?php

use Sprint\Migration\AbstractBuilder;

/**
 * @var $fieldCode string
 * @var $fieldItem array
 * @var $builder   AbstractBuilder
 */
?>
<select name="<?= $fieldCode ?>"
    <?php if (!empty($fieldItem['width'])) { ?>
        style="width: <?= $fieldItem['width'] ?>px;"
    <?php } ?>
><?php foreach ($fieldItem['select'] as $item) { ?>
        <option value="<?= $item['value'] ?>"
            <?php if ($fieldItem['value'] == $item['value']) { ?>
                selected="selected"
            <?php } ?>
        ><?= $item['title'] ?></option>
    <?php } ?>
</select>
