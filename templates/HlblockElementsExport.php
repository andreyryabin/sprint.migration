<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 * @var $moduleVersion
 * @var $updateMethod
 * @var $equalKeys
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
<?php } elseif($updateMethod == HlblockElementsBuilder::UPDATE_METHOD_EQUAL_KEYS) { ?>
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
