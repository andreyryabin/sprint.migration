<?php

/**
 * @var $version
 * @var $description
 * @var $iblock
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

    /**
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();
<?php if (!empty($iblockType)): ?>
        $helper->Iblock()->saveIblockType(<?php echo var_export($iblockType, 1) ?>);
<?php endif; ?>
<?php if (!empty($iblockExport)): ?>
        $iblockId = $helper->Iblock()->saveIblock(<?php echo var_export($iblock, 1) ?>);
<?php else:?>
        $iblockId = $helper->Iblock()->getIblockIdIfExists('<?php echo $iblock['CODE'] ?>', '<?php echo $iblock['IBLOCK_TYPE_ID'] ?>');
<?php endif; ?>
<?php if (!empty($iblockFields)): ?>
        $helper->Iblock()->saveIblockFields($iblockId, <?php echo var_export($iblockFields, 1) ?>);
<?php endif; ?>
<?php if (!empty($iblockPermissions)): ?>
    $helper->Iblock()->saveGroupPermissions($iblockId, <?php echo var_export($iblockPermissions, 1) ?>);
<?php endif; ?>
<?php if (!empty($iblockProperties)): ?>
<?php foreach ($iblockProperties as $iblockProperty) { ?>
        $helper->Iblock()->saveProperty($iblockId, <?php echo var_export($iblockProperty, 1) ?>);
    <?php } ?>
<?php endif; ?>
<?php if (!empty($exportElementForm)): ?>
        $helper->UserOptions()->saveElementForm($iblockId, <?php echo var_export($exportElementForm, 1) ?>);
<?php endif; ?>
<?php if (!empty($exportSectionForm)): ?>
        $helper->UserOptions()->saveSectionForm($iblockId, <?php echo var_export($exportSectionForm, 1) ?>);
<?php endif; ?>
<?php if (!empty($exportElementList)): ?>
        $helper->UserOptions()->saveElementList($iblockId, <?php echo var_export($exportElementList, 1) ?>);
<?php endif; ?>
<?php if (!empty($exportSectionList)): ?>
        $helper->UserOptions()->saveSectionList($iblockId, <?php echo var_export($exportSectionList, 1) ?>);
<?php endif; ?>
<?php if (!empty($exportElementGrid)): ?>
    $helper->UserOptions()->saveElementGrid($iblockId, <?php echo var_export($exportElementGrid, 1) ?>);
<?php endif; ?>
<?php if (!empty($exportSectionGrid)): ?>
    $helper->UserOptions()->saveSectionGrid($iblockId, <?php echo var_export($exportSectionGrid, 1) ?>);
<?php endif; ?>

    }
}
