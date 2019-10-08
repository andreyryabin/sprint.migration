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

<?foreach ($items as $item):?>
        $helper->UserGroup()->saveGroup('<?php echo $item['STRING_ID'] ?>',<?php echo var_export($item['FIELDS'], 1) ?>);
<? endforeach; ?>
    }

    public function down()
    {
        //your code ...
    }
}
