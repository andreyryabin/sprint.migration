<?php

namespace Sprint\Migration\Helpers;

use Bitrix\Vote\ChannelTable;
use CVote;
use CVoteAnswer;
use CVoteChannel;
use CVoteQuestion;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;

class VoteHelper extends Helper
{
    public function isEnabled(): bool
    {
        return $this->checkModules(['vote']);
    }

    public function getChannelsList(array $filter = []): array
    {
        $by = 's_c_sort';
        $order = 'asc';
        $isFiltered = null;

        return $this->fetchAll(CVoteChannel::GetList($by, $order, $filter, $isFiltered));
    }

    public function getVotesList(array $filter = []): array
    {
        $by = 's_c_sort';
        $order = 'asc';
        $isFiltered = null;

        return $this->fetchAll(CVote::GetList($by, $order, $filter, $isFiltered));
    }

    /**
     * @throws HelperException
     */
    public function exportChannelById(int $channelId): array
    {
        $channel = $this->getChannelById($channelId);
        $channel['SITE'] = $this->exportChannelSites($channelId);
        $channel['GROUP_ID'] = $this->exportChannelPermissions($channelId);

        return $this->export(
            $channel,
            $this->getDefaultChannel(),
            []
        );
    }

    /**
     * @throws HelperException
     */
    public function exportVoteById(int $voteId): array
    {
        return $this->export(
            $this->getVoteById($voteId),
            $this->getDefaultVote(),
            []
        );
    }

    public function exportQuestions(int $voteId): array
    {
        return array_map(function ($question) {
            $question['ANSWERS'] = $this->exportCollection(
                $this->getAnswers((int)$question['ID']),
                $this->getDefaultAnswer(),
                [
                    'ID',
                    'QUESTION_ID',
                ]
            );

            return $this->export(
                $question,
                $this->getDefaultQuestion(),
                [
                    'ID',
                    'VOTE_ID',
                ]
            );
        }, $this->getQuestions($voteId));
    }

    /**
     * @throws HelperException
     */
    public function saveChannel(array $channel): int
    {
        $this->checkRequiredKeys($channel, ['SYMBOLIC_NAME', 'TITLE', 'SITE']);

        $channel = $this->merge($channel, $this->getDefaultChannel());
        if (empty($channel['FIRST_SITE_ID']) && !empty($channel['SITE'][0])) {
            $channel['FIRST_SITE_ID'] = $channel['SITE'][0];
        }

        $channelForSave = $channel;
        $channelForSave['GROUP_ID'] = $this->revertChannelPermissions($channel['GROUP_ID'] ?? []);

        $channelId = $this->getChannelId($channel['SYMBOLIC_NAME'], $channel['SITE']);
        if ($channelId) {
            return $this->updateChannel($channelId, $channelForSave, $channel);
        }

        return $this->addChannel($channelForSave);
    }

    public function getChannelId(string $symbolicName, array $sites = []): int
    {
        $filter = [
            '=SYMBOLIC_NAME' => strtoupper($symbolicName),
        ];

        if (!empty($sites)) {
            $filter['=SITE.SITE_ID'] = $sites;
        }

        $channel = ChannelTable::getList([
            'select' => ['ID'],
            'filter' => $filter,
            'order' => ['ID' => 'ASC'],
            'limit' => 1,
        ])->fetch();

        return (int)($channel['ID'] ?? 0);
    }

    /**
     * @throws HelperException
     */
    public function saveVote(int $channelId, array $vote): int
    {
        $this->checkRequiredKeys($vote, ['DATE_START', 'DATE_END']);

        $vote = $this->merge($vote, $this->getDefaultVote());
        $vote['CHANNEL_ID'] = $channelId;

        $voteId = $this->getVoteId($channelId, (string)($vote['TITLE'] ?? ''), (string)$vote['DATE_START']);
        if ($voteId) {
            return $this->updateVote($voteId, $vote);
        }

        return $this->addVote($vote);
    }

