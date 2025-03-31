<?php

use Sprint\Migration\Builder;

/**
 * @var $fieldCode string
 * @var $fieldItem array
 * @var $builder   Builder
 */

?>
<input type="hidden" name="<?= $fieldCode ?>" value="<?= htmlspecialchars($fieldItem['value']) ?>"/>
