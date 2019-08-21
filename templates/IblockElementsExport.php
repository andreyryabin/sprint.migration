<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 *
 * @var $iblock
 * @var $iblockElementsFile
 */

?><?php echo "<?php\n" ?>

namespace Sprint\Migration;

<?php echo $extendUse ?>
use Sprint\Migration\Exchange\IblockElementsImport;

class <?php echo $version ?> extends <?php echo $extendClass ?>

{
    protected $description = "<?php echo $description ?>";

    /**
     * @throws Exceptions\ExchangeException
     * @throws Exceptions\HelperException
     * @throws Exceptions\RestartException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $iblockId = $helper->Iblock()->getIblockIdIfExists(
            '<?php echo $iblock['CODE'] ?>',
            '<?php echo $iblock['IBLOCK_TYPE_ID'] ?>'
        );

        $exchange = $this->getExchangeManager();

        $exchange->IblockElementsImport()
            ->setResource('iblock_elements.xml')
            ->setLimit(20)
            ->execute(function ($item) use ($helper, $iblockId) {
                $helper->Iblock()->addElement(
                    $iblockId,
                    $item['field'],
                    $item['property']
                );
            });
        }

    public function down()
    {
        //your code ...
    }
}