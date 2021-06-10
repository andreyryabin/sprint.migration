<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 * @var $moduleVersion
 * @var $hlblock
 * @var $hlblockFields
 * @var $hlblockPermissions
 * @formatter:off
 */

?><?php echo "<?php\n" ?>

namespace Sprint\Migration;

<?php echo $extendUse ?>

class <?php echo $version ?> extends <?php echo $extendClass ?>

{
    protected $description = "<?php echo $description ?>";

    protected $moduleVersion = "<?php echo $moduleVersion ?>";

    /**
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->saveHlblock(<?php echo var_export($hlblock, 1) ?>);
<?php if (!empty($hlblockPermissions)): ?>
    $helper->Hlblock()->saveGroupPermissions($hlblockId, <?php echo var_export($hlblockPermissions, 1) ?>);
<?php endif?>
<?php if (!empty($hlblockFields)): ?>
<?php foreach ($hlblockFields as $field): ?>
        $helper->Hlblock()->saveField($hlblockId, <?php echo var_export($field, 1) ?>);
    <?php endforeach; ?>
<?php endif?>
    }

    public function down()
    {
        //your code ...
    }
}
