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
 * @var $fieldsMode
 * @var $author
 * @formatter:off
 */

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
<?php if (!empty($formExport)): ?>
        $formId = $helper->Form()->saveForm(<?= var_export($form, 1)?>);
<?php else:?>
        $formId = $helper->Form()->getFormIdIfExists('<?= $form['SID']?>');
<?php endif?>
<?php if (!empty($statuses)): ?>
        $helper->Form()->saveStatuses($formId, <?= var_export($statuses, 1)?>);
<?php endif;?>
<?php if (!empty($fields) && $fieldsMode == 'all'): ?>
        $helper->Form()->saveFields($formId, <?= var_export($fields, 1)?>);
<?php endif;?>
<?php if (!empty($fields) && $fieldsMode == 'some'): ?>
    <?php foreach ($fields as $field) { ?>
        $helper->Form()->saveField($formId, <?= var_export($field, 1)?>);
    <?php } ?>
<?php endif;?>
    }
}

