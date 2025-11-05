<?php

/**
 * @var string $version
 * @var string $description
 * @var string $extendUse
 * @var string $extendClass
 * @var string $moduleVersion
 * @var string $author
 * @var array $exports
 * @formatter:off
 */

?><?php echo "<?php\n" ?>

namespace Sprint\Migration;

<?php echo $extendUse ?>

class <?php echo $version ?> extends <?php echo $extendClass ?>

{
    protected $author = "<?php echo $author ?>";

    protected $description = "<?php echo $description ?>";

    protected $moduleVersion = "<?php echo $moduleVersion ?>";

    /**
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();

<?php foreach ($exports as $export) {?>
        $helper->Iblock()->saveProperty(
            $helper->Iblock()->getIblockIdIfExists('<?php echo $export['iblock']['CODE'] ?>', '<?php echo $export['iblock']['IBLOCK_TYPE_ID'] ?>'),
            <?php echo var_export($export['prop'], 1) ?>
        );
<?php } ?>

    }
}
