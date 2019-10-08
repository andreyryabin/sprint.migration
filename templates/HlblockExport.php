<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 *
 * @var $hlblock
 * @var $hlblockFields
 * @var $hlblockPermissions
 */

?><?php echo "<?php\n" ?>

namespace Sprint\Migration;

<?php echo $extendUse ?>

class <?php echo $version ?> extends <?php echo $extendClass ?>

{
    protected $description = "<?php echo $description ?>";

    /**
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->saveHlblock(<?php echo var_export($hlblock, 1) ?>);
<? if (!empty($hlblockPermissions)): ?>
    $helper->Hlblock()->saveGroupPermissions($hlblockId, <?php echo var_export($hlblockPermissions, 1) ?>);
<?endif?>
<? if (!empty($hlblockFields)): ?>
<?php foreach ($hlblockFields as $field): ?>
        $helper->Hlblock()->saveField($hlblockId, <?php echo var_export($field, 1) ?>);
<? endforeach; ?>
<?endif?>
    }

    public function down()
    {
        //your code ...
    }
}
