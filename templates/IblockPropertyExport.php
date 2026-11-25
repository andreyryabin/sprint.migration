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
 * @var array $exports
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
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();

<?php foreach ($exports as $export) {?>
        $helper->Iblock()->saveProperty(
            $helper->Iblock()->getIblockIdIfExists(<?php echo var_export($export['iblock']['CODE'], true) ?>, <?php echo var_export($export['iblock']['IBLOCK_TYPE_ID'], true) ?>),
            <?php echo var_export($export['prop'], 1) ?>
        );
<?php } ?>

    }
}
