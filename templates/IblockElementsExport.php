<?php

/**
 * @var $version
 * @var $description
 * @var $updateMode
 * @var $extendUse
 * @var $extendClass
 * @var $moduleVersion
 * @var $iblockElementsFile
 * @formatter:off
 */

?><?php echo "<?php\n" ?>

namespace Sprint\Migration;

<?php echo $extendUse ?>

class <?php echo $version ?> extends <?php echo $extendClass ?>

{
    protected $description = "<?php echo $description ?>";

    protected $moduleVersion = "<?php echo $moduleVersion ?>";

    /**
     * @throws Exceptions\ExchangeException
     * @throws Exceptions\RestartException
     * @return bool|void
     */
    public function up()
    {
        $this->getExchangeManager()
            ->IblockElementsImport()
            ->setExchangeResource('iblock_elements.xml')
            ->setLimit(20)
            ->execute(function ($item) {
<?php if ($updateMode == 'code') { ?>
                $this->getHelperManager()
                    ->Iblock()
                    ->saveElement(
                        $item['iblock_id'],
                        $item['fields'],
                        $item['properties']
                    );
<?php } elseif($updateMode == 'xml_id') { ?>
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

    public function down()
    {
        //your code ...
    }
}
