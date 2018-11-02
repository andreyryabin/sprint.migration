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

    public function up() {
        $helper = new HelperManager();

    <? if (!empty($iblockType)): ?>
        $helper->Iblock()->saveIblockType(<?php echo var_export($iblockType, 1) ?>);
    <? endif; ?>

    <? if (!empty($iblockExport)): ?>
        $iblockId = $helper->Iblock()->saveIblock(<?php echo var_export($iblock, 1) ?>);
    <? else:?>
        $iblockId = $helper->Iblock()->getIblockIdIfExists('<?php echo $iblock['CODE'] ?>','<?php echo $iblock['IBLOCK_TYPE_ID'] ?>');
    <? endif; ?>

    <? if (!empty($iblockFields)): ?>
        $helper->Iblock()->saveIblockFields($iblockId, <?php echo var_export($iblockFields, 1) ?>);
    <? endif; ?>

    <? if (!empty($iblockProperties)): ?>
    <?php foreach ($iblockProperties as $iblockProperty): ?>
        $helper->Iblock()->saveProperty($iblockId, <?php echo var_export($iblockProperty, 1) ?>);
    <? endforeach; ?>
    <? endif; ?>

    <? if (!empty($iblockAdminTabs)): ?>
        $helper->AdminIblock()->saveElementForm($iblockId, <?php echo var_export($iblockAdminTabs, 1) ?>);
    <? endif; ?>

    }

    public function down() {
        $helper = new HelperManager();

        //your code ...

    }

}
