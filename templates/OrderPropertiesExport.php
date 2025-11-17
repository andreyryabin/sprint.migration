<?php

/**
 * @var $version
 * @var $description
 * @var $updateMethod
 * @var $properties
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
    <?php if ($updateMethod === OrderPropertiesBuilder::UPDATE_METHOD_CODE) { ?>
        $helper->OrderProperties()->saveOrderProperty(<?php echo var_export($property, 1) ?>, 'CODE');
    <?php } elseif ($updateMethod === OrderPropertiesBuilder::UPDATE_METHOD_XML_ID) { ?>
        $helper->OrderProperties()->saveOrderProperty(<?php echo var_export($property, 1) ?>, 'XML_ID');
    <?php } else { ?>
        $helper->OrderProperties()->saveOrderProperty(<?php echo var_export($property, 1) ?>);
    <?php } ?>
<?php } ?>
    }
}