    public function getVoteId(int $channelId, string $title = '', string $dateStart = ''): int
    {
        foreach ($this->getVotesList(['CHANNEL_ID' => $channelId]) as $vote) {
            if ($title !== '' && $vote['TITLE'] == $title) {
                return (int)$vote['ID'];
            }

            if ($title === '' && $dateStart !== '' && $vote['DATE_START'] == $dateStart) {
                return (int)$vote['ID'];
            }
        }

        return 0;
    }

    /**
     * @throws HelperException
     */
    public function saveQuestions(int $voteId, array $questions, bool $deleteOldQuestions = true): array
    {
        $currentQuestions = $this->getQuestions($voteId);
        $updatedIds = [];

        foreach ($questions as $question) {
            $this->checkRequiredKeys($question, ['QUESTION']);

            $questionId = $this->findByValue($currentQuestions, $updatedIds, 'QUESTION', $question['QUESTION']);
            $updatedIds[] = $this->replaceQuestion($voteId, $questionId, $question);
        }

        if ($deleteOldQuestions) {
            foreach ($currentQuestions as $currentQuestion) {
                if (!in_array((int)$currentQuestion['ID'], $updatedIds)) {
                    CVoteQuestion::Delete((int)$currentQuestion['ID'], $voteId);
                }
            }
        }

        return $updatedIds;
    }

    /**
     * @throws HelperException
     */
    public function getChannelById(int $channelId): array
    {
        $channel = (new CVoteChannel())->GetByID($channelId)->Fetch();
        if (empty($channel)) {
            throw new HelperException("Vote channel \"$channelId\" not found");
        }

        return $this->filterKeys($channel, $this->getChannelKeys());
    }

    /**
     * @throws HelperException
     */
    public function getVoteById(int $voteId): array
    {
        $vote = CVote::GetByID($voteId)->Fetch();
        if (empty($vote)) {
            throw new HelperException("Vote \"$voteId\" not found");
        }

        return $this->filterKeys($vote, $this->getVoteKeys());
    }

    public function getQuestions(int $voteId): array
    {
        $by = 's_c_sort';
        $order = 'asc';
        $isFiltered = null;

        return array_map(
            fn($question) => $this->filterKeys($question, $this->getQuestionKeys()),
            $this->fetchAll(CVoteQuestion::GetList($voteId, $by, $order, [], $isFiltered))
        );
    }

    public function getAnswers(int $questionId): array
    {
        return array_map(
            fn($answer) => $this->filterKeys($answer, $this->getAnswerKeys()),
            $this->fetchAll(CVoteAnswer::GetList($questionId))
        );
    }

    /**
     * @throws HelperException
     */
    protected function addChannel(array $channel): int
    {
        $channelId = CVoteChannel::Add($channel);
        if (empty($channelId)) {
            $this->throwApplicationExceptionIfExists();
            throw new HelperException($GLOBALS['strError'] ?? 'Vote channel not added');
        }

        $this->outNotice("Vote channel $channelId created");
        return (int)$channelId;
    }

    /**
     * @throws HelperException
     */
    protected function updateChannel(int $channelId, array $channel, array $compareChannel): int
    {
        $exists = $this->exportChannelById($channelId);
        $export = $this->export($compareChannel, $this->getDefaultChannel(), []);

        if ($this->checkDiff($exists, $export)) {
            $newId = (new CVoteChannel())->Update($channelId, $channel);
            if (empty($newId)) {
                $this->throwApplicationExceptionIfExists();
                throw new HelperException($GLOBALS['strError'] ?? 'Vote channel not updated');
            }

            $this->outNotice("Vote channel $channelId updated");
        }

        return $channelId;
    }

    /**
     * @throws HelperException
     */
    protected function addVote(array $vote): int
    {
        $voteId = CVote::Add($vote);
        if (empty($voteId)) {
            $this->throwApplicationExceptionIfExists();
            throw new HelperException($GLOBALS['strError'] ?? 'Vote not added');
        }

        $this->outNotice("Vote $voteId created");
        return (int)$voteId;
    }

