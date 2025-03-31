<?php

use Sprint\Migration\Builder;

/**
 * @var $fieldCode string
 * @var $fieldItem array
 * @var $builder   Builder
 */
?>
<input name="<?= $fieldCode ?>"
       type="text"
       value="<?= htmlspecialchars($fieldItem['value']) ?>"
    <?php if (!empty($fieldItem['placeholder'])) { ?>
        placeholder="<?= $fieldItem['placeholder'] ?>"
    <?php } ?>
    <?php if (!empty($fieldItem['width'])) { ?>
        style="width: <?= $fieldItem['width'] ?>px;"
    <?php } ?>
/>
