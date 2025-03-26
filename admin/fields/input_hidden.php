<?php

use Sprint\Migration\AbstractBuilder;

/**
 * @var $fieldCode string
 * @var $fieldItem array
 * @var $builder   AbstractBuilder
 */

?>
<input type="hidden" name="<?= $fieldCode ?>" value="<?= htmlspecialchars($fieldItem['value']) ?>"/>
