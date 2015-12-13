<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 */

?><?php echo "<?php\n"?>

namespace Sprint\Migration;
use \Sprint\Migration\Helpers\AgentHelper;
use \Sprint\Migration\Helpers\EventHelper;
use \Sprint\Migration\Helpers\IblockHelper;
use \Sprint\Migration\Helpers\LangHelper;
use \Sprint\Migration\Helpers\SiteHelper;
use \Sprint\Migration\Helpers\UserTypeEntityHelper;
use \Sprint\Migration\Helpers\UserGroupHelper;
<?php echo $extendUse?>

class <?php echo $version?> extends <?php echo $extendClass?> {

    protected $description = "<?php echo $description?>";

    public function up(){
        $agentHelper = new AgentHelper();
        $eventHelper = new EventHelper();
        $iblockHelper = new IblockHelper();
        $langHelper = new LangHelper();
        $siteHelper = new SiteHelper();
        $userTypeEntityHelper = new UserTypeEntityHelper();
        $userGroupHelper = new UserGroupHelper();

        //your code ...

    }

    public function down(){
        $agentHelper = new AgentHelper();
        $eventHelper = new EventHelper();
        $iblockHelper = new IblockHelper();
        $langHelper = new LangHelper();
        $siteHelper = new SiteHelper();
        $userTypeEntityHelper = new UserTypeEntityHelper();
        $userGroupHelper = new UserGroupHelper();

        //your code ...

    }

}
