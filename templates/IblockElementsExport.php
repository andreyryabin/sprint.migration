<?php

/**
 * @var $version
 * @var $description
 * @var $updateMode
 * @var $extendUse
 * @var $extendClass
 * @var $moduleVersion
 * @var $author
 * @formatter:off
 */

use Sprint\Migration\Builders\IblockElementsBuilder;

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
     * @return bool|void
     */
    public function up()
    {
        $this->getExchangeManager()
             ->IblockElementsImport()
             ->setLimit(20)
             ->execute(function ($item) {
<?php if ($updateMode == IblockElementsBuilder::UPDATE_MODE_CODE) { ?>
                 $this->getHelperManager()
                      ->Iblock()
                      ->saveElement(
                          $item['iblock_id'],
                          $item['fields'],
                          $item['properties']
                      );
<?php } elseif($updateMode == IblockElementsBuilder::UPDATE_MODE_XML_ID) { ?>
                 $this->getHelperManager()
                      ->Iblock()
                      ->saveElementByXmlId(
                          $item['iblock_id'],
                          $item['fields'],
                          $item['properties']
                      );
<?php } else { ?>
                 $this->getHelperManager()
                      ->Iblock()
                      ->addElement(
                          $item['iblock_id'],
                          $item['fields'],
                          $item['properties']
                      );
<?php } ?>
             });
    }

    /**
     * @throws Exceptions\MigrationException
     * @throws Exceptions\RestartException
     * @return bool|void
     */
    public function down()
    {
<?php if ($updateMode == IblockElementsBuilder::UPDATE_MODE_CODE) { ?>
        $this->getExchangeManager()
             ->IblockElementsImport()
             ->setLimit(10)
             ->execute(function ($item) {
                 $this->getHelperManager()
                      ->Iblock()
                      ->deleteElementByCode(
                          $item['iblock_id'],
                          $item['fields']['CODE']
                 );
             });
<?php } elseif($updateMode == IblockElementsBuilder::UPDATE_MODE_XML_ID) { ?>
        $this->getExchangeManager()
             ->IblockElementsImport()
             ->setLimit(10)
             ->execute(function ($item) {
                 $this->getHelperManager()
                     ->Iblock()
                     ->deleteElementByXmlId(
                         $item['iblock_id'],
                         $item['fields']['XML_ID']
                     );
             });
<?php } ?>
    }
}