    /**
     * @throws HelperException
     */
    protected function updateVote(int $voteId, array $vote): int
    {
        $exists = $this->exportVoteById($voteId);
        $export = $this->export($vote, $this->getDefaultVote(), []);

        if ($this->checkDiff($exists, $export)) {
            $newId = CVote::Update($voteId, $vote);
            if (empty($newId)) {
                $this->throwApplicationExceptionIfExists();
                throw new HelperException($GLOBALS['strError'] ?? 'Vote not updated');
            }

            $this->outNotice("Vote $voteId updated");
        }

        return $voteId;
    }

    /**
     * @throws HelperException
     */
    private function replaceQuestion(int $voteId, int $questionId, array $question): int
    {
        $answers = [];
        if (isset($question['ANSWERS'])) {
            $answers = is_array($question['ANSWERS']) ? $question['ANSWERS'] : [];
            unset($question['ANSWERS']);
        }

        $question = $this->merge($question, $this->getDefaultQuestion());
        $question['VOTE_ID'] = $voteId;

        if ($questionId) {
            $newId = CVoteQuestion::Update($questionId, $question);
        } else {
            $newId = CVoteQuestion::Add($question);
        }

        if (empty($newId)) {
            $this->throwApplicationExceptionIfExists();
            throw new HelperException($GLOBALS['strError'] ?? 'Vote question not saved');
        }

        $this->saveAnswers((int)$newId, $answers);
        $this->outNotice($questionId ? "Vote question $newId updated" : "Vote question $newId created");

        return (int)$newId;
    }

    /**
     * @throws HelperException
     */
    private function saveAnswers(int $questionId, array $answers): array
    {
        $currentAnswers = $this->getAnswers($questionId);
        $updatedIds = [];

        foreach ($answers as $answer) {
            $this->checkRequiredKeys($answer, ['MESSAGE']);

            $answerId = $this->findAnswer($currentAnswers, $updatedIds, $answer);
            $answer = $this->merge($answer, $this->getDefaultAnswer());
            $answer['QUESTION_ID'] = $questionId;

            if ($answerId) {
                $newId = (new CVoteAnswer())->Update($answerId, $answer);
            } else {
                $newId = CVoteAnswer::Add($answer);
            }

            if (empty($newId)) {
                $this->throwApplicationExceptionIfExists();
                throw new HelperException($GLOBALS['strError'] ?? 'Vote answer not saved');
            }

            $updatedIds[] = (int)$newId;
        }

        foreach ($currentAnswers as $currentAnswer) {
            if (!in_array((int)$currentAnswer['ID'], $updatedIds)) {
                CVoteAnswer::Delete((int)$currentAnswer['ID'], $questionId);
            }
        }

        return $updatedIds;
    }

    private function exportChannelSites(int $channelId): array
    {
        $sites = (new CVoteChannel())->GetSiteArray($channelId);
        return is_array($sites) ? $sites : [];
    }

    private function exportChannelPermissions(int $channelId): array
    {
        $groupHelper = new UserGroupHelper();
        $permissions = [];
        $groupPermissions = (new CVoteChannel())->GetArrayGroupPermission($channelId);

        foreach ($groupPermissions as $groupId => $permission) {
            $groupCode = $groupHelper->getGroupCode($groupId);
            if ($groupCode) {
                $permissions[$groupCode] = (int)$permission;
            }
        }

        return $permissions;
    }

    private function revertChannelPermissions(array $permissions): array
    {
        $groupHelper = new UserGroupHelper();
        $result = [];

        foreach ($permissions as $groupCode => $permission) {
            $groupId = $groupHelper->getGroupId($groupCode);
            if ($groupId) {
                $result[$groupId] = (int)$permission;
            }
        }

        return $result;
    }

    private function findByValue(array $items, array $usedIds, string $key, mixed $value): int
    {
        foreach ($items as $item) {
            if (!in_array((int)$item['ID'], $usedIds) && $item[$key] == $value) {
                return (int)$item['ID'];
            }
        }

        return 0;
    }

