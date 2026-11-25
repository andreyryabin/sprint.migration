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
 * @var $iblockElementsFile
 */

/** @formatter:off */?><?php echo "<?php\n" ?>

namespace Sprint\Migration;

<?php echo $extendUse ?>

class <?php echo $version ?> extends <?php echo $extendClass ?>

{

    protected $author = <?php echo var_export($author, true) ?>;

    protected $description = <?php echo var_export($description, true) ?>;

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
             ->MedialibElementsImport()
             ->setLimit(20)
             ->execute(
                 function ($item) {
                     $this->getHelperManager()
                          ->Medialib()
                          ->saveElement($item);
                 }
             );
    }
}
