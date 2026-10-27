<?php

/**
 * @var $version
 * @var $description
 * @var $group
 * @var $blogs
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

        $groupId = $helper->Blog()->saveGroup(<?php echo var_export($group, 1) ?>);

<?php foreach ($blogs as $blog) { ?>
        $helper->Blog()->saveBlog($groupId, <?php echo var_export($blog, 1) ?>);
<?php } ?>
    }
}
