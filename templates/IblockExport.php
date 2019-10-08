<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
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
<? if (!empty($iblockType)): ?>
        $helper->Iblock()->saveIblockType(<?php echo var_export($iblockType, 1) ?>);
<? endif; ?>
<? if (!empty($iblockExport)): ?>
        $iblockId = $helper->Iblock()->saveIblock(<?php echo var_export($iblock, 1) ?>);
<? else:?>
        $iblockId = $helper->Iblock()->getIblockIdIfExists('<?php echo $iblock['CODE'] ?>', '<?php echo $iblock['IBLOCK_TYPE_ID'] ?>');
<? endif; ?>
<? if (!empty($iblockFields)): ?>
        $helper->Iblock()->saveIblockFields($iblockId, <?php echo var_export($iblockFields, 1) ?>);
<? endif; ?>
<? if (!empty($iblockPermissions)): ?>
    $helper->Iblock()->saveGroupPermissions($iblockId, <?php echo var_export($iblockPermissions, 1) ?>);
<? endif; ?>
<? if (!empty($iblockProperties)): ?>
<?php foreach ($iblockProperties as $iblockProperty): ?>
        $helper->Iblock()->saveProperty($iblockId, <?php echo var_export($iblockProperty, 1) ?>);
<? endforeach; ?>
<? endif; ?>
<? if (!empty($exportElementForm)): ?>
        $helper->UserOptions()->saveElementForm($iblockId, <?php echo var_export($exportElementForm, 1) ?>);
<? endif; ?>
<? if (!empty($exportSectionForm)): ?>
        $helper->UserOptions()->saveSectionForm($iblockId, <?php echo var_export($exportSectionForm, 1) ?>);
<? endif; ?>
<? if (!empty($exportElementList)): ?>
        $helper->UserOptions()->saveElementList($iblockId, <?php echo var_export($exportElementList, 1) ?>);
<? endif; ?>
<? if (!empty($exportSectionList)): ?>
        $helper->UserOptions()->saveSectionList($iblockId, <?php echo var_export($exportSectionList, 1) ?>);
<? endif; ?>
<? if (!empty($exportElementGrid)): ?>
    $helper->UserOptions()->saveElementGrid($iblockId, <?php echo var_export($exportElementGrid, 1) ?>);
<? endif; ?>
<? if (!empty($exportSectionGrid)): ?>
    $helper->UserOptions()->saveSectionGrid($iblockId, <?php echo var_export($exportSectionGrid, 1) ?>);
<? endif; ?>

    }

    public function down()
    {
        //your code ...
    }
}