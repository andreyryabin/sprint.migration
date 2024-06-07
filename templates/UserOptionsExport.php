<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 * @var $moduleVersion
 * @var $author
 * @formatter:off
 */

?><?php echo "<?php\n" ?>

namespace Sprint\Migration;

<?php echo $extendUse ?>

class <?php echo $version ?> extends <?php echo $extendClass ?>

{
    protected $author = "<?php echo $author ?>";

    protected $description = "<?php echo $description ?>";

    protected $moduleVersion = "<?php echo $moduleVersion ?>";

    public function up()
    {
        $helper = $this->getHelperManager();
<?php if (!empty($exportUserForm)): ?>
        $helper->UserOptions()->saveUserForm(<?php echo var_export($exportUserForm, 1) ?>);
<?php endif; ?>
<?php if (!empty($exportUserList)): ?>
        $helper->UserOptions()->saveUserList(<?php echo var_export($exportUserList, 1) ?>);
<?php endif; ?>
<?php if (!empty($exportUserGroupList)): ?>
        $helper->UserOptions()->saveUserGroupList(<?php echo var_export($exportUserGroupList, 1) ?>);
<?php endif; ?>
<?php if (!empty($exportUserGrid)): ?>
    $helper->UserOptions()->saveUserGrid(<?php echo var_export($exportUserGrid, 1) ?>);
<?php endif; ?>
<?php if (!empty($exportUserGroupGrid)): ?>
    $helper->UserOptions()->saveUserGroupGrid(<?php echo var_export($exportUserGroupGrid, 1) ?>);
<?php endif; ?>

    }

}
