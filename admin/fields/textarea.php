<?php

use Sprint\Migration\Builder;

/**
 * @var $fieldCode string
 * @var $fieldItem array
 * @var $builder   Builder
 */

$style = 'style="height: ' . $fieldItem['height'] . 'px;"';
if (!empty($fieldItem['width'])) {
    $style = 'style="width: ' . $fieldItem['width'] . 'px; height: ' . $fieldItem['height'] . 'px;"';
}

?><textarea name="<?= $fieldCode ?>" <?= $style ?>><?= $fieldItem['value'] ?></textarea>
