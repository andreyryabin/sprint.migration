<?php
/**
 * @var string $version
 * @var string $description
 * @var string $extendUse
 * @var string $extendClass
 * @var string $moduleVersion
 * @var string $author
 */

/**
 * @var $result
 */

/** @formatter:off */?><?php echo "<?php\n" ?>

namespace Sprint\Migration;

<?php echo $extendUse ?>

class <?php echo $version ?> extends <?php echo $extendClass ?>

{
    protected $author = <?php echo var_export($author, true) ?>;

    protected $description = <?php echo var_export($description, true) ?>;

    protected $moduleVersion = <?php echo var_export($moduleVersion, true) ?>;

    /**
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();
<?php foreach ($result as $eventName => $item) { ?>
<?php foreach ($item['types'] as $fields) { ?>
        $helper->Event()->saveEventType(<?php echo var_export($eventName, 1) ?>, <?php echo var_export($fields, 1) ?>);
    <?php } ?>
<?php foreach ($item['email'] as $fields) { ?>
        $helper->Event()->saveEventMessage(<?php echo var_export($eventName, 1) ?>, <?php echo var_export($fields, 1) ?>);
    <?php } ?>
<?php foreach ($item['sms'] as $fields) { ?>
    $helper->Event()->saveEventSmsTemplate(<?php echo var_export($eventName, 1) ?>, <?php echo var_export($fields, 1) ?>);
<?php } ?>
<?php } ?>
    }
}
