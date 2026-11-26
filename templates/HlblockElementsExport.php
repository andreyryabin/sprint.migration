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
 * @var $equalKeys
 */

use Sprint\Migration\Builders\HlblockElementsBuilder;

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
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $this->getExchangeManager()
             ->HlblockElementsImport()
             ->setLimit(20)
             ->execute(function ($item) {
<?php if ($updateMethod == HlblockElementsBuilder::UPDATE_METHOD_XML_ID) { ?>
                 $this->getHelperManager()
                      ->Hlblock()
                      ->saveElementByXmlId(
                          $item['hlblock_id'],
                          $item['fields']
                      );
<?php } elseif($updateMethod == HlblockElementsBuilder::UPDATE_METHOD_EQUAL) { ?>
                 $this->getHelperManager()
                      ->Hlblock()
                      ->saveElementWithEqualKeys(
                          $item['hlblock_id'],
                          $item['fields'],
                          [<?php echo implode(', ', array_map(fn($v) => "'$v'",$equalKeys))?>]
                      );
<?php } else { ?>
                 $this->getHelperManager()
                      ->Hlblock()
                      ->addElement(
                          $item['hlblock_id'],
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
<?php if ($updateMethod == HlblockElementsBuilder::UPDATE_METHOD_XML_ID) { ?>
        $this->getExchangeManager()
             ->HlblockElementsImport()
             ->setLimit(20)
             ->execute(function ($item) {
                 $this->getHelperManager()
                      ->Hlblock()
                      ->deleteElementByXmlId(
                          $item['hlblock_id'],
                          $item['fields']['UF_XML_ID']
                      );
             });
<?php } ?>
    }


}
