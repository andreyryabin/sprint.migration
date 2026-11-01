<?php
/**
 * @var string $version
 * @var string $description
 * @var string $extendUse
 * @var string $extendClass
 * @var string $moduleVersion
 * @var string $author
 * @var string $tableName
 * @var array $columnNames
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

<?php foreach ($columnNames as $columnName) { ?>
        $helper->Sql()->dropColumnIfExists($tableName, <?php echo var_export($columnName, 1) ?>);
<?php } ?>
    }

    /**
     * @throws Exceptions\MigrationException
     * @return bool|void
     */
    public function down()
    {
        throw new Exceptions\MigrationException('This migration cannot be rolled back automatically');
    }
}
