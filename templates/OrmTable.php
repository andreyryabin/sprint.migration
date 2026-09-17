<?php
/**
 * @var string $version
 * @var string $description
 * @var string $extendUse
 * @var string $extendClass
 * @var string $moduleVersion
 * @var string $author
 * @var string $tableName
 * @var bool $createTable
 * @var array $fields
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
        $tableName = <?php echo var_export($tableName, 1) ?>;
        $fields = <?php echo var_export($fields, 1) ?>;

<?php if ($createTable) { ?>
        $helper->Sql()->createTableIfNotExists($tableName, $fields);
<?php } else { ?>
        if (!$helper->Sql()->hasTable($tableName)) {
            throw new Exceptions\HelperException("Table \"$tableName\" not found");
        }
<?php } ?>
        $helper->Sql()->addColumnsIfNotExists($tableName, $fields);
    }
}
