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
 * @var array $iblock
 * @var array $propertyCodes
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
        $iblockId = $helper->Iblock()->getIblockIdIfExists(
            <?php echo var_export((string)$iblock['CODE'], true) ?>,
            <?php echo var_export((string)$iblock['IBLOCK_TYPE_ID'], true) ?>
        );

<?php foreach ($propertyCodes as $propertyCode) {?>
        $helper->Iblock()->deletePropertyIfExists($iblockId, <?php echo var_export((string)$propertyCode, 1) ?>);
<?php } ?>
    }
}
