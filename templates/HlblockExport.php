<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 */

?><?php echo "<?php\n"?>

namespace Sprint\Migration;

<?php echo $extendUse?>

class <?php echo $version?> extends <?php echo $extendClass?> {

    protected $description = "<?php echo $description?>";

    public function up(){
        $helper = new HelperManager();

        $hlblockId = $helper->Hlblock()->addHlblockIfNotExists(<?php echo var_export($hlblock, 1)?>);
        $entityId = 'HLBLOCK_' . $hlblockId;

        <?php foreach ($hlblockEntities as $entity):?>
        $helper->UserTypeEntity()->addUserTypeEntityIfNotExists($entityId, '<?php echo $entity['FIELD_NAME']?>', <?php echo var_export($entity, 1)?>);
        <?endforeach;?>
    }

    public function down(){
        $helper = new HelperManager();

        //your code ...

    }

}
