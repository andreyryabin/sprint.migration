<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 */

?><?php echo "<?php\n" ?>

namespace Sprint\Migration;

<?php echo $extendUse ?>

class <?php echo $version ?> extends <?php echo $extendClass ?> {

protected $description = "<?php echo $description ?>";

public function up(){
$helper = new HelperManager();

<?foreach ($items as $item):?>

$hlblockId = $helper->Hlblock()->addHlblockIfNotExists(<?php echo var_export($item['hlblock'], 1) ?>);
$entityId = 'HLBLOCK_' . $hlblockId;

<?php foreach ($item['hlblockEntities'] as $entity): ?>
    $helper->UserTypeEntity()->addUserTypeEntityIfNotExists($entityId, '<?php echo $entity['FIELD_NAME'] ?>', <?php echo var_export($entity, 1) ?>);
<? endforeach; ?>

<? endforeach; ?>
}

public function down(){
$helper = new HelperManager();

//your code ...

}

}
