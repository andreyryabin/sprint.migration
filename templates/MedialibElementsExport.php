<?php

/**
 * @var $version
 * @var $description
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
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $this->getExchangeManager()
             ->MedialibElementsImport()
             ->setExchangeResource('medialib_elements.xml')
             ->setLimit(20)
             ->execute(
                 function ($item) {
                     $this->getHelperManager()
                          ->Medialib()
                          ->saveElement($item);
                 }
             );
    }

    public function down()
    {
        //your code ...
    }
}
