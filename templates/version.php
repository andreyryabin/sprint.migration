<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 */

?><?php echo "<?php\n"?>

namespace Sprint\Migration;
use \Sprint\Migration\Helpers\IblockHelper;
use \Sprint\Migration\Helpers\EventHelper;
use \Sprint\Migration\Helpers\UserTypeEntityHelper;
<?php echo $extendUse?>

class <?php echo $version?> extends <?php echo $extendClass?> {

    protected $description = "<?php echo $description?>";

    public function up(){
        $helper = new IblockHelper();
    }

    public function down(){
        $helper = new IblockHelper();
    }

}
