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

        $iblockId = $helper->Iblock()->getIblockId('<?php echo $iblock['CODE']?>','<?php echo $iblock['IBLOCK_TYPE_ID']?>');

        <?php foreach ($iblockProperties as $iblockProperty):?>
        $helper->Iblock()->addPropertyIfNotExists($iblockId, <?php echo var_export($iblockProperty, 1)?>);
        <?endforeach;?>

    }

    public function down(){
        $helper = new HelperManager();

        //your code ...

    }

}
