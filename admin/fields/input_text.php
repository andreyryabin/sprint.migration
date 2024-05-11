<?php

use Sprint\Migration\AbstractBuilder;

/**
 * @var $fieldCode string
 * @var $fieldItem array
 * @var $builder   AbstractBuilder
 */
?>
<input name="<?= $fieldCode ?>"
       type="text"
       value="<?= $fieldItem['value'] ?>"
    <?php if (!empty($fieldItem['placeholder'])) { ?>
        placeholder="<?= $fieldItem['placeholder'] ?>"
    <?php } ?>
    <?php if (!empty($fieldItem['width'])) { ?>
        style="width: <?= $fieldItem['width'] ?>px;"
    <?php } ?>
/>
