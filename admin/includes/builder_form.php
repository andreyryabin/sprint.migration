<?php
/** @var $builder AbstractBuilder */

use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\Locale;
use Sprint\Migration\Out;

echo '<form method="post">';
foreach ($builder->getFields() as $fieldCode => $fieldItem) {
    if ($fieldItem['type'] == 'hidden') {
        include __DIR__ . '/../fields/input_hidden.php';
        continue;
    }

    echo '<div class="sp-field">';

    echo !empty($fieldItem['title']) ? '<div class="sp-field-title">' . $fieldItem['title'] . '</div>' : '';
    echo !empty($fieldItem['note']) ? '<div class="sp-field-note">' . $fieldItem['note'] . '</div>' : '';

    if (!empty($fieldItem['height'])) {
        include __DIR__ . '/../fields/textarea.php';
    } elseif (isset($fieldItem['select']) && !$fieldItem['multiple']) {
        include __DIR__ . '/../fields/select.php';
    } elseif (isset($fieldItem['select']) && $fieldItem['multiple']) {
        include __DIR__ . '/../fields/select_multiple.php';
    } elseif (isset($fieldItem['items']) && !$fieldItem['multiple']) {
        include __DIR__ . '/../fields/select_groups.php';
    } elseif (isset($fieldItem['items']) && $fieldItem['multiple']) {
        include __DIR__ . '/../fields/select_groups_multiple.php';
    } else {
        include __DIR__ . '/../fields/input_text.php';
    }
    echo '</div>';
}

echo '<div class="sp-field">' .
     '<input type="submit" value="' . Locale::getMessage('BUILDER_NEXT') . '"/>' .
     '<input type="reset" value="' . Locale::getMessage('BUILDER_RESET') . '"/>' .
     '</div>';

if ($builder->hasDescription()) {
    echo '<div class="sp-field sp-info-message">';
    Out::out($builder->getDescription());
    echo '</div>';
}

echo '</form>';
