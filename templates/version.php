<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 * @var $upcode
 * @var $downcode
 */

?><?php echo "<?php\n"?>

namespace Sprint\Migration;

<?php echo $extendUse?>

class <?php echo $version?> extends <?php echo $extendClass?> {

    protected $description = "<?php echo $description?>";

    public function up(){
        $helper = new HelperManager();

        <?php echo $upcode?>

    }

    public function down(){
        $helper = new HelperManager();

        <?php echo $downcode?>

    }

}
