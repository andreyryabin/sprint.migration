<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 * @var $moduleVersion
 * @var $form
 * @var $statuses
 * @var $fields
 * @var $validators
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
        $formHelper = $helper->Form();
<?php if (!empty($formExport)): ?>
        $formId = $formHelper->saveForm(<?= var_export($form, 1)?>);
<?php else:?>
        $formId = $formHelper->getFormIdIfExists('<?= $form['SID']?>');
<?php endif?>
<?php if (!empty($statuses)): ?>
        $formHelper->saveStatuses($formId, <?= var_export($statuses, 1)?>);
<?php endif;?>
<?php if (!empty($fields)): ?>
        $formHelper->saveFields($formId, <?= var_export($fields, 1)?>);
<?php endif;?>
    }

    public function down()
    {
        //your code ...
    }
}

