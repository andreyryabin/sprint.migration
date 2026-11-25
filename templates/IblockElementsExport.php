<?php
/**
 * @var string $version
 * @var string $description
 * @var string $extendUse
 * @var string $extendClass
 * @var string $moduleVersion
 * @var string $author
 */
/**
 * @var $updateMethod
 */

use Sprint\Migration\Builders\IblockElementsBuilder;

/** @formatter:off */?><?php echo "<?php\n" ?>

namespace Sprint\Migration;
<?php echo $extendUse ?>

class <?php echo $version ?> extends <?php echo $extendClass ?>

{
    protected $author = <?php echo var_export($author, true) ?>;

    protected $description   = <?php echo var_export($description, true) ?>;

    protected $moduleVersion = <?php echo var_export($moduleVersion, true) ?>;

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
<?php if ($updateMethod == IblockElementsBuilder::UPDATE_METHOD_CODE) { ?>
                 $this->getHelperManager()
                      ->Iblock()
                      ->saveElementByCode(
                          $item['iblock_id'],
                          $item['fields'],
                          $item['properties']
                      );
<?php } elseif($updateMethod == IblockElementsBuilder::UPDATE_METHOD_XML_ID) { ?>
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
<?php if ($updateMethod == IblockElementsBuilder::UPDATE_METHOD_CODE) { ?>
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
<?php } elseif($updateMethod == IblockElementsBuilder::UPDATE_METHOD_XML_ID) { ?>
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
