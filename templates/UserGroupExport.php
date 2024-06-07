<?php

/**
 * @var $version
 * @var $description
 * @var $items
 * @var $extendUse
 * @var $extendClass
 * @var $moduleVersion
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

<?php foreach ($items as $item) { ?>
        $helper->UserGroup()->saveGroup('<?php echo $item['STRING_ID'] ?>',<?php echo var_export($item['FIELDS'], 1) ?>);
<?php } ?>
    }

}
