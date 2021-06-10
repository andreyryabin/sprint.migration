<?php

/**
 * @var $version
 * @var $description
 * @var $result
 * @var $extendUse
 * @var $extendClass
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
<?php foreach ($result as $eventName => $item):?>
<?php foreach ($item['types'] as $fields): ?>
        $helper->Event()->saveEventType('<?php echo $eventName ?>', <?php echo var_export($fields, 1) ?>);
    <?php endforeach; ?>
<?php foreach ($item['messages'] as $fields): ?>
        $helper->Event()->saveEventMessage('<?php echo $eventName ?>', <?php echo var_export($fields, 1) ?>);
    <?php endforeach; ?>
<?php endforeach; ?>
    }

    public function down()
    {
        //your code ...
    }
}
