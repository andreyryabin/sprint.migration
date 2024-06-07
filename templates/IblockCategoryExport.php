<?php
/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 * @var $moduleVersion
 * @var $iblock
 * @var $sectionTree
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

        $iblockId = $helper->Iblock()->getIblockIdIfExists(
            '<?php echo $iblock['CODE'] ?>',
            '<?php echo $iblock['IBLOCK_TYPE_ID'] ?>'
        );

        $helper->Iblock()->addSectionsFromTree(
            $iblockId,
            <?php echo var_export($sectionTree, 1) ?>
        );
    }
}
