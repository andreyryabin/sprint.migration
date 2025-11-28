<?php

/**
 * @var $version
 * @var $description
 * @var $updateMethod
 * @var $properties
 * @var $propertyVariants
 * @var $extendUse
 * @var $extendClass
 * @var $moduleVersion
 * @var $author
 * @formatter:off
 */

use Sprint\Migration\Builders\OrderPropertiesBuilder;

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
