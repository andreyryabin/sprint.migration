<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 * @var $form
 * @var $statuses
 * @var $fields
 * @var $validators
 */

?><?php echo "<?php\n" ?>

namespace Sprint\Migration;

<?php echo $extendUse ?>

class <?php echo $version ?> extends <?php echo $extendClass ?>

{

    protected $description = "<?php echo $description ?>";

    public function up() {
        $helper = new HelperManager();

        $formHelper = $helper->Form();

        $formId = $formHelper->saveForm(<?= var_export($form, 1)?>);

        $formHelper->saveStatuses($formId, <?= var_export($statuses, 1)?>);

        $formHelper->saveFields($formId, <?= var_export($fields, 1)?>);

    }

    public function down() {
        $helper = new HelperManager();

        $helper->Form()->deleteFormBySid('<?= $form['SID']?>');

    }

}

