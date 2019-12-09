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
<? if (!empty($formExport)): ?>
        $formId = $formHelper->saveForm(<?= var_export($form, 1)?>);
<?else:?>
        $formId = $formHelper->getFormIdIfExists('<?= $form['SID']?>');
<?endif?>
<? if (!empty($statuses)): ?>
        $formHelper->saveStatuses($formId, <?= var_export($statuses, 1)?>);
<?endif;?>
<? if (!empty($fields)): ?>
        $formHelper->saveFields($formId, <?= var_export($fields, 1)?>);
<?endif;?>
    }

    public function down()
    {
        //your code ...
    }
}

