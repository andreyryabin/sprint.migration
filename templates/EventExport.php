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

class <?php echo $version ?> extends <?php echo $extendClass ?>

{
    protected $description = "<?php echo $description ?>";

    /**
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();
<?foreach ($result as $eventName => $item):?>
<?php foreach ($item['types'] as $fields): ?>
        $helper->Event()->saveEventType('<?php echo $eventName ?>', <?php echo var_export($fields, 1) ?>);
<? endforeach; ?>
<?php foreach ($item['messages'] as $fields): ?>
        $helper->Event()->saveEventMessage('<?php echo $eventName ?>', <?php echo var_export($fields, 1) ?>);
<? endforeach; ?>
<? endforeach; ?>
    }

    public function down()
    {
        //your code ...
    }
}
