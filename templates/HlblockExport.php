<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 * @var $moduleVersion
 * @var $hlblockExport
 * @var $hlblock
 * @var $hlblockFields
 * @var $hlblockPermissions
 * @var $exportElementForm
 * @var $exportElementList
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

    /**
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();
<?php if (!empty($hlblockExport)): ?>
    $hlblockId = $helper->Hlblock()->saveHlblock(<?php echo var_export($hlblock, 1) ?>);
<?php else:?>
    $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('<?php echo $hlblock['NAME'] ?>');
<?php endif; ?>
<?php if (!empty($hlblockPermissions)): ?>
    $helper->Hlblock()->saveGroupPermissions($hlblockId, <?php echo var_export($hlblockPermissions, 1) ?>);
<?php endif?>
<?php if (!empty($hlblockFields)): ?>
<?php foreach ($hlblockFields as $field) { ?>
        $helper->Hlblock()->saveField($hlblockId, <?php echo var_export($field, 1) ?>);
    <?php } ?>
<?php endif?>
<?php if (!empty($exportElementForm)): ?>
    $helper->UserOptions()->saveHlblockForm($hlblockId, <?php echo var_export($exportElementForm, 1) ?>);
<?php endif?>
<?php if (!empty($exportElementList)): ?>
    $helper->UserOptions()->saveHlblockList($hlblockId, <?php echo var_export($exportElementList, 1) ?>);
<?php endif?>
    }
}
