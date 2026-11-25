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
 * @var $channel
 * @var $vote
 * @var $questions
 */

/** @formatter:off */?><?php echo "<?php\n" ?>

namespace Sprint\Migration;

<?php echo $extendUse ?>

class <?php echo $version ?> extends <?php echo $extendClass ?>

{
    protected $author = <?php echo var_export($author, true) ?>;

    protected $description = <?php echo var_export($description, true) ?>;

    protected $moduleVersion = <?php echo var_export($moduleVersion, true) ?>;

    /**
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $channelId = $helper->Vote()->saveChannel(<?= var_export($channel, 1)?>);

        $voteId = $helper->Vote()->saveVote($channelId, <?= var_export($vote, 1)?>);

        $helper->Vote()->saveQuestions($voteId, <?= var_export($questions, 1)?>);
    }
}
