<?php

/**
 * @var $version
 * @var $description
 * @var $cultures
 * @var $languages
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
    protected $author = "<?php echo $author?>";

    protected $description = "<?php echo $description ?>";

    protected $moduleVersion = "<?php echo $moduleVersion ?>";

    /**
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();
<?php foreach ($cultures as $item) { ?>
    $helper->Culture()->saveCulture(<?php echo var_export($item, 1) ?>);
<?php } ?>
<?php foreach ($languages as $item) { ?>
        $helper->Lang()->saveLang(<?php echo var_export($item, 1) ?>);
<?php } ?>
    }
}
