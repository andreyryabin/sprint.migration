<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 * @var $moduleVersion
 */

?><?php echo "<?php\n" ?>

namespace Sprint\Migration;

<?php echo $extendUse ?>

class <?php echo $version ?> extends <?php echo $extendClass ?>

{
    protected $description = "<?php echo $description ?>";

    protected $moduleVersion = "<?php echo $moduleVersion ?>";

    public function up()
    {
        $helper = $this->getHelperManager();
<? if (!empty($exportUserForm)): ?>
        $helper->UserOptions()->saveUserForm(<?php echo var_export($exportUserForm, 1) ?>);
<? endif; ?>
<? if (!empty($exportUserList)): ?>
        $helper->UserOptions()->saveUserList(<?php echo var_export($exportUserList, 1) ?>);
<? endif; ?>
<? if (!empty($exportUserGroupList)): ?>
        $helper->UserOptions()->saveUserGroupList(<?php echo var_export($exportUserGroupList, 1) ?>);
<? endif; ?>
<? if (!empty($exportUserGrid)): ?>
    $helper->UserOptions()->saveUserGrid(<?php echo var_export($exportUserGrid, 1) ?>);
<? endif; ?>
<? if (!empty($exportUserGroupGrid)): ?>
    $helper->UserOptions()->saveUserGroupGrid(<?php echo var_export($exportUserGroupGrid, 1) ?>);
<? endif; ?>

    }

    public function down()
    {
        //your code ...
    }
}
