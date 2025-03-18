<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 * @var $moduleVersion
 * @var $updateMode
 * @var $hlblock
 * @var $author
 * @formatter:off
 */

use Sprint\Migration\Builders\HlblockElementsBuilder;

?><?php echo "<?php\n" ?>

namespace Sprint\Migration;

<?php echo $extendUse ?>

class <?php echo $version ?> extends <?php echo $extendClass ?>

{
    protected $author = "<?php echo $author ?>";

    protected $description   = "<?php echo $description ?>";

    protected $moduleVersion = "<?php echo $moduleVersion ?>";

    /**
     * @throws Exceptions\MigrationException
     * @throws Exceptions\RestartException
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('<?php echo $hlblock['NAME'] ?>');

        $this->getExchangeManager()
             ->HlblockElementsImport()
             ->setLimit(20)
             ->execute(function ($item) {
<?php if ($updateMode == HlblockElementsBuilder::UPDATE_MODE_XML_ID) { ?>
                 $this->getHelperManager()
                      ->Hlblock()
                      ->saveElementByXmlId(
                          $hlblockId,
                          $item['fields']
                      );
<?php } else { ?>
                 $this->getHelperManager()
                      ->Hlblock()
                      ->addElement(
                          $hlblockId,
                          $item['fields']
                      );
<?php } ?>
             });
    }

    /**
     * @throws Exceptions\MigrationException
     * @throws Exceptions\RestartException
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function down()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->getHlblockIdIfExists('<?php echo $hlblock['NAME'] ?>');

<?php if ($updateMode == HlblockElementsBuilder::UPDATE_MODE_XML_ID) { ?>
        $this->getExchangeManager()
             ->HlblockElementsImport()
             ->setLimit(20)
             ->execute(function ($item) {
                 $this->getHelperManager()
                      ->Hlblock()
                      ->deleteElementByXmlId(
                          $hlblockId,
                          $item['fields']['UF_XML_ID']
                      );
             });
<?php } ?>
    }


}
