<?php

/**
 * @var $version
 * @var $description
 * @var $groups
 * @var $forums
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
<?php foreach ($groups as $group) { ?>
        $helper->Forum()->saveGroup(<?php echo var_export($group, 1) ?>);
<?php } ?>
<?php foreach ($forums as $forum) { ?>
        $helper->Forum()->saveForum(<?php echo var_export($forum, 1) ?>);
<?php } ?>
    }
}
