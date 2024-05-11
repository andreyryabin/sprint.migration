<?php

use Sprint\Migration\AbstractBuilder;

/**
 * @var $fieldCode string
 * @var $fieldItem array
 * @var $builder   AbstractBuilder
 */

$style = 'style="height: ' . $fieldItem['height'] . 'px;"';
if (!empty($fieldItem['width'])) {
    $style = 'style="width: ' . $fieldItem['width'] . 'px; height: ' . $fieldItem['height'] . 'px;"';
}

?><textarea name="<?= $fieldCode ?>" <?= $style ?>><?= $fieldItem['value'] ?></textarea>
