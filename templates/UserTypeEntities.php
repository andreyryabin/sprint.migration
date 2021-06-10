<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 * @var $entities
 * @var $moduleVersion
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
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();
<?php foreach ($entities as $entity): ?>
        $helper->UserTypeEntity()->saveUserTypeEntity(<?php echo var_export($entity, 1) ?>);
<?php endforeach; ?>
    }

    public function down()
    {
        //your code ...
    }
}
