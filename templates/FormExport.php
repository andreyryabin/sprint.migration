<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 * @var $form
 * @var $sid
 */

$SID = $sid ? $sid : $form['FORM']['SID']
?><?php echo "<?php\n" ?>

namespace Sprint\Migration;

<?php echo $extendUse ?>

class <?php echo $version ?> extends <?php echo $extendClass ?>

{

    protected $description = "<?php echo $description ?>";

    public function up() {
        $helper = new HelperManager();

        $formHelper = $helper->Form();
        $formId = $formHelper->saveForm(<?= var_export($form, 1)?>, '<?= $SID?>');

    }

    public function down() {
        $helper = new HelperManager();

        $helper->Form()->deleteFormBySID('<?= $SID?>');

    }

}

