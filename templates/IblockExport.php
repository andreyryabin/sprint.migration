<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 */

?><?php echo "<?php\n"?>

namespace Sprint\Migration;

<?php echo $extendUse?>

class <?php echo $version?> extends <?php echo $extendClass?> {

    protected $description = "<?php echo $description?>";

    public function up(){
        $helper = new HelperManager();

        $helper->Iblock()->addIblockTypeIfNotExists(<?php echo var_export($iblockType, 1)?>);

        $iblockId = $helper->Iblock()->addIblockIfNotExists(<?php echo var_export($iblock, 1)?>);

        $helper->Iblock()->updateIblockFields($iblockId, <?php echo var_export($iblockFields, 1)?>);

        <?php foreach ($iblockProperties as $iblockProperty):?>
        $helper->Iblock()->addPropertyIfNotExists($iblockId, <?php echo var_export($iblockProperty, 1)?>);
        <?endforeach;?>

        <?if (!empty($iblockAdminTabs)):?>
        $helper->AdminIblock()->buildElementForm($iblockId, <?php echo var_export($iblockAdminTabs, 1)?>);
        <?endif;?>

    }

    public function down(){
        $helper = new HelperManager();

        //your code ...

    }

}
