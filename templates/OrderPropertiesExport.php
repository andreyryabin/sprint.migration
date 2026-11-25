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
 * @var $updateMethod
 * @var $properties
 * @var $propertyVariants
 */

use Sprint\Migration\Builders\OrderPropertiesBuilder;

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
<?php foreach ($properties as $property) { ?>
    <?php $updateMethod = $updateMethod !== OrderPropertiesBuilder::UPDATE_METHOD_NOT ? $updateMethod : null;?>
    <?php $variants = $propertyVariants[$property['ID']];?>
    <?php if(!empty($variants)) {?>
    $propertyId = $helper->OrderProperties()->saveOrderProperty(<?php echo var_export($property, 1) ?>, <?php echo var_export($updateMethod, 1)?>);
    <?php if($variants = $propertyVariants[$property['ID']]) { ?>
        if($propertyId > 0) {
            $helper->OrderProperties()->saveOrderPropertyVariants($propertyId, <?php echo var_export($variants, 1) ?>);
        }
    <?php } ?>
    <?php } else { ?>
    $helper->OrderProperties()->saveOrderProperty(<?php echo var_export($property, 1) ?>, <?php echo var_export($updateMethod, 1)?>);
    <?php } ?>
<?php } ?>
    }
}