    private function findAnswer(array $items, array $usedIds, array $answer): int
    {
        foreach ($items as $item) {
            if (
                !in_array((int)$item['ID'], $usedIds)
                && $item['MESSAGE'] == $answer['MESSAGE']
                && (int)$item['FIELD_TYPE'] == (int)($answer['FIELD_TYPE'] ?? 0)
            ) {
                return (int)$item['ID'];
            }
        }

        return 0;
    }

    private function filterKeys(array $item, array $keys): array
    {
        return array_intersect_key($item, array_flip($keys));
    }

    private function getChannelKeys(): array
    {
        return [
            'SYMBOLIC_NAME',
            'C_SORT',
            'FIRST_SITE_ID',
            'ACTIVE',
            'HIDDEN',
            'TITLE',
            'VOTE_SINGLE',
            'USE_CAPTCHA',
        ];
    }

    private function getVoteKeys(): array
    {
        return [
            'CHANNEL_ID',
            'C_SORT',
            'ACTIVE',
            'NOTIFY',
            'DATE_START',
            'DATE_END',
            'URL',
            'TITLE',
            'DESCRIPTION',
            'DESCRIPTION_TYPE',
            'EVENT1',
            'EVENT2',
            'EVENT3',
            'UNIQUE_TYPE',
            'KEEP_IP_SEC',
            'TEMPLATE',
            'RESULT_TEMPLATE',
        ];
    }

    private function getQuestionKeys(): array
    {
        return [
            'ID',
            'ACTIVE',
            'VOTE_ID',
            'C_SORT',
            'QUESTION',
            'QUESTION_TYPE',
            'DIAGRAM',
            'REQUIRED',
            'DIAGRAM_TYPE',
            'TEMPLATE',
            'TEMPLATE_NEW',
        ];
    }

    private function getAnswerKeys(): array
    {
        return [
            'ID',
            'ACTIVE',
            'QUESTION_ID',
            'C_SORT',
            'MESSAGE',
            'MESSAGE_TYPE',
            'FIELD_TYPE',
            'FIELD_WIDTH',
            'FIELD_HEIGHT',
            'FIELD_PARAM',
            'COLOR',
        ];
    }

    private function getDefaultChannel(): array
    {
        return [
            'C_SORT' => '100',
            'FIRST_SITE_ID' => null,
            'ACTIVE' => 'Y',
            'HIDDEN' => 'N',
            'VOTE_SINGLE' => 'Y',
            'USE_CAPTCHA' => 'N',
            'GROUP_ID' => [],
        ];
    }

    private function getDefaultVote(): array
    {
        return [
            'C_SORT' => '100',
            'ACTIVE' => 'Y',
            'NOTIFY' => 'N',
            'URL' => null,
            'DESCRIPTION' => '',
            'DESCRIPTION_TYPE' => 'html',
            'EVENT1' => '',
            'EVENT2' => '',
            'EVENT3' => '',
            'UNIQUE_TYPE' => '2',
            'KEEP_IP_SEC' => null,
            'TEMPLATE' => '',
            'RESULT_TEMPLATE' => '',
        ];
    }

    private function getDefaultQuestion(): array
    {
        return [
            'ACTIVE' => 'Y',
            'C_SORT' => '100',
            'QUESTION_TYPE' => 'html',
            'DIAGRAM' => 'Y',
            'REQUIRED' => 'N',
            'DIAGRAM_TYPE' => 'histogram',
            'TEMPLATE' => '',
            'TEMPLATE_NEW' => '',
        ];
    }

    private function getDefaultAnswer(): array
    {
        return [
            'ACTIVE' => 'Y',
            'C_SORT' => '100',
            'MESSAGE' => ' ',
            'MESSAGE_TYPE' => 'html',
            'FIELD_TYPE' => '0',
            'FIELD_WIDTH' => '0',
            'FIELD_HEIGHT' => '0',
            'FIELD_PARAM' => '',
            'COLOR' => '',
        ];
    }
}
