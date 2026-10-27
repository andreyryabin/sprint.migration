<?php

use Sprint\Migration\Builder;

/**
 * @var $fieldCode string
 * @var $fieldItem array
 * @var $builder   Builder
 */
?>
<input name="<?= htmlspecialcharsbx($fieldCode) ?>"
       type="text"
       value="<?= htmlspecialcharsbx($fieldItem['value']) ?>"
    <?php if (!empty($fieldItem['placeholder'])) { ?>
        placeholder="<?= htmlspecialcharsbx($fieldItem['placeholder']) ?>"
    <?php } ?>
    <?php if (!empty($fieldItem['width'])) { ?>
        style="width: <?= htmlspecialcharsbx($fieldItem['width']) ?>px;"
    <?php } ?>
/>
