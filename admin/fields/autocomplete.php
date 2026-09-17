<?php

use Sprint\Migration\Builder;
use Sprint\Migration\Locale;

/**
 * @var $fieldCode string
 * @var $fieldItem array
 * @var $builder   Builder
 */

?>
<div class="sp-autocomplete">
    <input name="<?= htmlspecialcharsbx($fieldCode) ?>"
           type="text"
           value="<?= htmlspecialcharsbx($fieldItem['value']) ?>"
           autocomplete="off"
        <?php if (!empty($fieldItem['placeholder'])) { ?>
            placeholder="<?= htmlspecialcharsbx($fieldItem['placeholder']) ?>"
        <?php } ?>
        <?php if (!empty($fieldItem['width'])) { ?>
            style="width: <?= htmlspecialcharsbx($fieldItem['width']) ?>px;"
        <?php } ?>
           data-autocomplete-source="<?= htmlspecialcharsbx($fieldItem['source']) ?>"
           data-autocomplete-selected-value="<?= htmlspecialcharsbx($fieldItem['value']) ?>"
           data-autocomplete-message="<?= htmlspecialcharsbx($fieldItem['message'] ?? Locale::getMessage('AUTOCOMPLETE_SELECT_FROM_LIST')) ?>"
    />
    <div class="sp-autocomplete-items"></div>
    <div class="sp-autocomplete-message"></div>
</div>
