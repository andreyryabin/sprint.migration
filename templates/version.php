<?php echo "<?php\n"?>

namespace Sprint\Migration;
use \Sprint\Migration\Helpers\IblockHelper;
use \Sprint\Migration\Helpers\EventHelper;
use \Sprint\Migration\Helpers\UserTypeEntityHelper;

class <?=$version?> extends Version {

    protected $description = "<?=$description?>";

    public function up(){
        $helper = new IblockHelper();
    }

    public function down(){
        $helper = new IblockHelper();
    }

}
