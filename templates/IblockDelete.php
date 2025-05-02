<?php

/**
 * @var       $version
 * @var       $description
 * @var       $items
 * @var       $extendUse
 * @var       $extendClass
 * @var       $moduleVersion
 * @var       $author
 * @var       $tag
 * @var array $iblocks
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

    protected $tag = "<?php echo $tag ?>";

    public function up()
    {
        $helper = $this->getHelperManager();

<?php foreach ($iblocks as $iblock){?>
        $helper->Iblock()->deleteIblockIfExists('<?php echo $iblock['CODE']?>', '<?php echo $iblock['IBLOCK_TYPE_ID']?>');
<?php } ?>
    }
}
