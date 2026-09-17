<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class VoteBuilder extends VersionBuilder
{
    protected function isBuilderEnabled()
    {
        return $this->getHelperManager()->Vote()->isEnabled();
    }

    protected function initialize()
    {
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Vote'));

        $this->setTitle(implode(' ', [
            Locale::getMessage('BUILDER_VoteExport1'),
        ]));

        $this->setDescription(implode(PHP_EOL, [
            Locale::getMessage('DEVELOPER_NAME', ['#VALUE#' => '@temi4']),
            Locale::getMessage('DEVELOPER_URI', ['#VALUE#' => 'https://github.com/andreyryabin/sprint.migration/pull/179']),
            Locale::getMessage('BUILDER_VoteExport_Info')
        ]));

        $this->addVersionFields();
    }

    /**
     * @throws HelperException
     * @throws MigrationException
     * @throws RebuildException
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();

        $voteId = $this->addFieldAndReturn('vote_id', [
            'title' => Locale::getMessage('BUILDER_VoteExport_VoteId'),
            'width' => 250,
            'items' => $this->getVotesSelect(),
        ]);

        $vote = $helper->Vote()->exportVoteById($voteId);
        $channel = $helper->Vote()->exportChannelById((int)$vote['CHANNEL_ID']);
        $questions = $helper->Vote()->exportQuestions($voteId);

        unset($vote['CHANNEL_ID']);

        $this->createVersionFile(
            Module::getModuleTemplateFile('VoteExport'),
            [
                'channel'   => $channel,
                'vote'      => $vote,
                'questions' => $questions,
            ]
        );
    }

    private function getVotesSelect(): array
    {
        $helper = $this->getHelperManager();
        $channels = [];

        foreach ($helper->Vote()->getChannelsList() as $channel) {
            $channels[$channel['ID']] = '[' . $channel['SYMBOLIC_NAME'] . '] ' . $channel['TITLE'];
        }

        $votes = array_map(function ($vote) use ($channels) {
            $vote['CHANNEL_TITLE'] = $channels[$vote['CHANNEL_ID']] ?? Locale::getMessage('BUILDER_VoteExport_UnknownChannel');
            $vote['TITLE'] = '[' . $vote['ID'] . '] ' . $vote['TITLE'];
            return $vote;
        }, $helper->Vote()->getVotesList());

        return $this->createSelectWithGroups($votes, 'ID', 'TITLE', 'CHANNEL_TITLE');
    }
}
