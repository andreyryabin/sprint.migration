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

        $iblockId = $helper->Iblock()->getIblockIdIfExists('<?php echo $iblock['CODE'] ?>', '<?php echo $iblock['IBLOCK_TYPE_ID'] ?>');
        $xmlfile = __DIR__ . '/<?=$version?>_files/iblock_elements.xml';

        $exchange = new IblockElementsImport($this);
        $exchange->from($xmlfile);
        $exchange->to($iblockId);
        $exchange->execute();
    }

    public function down()
    {
        $helper = $this->getHelperManager();

        //your code ...
    }
}