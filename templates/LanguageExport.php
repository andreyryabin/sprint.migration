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
 * @var $cultures
 * @var $languages
 */

/** @formatter:off */?><?php echo "<?php\n" ?>

namespace Sprint\Migration;

<?php echo $extendUse ?>

class <?php echo $version ?> extends <?php echo $extendClass ?>

{
    protected $author = <?php echo var_export($author, true)?>;

    protected $description = <?php echo var_export($description, true) ?>;

    protected $moduleVersion = <?php echo var_export($moduleVersion, true) ?>;

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
