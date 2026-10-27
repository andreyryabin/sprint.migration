<?php

/**
 * @var string $version
 * @var string $description
 * @var string $extendUse
 * @var string $extendClass
 * @var string $moduleVersion
 * @var string $author
 * @var array $iblock
 * @var array $propertyCodes
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
        $iblockId = $helper->Iblock()->getIblockIdIfExists(
            <?php echo var_export((string)$iblock['CODE'], 1) ?>,
            <?php echo var_export((string)$iblock['IBLOCK_TYPE_ID'], 1) ?>
        );

<?php foreach ($propertyCodes as $propertyCode) {?>
        $helper->Iblock()->deletePropertyIfExists($iblockId, <?php echo var_export((string)$propertyCode, 1) ?>);
<?php } ?>
    }
}
