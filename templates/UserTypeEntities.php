<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 * @var $entities
 */

?><?php echo "<?php\n" ?>

namespace Sprint\Migration;

<?php echo $extendUse ?>

class <?php echo $version ?> extends <?php echo $extendClass ?>

{

    protected $description = "<?php echo $description ?>";

    public function up() {
        $helper = new HelperManager();

    <?php foreach ($entities as $entity): ?>
        $helper->UserTypeEntity()->saveUserTypeEntity(<?php echo var_export($entity, 1) ?>);
    <? endforeach; ?>
    }

    public function down() {
        $helper = new HelperManager();

        //your code ...

    }

}
