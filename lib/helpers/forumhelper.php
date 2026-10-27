<?php

namespace Sprint\Migration\Helpers;

use CForumGroup;
use CForumNew;
use CLanguage;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;

class ForumHelper extends Helper
{
    public function isEnabled(): bool
    {
        return $this->checkModules(['forum']);
    }

    /**
     * @throws HelperException
     */
    public function getGroups(): array
    {
        $items = [];

        foreach ($this->getForumGroupsRaw() as $item) {
            $langs = $this->getGroupLangs((int)$item['ID']);
            $item['NAME'] = $this->getFirstLangName($langs);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @throws HelperException
     */
    public function getForums(array $filter = []): array
    {
        return $this->fetchAll(CForumNew::GetList(
            [
                'FORUM_GROUP_ID' => 'ASC',
                'SORT'           => 'ASC',
                'NAME'           => 'ASC',
                'ID'             => 'ASC',
            ],
            $filter
        ));
    }

    /**
     * @throws HelperException
     */
    public function ensureGroupXmlIds(array $items): array
    {
        foreach ($items as $index => $item) {
            if (empty($item['XML_ID'])) {
                $items[$index] = $this->ensureGroupXmlId($item);
            }
        }

        return $items;
    }

    /**
     * @throws HelperException
     */
    public function ensureGroupXmlId(array $item): array
    {
        $this->checkRequiredKeys($item, ['ID']);
        $this->ensureGroupXmlIdColumn();

        if (!empty($item['XML_ID'])) {
            return $item;
        }

        if (empty($item['NAME'])) {
            $item['NAME'] = $this->getFirstLangName($this->getGroupLangs((int)$item['ID']));
        }

        $item['XML_ID'] = $this->makeGroupXmlId($item);
        $result = CForumGroup::Update((int)$item['ID'], [
            'XML_ID'    => $item['XML_ID'],
            'PARENT_ID' => $item['PARENT_ID'] ?? false,
        ]);

        if (!$result) {
            $this->throwApplicationExceptionIfExists();
            throw new HelperException("Forum group \"{$item['ID']}\" XML_ID not updated");
        }

        return $item;
    }

    /**
     * @throws HelperException
     */
    public function ensureForumXmlIds(array $items): array
    {
        foreach ($items as $index => $item) {
            if (empty($item['XML_ID'])) {
                $items[$index] = $this->ensureForumXmlId($item);
            }
        }

        return $items;
    }

    /**
     * @throws HelperException
     */
    public function ensureForumXmlId(array $item): array
    {
        $this->checkRequiredKeys($item, ['ID', 'NAME']);

        if (!empty($item['XML_ID'])) {
            return $item;
        }

        $item['XML_ID'] = $this->makeForumXmlId($item);
        $result = CForumNew::Update((int)$item['ID'], ['XML_ID' => $item['XML_ID']], false);

        if (!$result) {
            $this->throwApplicationExceptionIfExists();
            throw new HelperException("Forum \"{$item['ID']}\" XML_ID not updated");
        }

        return $item;
    }

    /**
     * @throws HelperException
     */
    public function exportGroupById(int $groupId): array
    {
        return $this->prepareExportGroup($this->getGroupById($groupId));
    }

    /**
     * @throws HelperException
     */
    public function exportGroups(array $groupIds): array
    {
        return array_map(
            fn($groupId) => $this->exportGroupById((int)$groupId),
            $this->makeNonEmptyArray($groupIds)
        );
    }

    /**
     * @throws HelperException
     */
    public function exportForumById(int $forumId): array
    {
        return $this->prepareExportForum($this->getForumById($forumId));
    }

    /**
     * @throws HelperException
     */
    public function exportForums(array $forumIds): array
    {
        return array_map(
            fn($forumId) => $this->exportForumById((int)$forumId),
            $this->makeNonEmptyArray($forumIds)
        );
    }

    /**
     * @throws HelperException
     */
    public function saveGroup(array $fields): int
    {
        $this->checkRequiredKeys($fields, ['XML_ID', 'LANG']);
        $this->ensureGroupXmlIdColumn();

        $fields = $this->prepareGroupFields($fields);
        $groupId = $this->getGroupId($fields['XML_ID']);

        if (!$groupId) {
            return $this->addGroup($fields);
        }

        $exists = $this->exportGroupById($groupId);
        $export = $this->prepareExportGroup($fields);

        if ($this->checkDiff($exists, $export)) {
            return $this->updateGroup($groupId, $fields);
        }

        return $groupId;
    }

    /**
     * @throws HelperException
     */
    public function saveForum(array $fields): int
    {
        $this->checkRequiredKeys($fields, ['XML_ID', 'NAME', 'SITES']);

        $fields = $this->prepareForumFields($fields);
        $forumId = $this->getForumId($fields['XML_ID']);

        if (!$forumId) {
            return $this->addForum($fields);
        }

        $exists = $this->exportForumById($forumId);
        $export = $this->prepareExportForum($fields);

        if ($this->checkDiff($exists, $export)) {
            return $this->updateForum($forumId, $fields);
        }

        return $forumId;
    }

    /**
     * @throws HelperException
     */
    protected function addGroup(array $fields): int
    {
        $fields = $this->prepareGroupFieldsForSave($fields);
        $groupId = CForumGroup::Add($fields);

        if ($groupId) {
            $this->outNotice(Locale::getMessage('FORUM_GROUP_UPDATED', ['#NAME#' => $this->getFirstLangName($fields['LANG'])]));
            return (int)$groupId;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException("Forum group \"{$fields['XML_ID']}\" not added");
    }

    /**
     * @throws HelperException
     */
    protected function updateGroup(int $groupId, array $fields): int
    {
        $fields = $this->prepareGroupFieldsForSave($fields);
        $result = CForumGroup::Update($groupId, $fields);

        if ($result) {
            $this->outNotice(Locale::getMessage('FORUM_GROUP_UPDATED', ['#NAME#' => $this->getFirstLangName($fields['LANG'])]));
            return (int)$result;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException("Forum group \"{$fields['XML_ID']}\" not updated");
    }

    /**
     * @throws HelperException
     */
    protected function addForum(array $fields): int
    {
        $fields = $this->prepareForumFieldsForSave($fields);
        $forumId = CForumNew::Add($fields);

        if ($forumId) {
            $this->outNotice(Locale::getMessage('FORUM_UPDATED', ['#NAME#' => $fields['NAME']]));
            return (int)$forumId;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException("Forum \"{$fields['XML_ID']}\" not added");
    }

    /**
     * @throws HelperException
     */
    protected function updateForum(int $forumId, array $fields): int
    {
        $fields = $this->prepareForumFieldsForSave($fields);
        $result = CForumNew::Update($forumId, $fields, false);

        if ($result) {
            $this->outNotice(Locale::getMessage('FORUM_UPDATED', ['#NAME#' => $fields['NAME']]));
            return (int)$result;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException("Forum \"{$fields['XML_ID']}\" not updated");
    }

    /**
     * @throws HelperException
     */
    protected function getGroupById(int $groupId): array
    {
        $this->ensureGroupXmlIdColumn();

        $item = $this->queryFetch("SELECT ID, SORT, PARENT_ID, LEFT_MARGIN, RIGHT_MARGIN, DEPTH_LEVEL, XML_ID FROM b_forum_group WHERE ID = " . $groupId);
        if (empty($item)) {
            throw new HelperException("Forum group \"$groupId\" not found");
        }

        $item = $this->ensureGroupXmlId($item);
        $item['LANG'] = $this->getGroupLangs($groupId);

        return $item;
    }

    /**
     * @throws HelperException
     */
    protected function getForumById(int $forumId): array
    {
        $item = CForumNew::GetByID($forumId);
        if (empty($item)) {
            throw new HelperException("Forum \"$forumId\" not found");
        }

        $item = $this->ensureForumXmlId($item);
        $item['SITES'] = CForumNew::GetSites($forumId) ?: [];
        $item['PERMISSIONS'] = $this->exportForumPermissions($forumId);

        return $item;
    }

    /**
     * @throws HelperException
     */
    protected function getGroupId(string $xmlId): int
    {
        $this->ensureGroupXmlIdColumn();

        $item = $this->queryFetch("SELECT ID FROM b_forum_group WHERE XML_ID = '" . $this->forSql($xmlId) . "' ORDER BY ID ASC LIMIT 1");
        return (int)($item['ID'] ?? 0);
    }

    protected function getForumId(string $xmlId): int
    {
        $item = CForumNew::GetList(['ID' => 'ASC'], ['XML_ID' => $xmlId])->Fetch();
        return (int)($item['ID'] ?? 0);
    }

    /**
     * @throws HelperException
     */
    protected function prepareExportGroup(array $item): array
    {
        if (!empty($item['ID']) && empty($item['XML_ID'])) {
            $item = $this->ensureGroupXmlId($item);
        }

        if (empty($item['LANG']) && !empty($item['ID'])) {
            $item['LANG'] = $this->getGroupLangs((int)$item['ID']);
        }

        if (!empty($item['PARENT_ID'])) {
            $item['PARENT'] = $this->exportGroupById((int)$item['PARENT_ID']);
        }

        $item = $this->prepareGroupFields($item);

        return $this->export(
            $item,
            $this->getDefaultGroup(),
            ['ID', 'PARENT_ID', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'DEPTH_LEVEL']
        );
    }

    /**
     * @throws HelperException
     */
    protected function prepareGroupFields(array $item): array
    {
        $item = array_merge($this->getDefaultGroup(), $item);
        $item['SORT'] = (string)$item['SORT'];
        $item['LANG'] = array_values($item['LANG']);

        return array_intersect_key(
            $item,
            array_flip([
                'ID',
                'XML_ID',
                'SORT',
                'PARENT_ID',
                'PARENT',
                'LEFT_MARGIN',
                'RIGHT_MARGIN',
                'DEPTH_LEVEL',
                'LANG',
            ])
        );
    }

    /**
     * @throws HelperException
     */
    protected function prepareGroupFieldsForSave(array $item): array
    {
        $item = $this->prepareGroupFields($item);

        if (!empty($item['PARENT'])) {
            $item['PARENT_ID'] = $this->saveGroup($item['PARENT']);
        }

        unset($item['ID'], $item['PARENT'], $item['LEFT_MARGIN'], $item['RIGHT_MARGIN'], $item['DEPTH_LEVEL']);

        if (empty($item['PARENT_ID'])) {
            $item['PARENT_ID'] = false;
        }

        $item['LANG'] = $this->fillMissingGroupLangs($item['LANG']);

        return $item;
    }

    /**
     * @throws HelperException
     */
    protected function prepareExportForum(array $item): array
    {
        if (!empty($item['ID']) && empty($item['XML_ID'])) {
            $item = $this->ensureForumXmlId($item);
        }

        if (!isset($item['SITES']) && !empty($item['ID'])) {
            $item['SITES'] = CForumNew::GetSites((int)$item['ID']) ?: [];
        }

        if (!isset($item['PERMISSIONS']) && !empty($item['ID'])) {
            $item['PERMISSIONS'] = $this->exportForumPermissions((int)$item['ID']);
        }

        if (!empty($item['GROUP'])) {
            $item['FORUM_GROUP_ID'] = $this->saveGroup($item['GROUP']);
        }

        if (!empty($item['FORUM_GROUP_ID'])) {
            $item['GROUP'] = $this->exportGroupById((int)$item['FORUM_GROUP_ID']);
        }

        $item = $this->prepareForumFields($item);

        return $this->export(
            $item,
            $this->getDefaultForum(),
            [
                'ID',
                'LID',
                'PATH2FORUM_MESSAGE',
                'FORUM_GROUP_ID',
                'TOPICS',
                'POSTS',
                'LAST_POSTER_ID',
                'LAST_POSTER_NAME',
                'LAST_POST_DATE',
                'LAST_MESSAGE_ID',
                'MID',
                'POSTS_UNAPPROVED',
                'ABS_LAST_POSTER_ID',
                'ABS_LAST_POSTER_NAME',
                'ABS_LAST_POST_DATE',
                'ABS_LAST_MESSAGE_ID',
            ]
        );
    }

    protected function prepareForumFields(array $item): array
    {
        $item = array_merge($this->getDefaultForum(), $item);
        $item['SORT'] = (string)$item['SORT'];
        $item['SITES'] = is_array($item['SITES']) ? $item['SITES'] : [];
        $item['PERMISSIONS'] = is_array($item['PERMISSIONS']) ? $item['PERMISSIONS'] : [];

        ksort($item['SITES']);
        ksort($item['PERMISSIONS']);

        return array_intersect_key(
            $item,
            array_flip([
                'ID',
                'XML_ID',
                'FORUM_GROUP_ID',
                'GROUP',
                'NAME',
                'DESCRIPTION',
                'SORT',
                'ACTIVE',
                'ALLOW_HTML',
                'ALLOW_ANCHOR',
                'ALLOW_BIU',
                'ALLOW_IMG',
                'ALLOW_VIDEO',
                'ALLOW_LIST',
                'ALLOW_QUOTE',
                'ALLOW_CODE',
                'ALLOW_FONT',
                'ALLOW_SMILES',
                'ALLOW_UPLOAD',
                'ALLOW_TABLE',
                'ALLOW_ALIGN',
                'ALLOW_UPLOAD_EXT',
                'ALLOW_MOVE_TOPIC',
                'ALLOW_TOPIC_TITLED',
                'ALLOW_NL2BR',
                'ALLOW_SIGNATURE',
                'ASK_GUEST_EMAIL',
                'USE_CAPTCHA',
                'INDEXATION',
                'DEDUPLICATION',
                'MODERATION',
                'ORDER_BY',
                'ORDER_DIRECTION',
                'EVENT1',
                'EVENT2',
                'EVENT3',
                'HTML',
                'SITES',
                'PERMISSIONS',
                'LID',
                'PATH2FORUM_MESSAGE',
                'TOPICS',
                'POSTS',
                'LAST_POSTER_ID',
                'LAST_POSTER_NAME',
                'LAST_POST_DATE',
                'LAST_MESSAGE_ID',
                'MID',
                'POSTS_UNAPPROVED',
                'ABS_LAST_POSTER_ID',
                'ABS_LAST_POSTER_NAME',
                'ABS_LAST_POST_DATE',
                'ABS_LAST_MESSAGE_ID',
            ])
        );
    }

    /**
     * @throws HelperException
     */
    protected function prepareForumFieldsForSave(array $item): array
    {
        $item = $this->prepareForumFields($item);

        if (!empty($item['GROUP'])) {
            $item['FORUM_GROUP_ID'] = $this->saveGroup($item['GROUP']);
        }

        $item['GROUP_ID'] = $this->revertForumPermissions($item['PERMISSIONS']);

        unset(
            $item['ID'],
            $item['GROUP'],
            $item['PERMISSIONS'],
            $item['LID'],
            $item['PATH2FORUM_MESSAGE'],
            $item['TOPICS'],
            $item['POSTS'],
            $item['LAST_POSTER_ID'],
            $item['LAST_POSTER_NAME'],
            $item['LAST_POST_DATE'],
            $item['LAST_MESSAGE_ID'],
            $item['MID'],
            $item['POSTS_UNAPPROVED'],
            $item['ABS_LAST_POSTER_ID'],
            $item['ABS_LAST_POSTER_NAME'],
            $item['ABS_LAST_POST_DATE'],
            $item['ABS_LAST_MESSAGE_ID']
        );

        if (empty($item['FORUM_GROUP_ID'])) {
            $item['FORUM_GROUP_ID'] = false;
        }

        return $item;
    }

    protected function getDefaultGroup(): array
    {
        return [
            'SORT'      => '150',
            'PARENT_ID' => false,
            'LANG'      => [],
        ];
    }

    protected function getDefaultForum(): array
    {
        return [
            'DESCRIPTION'         => null,
            'SORT'                => '150',
            'ACTIVE'              => 'Y',
            'ALLOW_HTML'          => 'N',
            'ALLOW_ANCHOR'        => 'Y',
            'ALLOW_BIU'           => 'Y',
            'ALLOW_IMG'           => 'Y',
            'ALLOW_VIDEO'         => 'Y',
            'ALLOW_LIST'          => 'Y',
            'ALLOW_QUOTE'         => 'Y',
            'ALLOW_CODE'          => 'Y',
            'ALLOW_FONT'          => 'Y',
            'ALLOW_SMILES'        => 'Y',
            'ALLOW_UPLOAD'        => 'N',
            'ALLOW_TABLE'         => 'N',
            'ALLOW_ALIGN'         => 'Y',
            'ALLOW_UPLOAD_EXT'    => null,
            'ALLOW_MOVE_TOPIC'    => 'Y',
            'ALLOW_TOPIC_TITLED'  => 'N',
            'ALLOW_NL2BR'         => 'N',
            'ALLOW_SIGNATURE'     => 'Y',
            'ASK_GUEST_EMAIL'     => 'N',
            'USE_CAPTCHA'         => 'N',
            'INDEXATION'          => 'Y',
            'DEDUPLICATION'       => 'Y',
            'MODERATION'          => 'N',
            'ORDER_BY'            => 'P',
            'ORDER_DIRECTION'     => 'DESC',
            'EVENT1'              => 'forum',
            'EVENT2'              => 'message',
            'EVENT3'              => null,
            'HTML'                => null,
            'SITES'               => [],
            'PERMISSIONS'         => [],
        ];
    }

    /**
     * @throws HelperException
     */
    protected function getForumGroupsRaw(): array
    {
        $this->ensureGroupXmlIdColumn();

        return $this->queryFetchAll(
            'SELECT ID, SORT, PARENT_ID, LEFT_MARGIN, RIGHT_MARGIN, DEPTH_LEVEL, XML_ID
            FROM b_forum_group
            ORDER BY LEFT_MARGIN ASC, SORT ASC, ID ASC'
        );
    }

    protected function getGroupLangs(int $groupId): array
    {
        return $this->queryFetchAll(
            'SELECT LID, NAME, DESCRIPTION
            FROM b_forum_group_lang
            WHERE FORUM_GROUP_ID = ' . $groupId . '
            ORDER BY LID ASC'
        );
    }

    protected function getFirstLangName(array $langs): string
    {
        $preferred = defined('LANGUAGE_ID') ? LANGUAGE_ID : 'ru';

        foreach ([$preferred, 'ru'] as $lid) {
            foreach ($langs as $lang) {
                if (($lang['LID'] ?? '') === $lid && !empty($lang['NAME'])) {
                    return (string)$lang['NAME'];
                }
            }
        }

        $lang = reset($langs);
        return (string)($lang['NAME'] ?? '');
    }

    protected function fillMissingGroupLangs(array $langs): array
    {
        $activeLangIds = $this->getActiveLangIds();
        if (empty($activeLangIds) || empty($langs)) {
            return $langs;
        }

        $indexed = [];
        foreach ($langs as $lang) {
            if (!empty($lang['LID'])) {
                $indexed[$lang['LID']] = $lang;
            }
        }

        $default = reset($langs);
        foreach ($activeLangIds as $lid) {
            if (empty($indexed[$lid])) {
                $indexed[$lid] = [
                    'LID'         => $lid,
                    'NAME'        => (string)($default['NAME'] ?? ''),
                    'DESCRIPTION' => (string)($default['DESCRIPTION'] ?? ''),
                ];
            }
        }

        ksort($indexed);
        return array_values($indexed);
    }

    protected function getActiveLangIds(): array
    {
        $by = 'sort';
        $order = 'asc';
        $items = [];

        $dbres = CLanguage::GetList($by, $order, ['ACTIVE' => 'Y']);
        while ($item = $dbres->Fetch()) {
            $items[] = $item['LID'];
        }

        return $items;
    }

    protected function exportForumPermissions(int $forumId): array
    {
        $groupHelper = new UserGroupHelper();
        $permissions = [];

        foreach (CForumNew::GetAccessPermissions($forumId) as $permission) {
            $groupCode = $groupHelper->getGroupCode((int)$permission[0]);
            if ($groupCode) {
                $permissions[$groupCode] = (string)$permission[1];
            }
        }

        ksort($permissions);
        return $permissions;
    }

    /**
     * @throws HelperException
     */
    protected function revertForumPermissions(array $permissions): array
    {
        $groupHelper = new UserGroupHelper();
        $result = [];

        foreach ($permissions as $groupCode => $permission) {
            $groupId = $groupHelper->getGroupIdIfExists((string)$groupCode);
            $result[$groupId] = (string)$permission;
        }

        return $result;
    }

    /**
     * @throws HelperException
     */
    protected function ensureGroupXmlIdColumn(): void
    {
        $sqlHelper = new SqlHelper();

        if (!$sqlHelper->hasColumn('b_forum_group', 'XML_ID')) {
            $sqlHelper->query('ALTER TABLE b_forum_group ADD XML_ID varchar(255) NULL');
        }
    }

    protected function makeGroupXmlId(array $item): string
    {
        $slug = $this->makeCodeSlug((string)($item['NAME'] ?? ''));
        if ($slug === '') {
            $slug = 'GROUP';
        }

        return sprintf('FORUM_GROUP_%d_%s', (int)$item['ID'], $slug);
    }

    protected function makeForumXmlId(array $item): string
    {
        $slug = $this->makeCodeSlug((string)($item['NAME'] ?? ''));
        if ($slug === '') {
            $slug = 'FORUM';
        }

        return sprintf('FORUM_%d_%s', (int)$item['ID'], $slug);
    }

    protected function makeCodeSlug(string $value): string
    {
        if (class_exists('CUtil')) {
            $value = \CUtil::translit($value, 'ru', [
                'replace_space' => '_',
                'replace_other' => '_',
                'change_case'   => 'U',
            ]);
        }

        $value = strtoupper($value);
        $value = preg_replace('/[^A-Z0-9_]+/', '_', $value);
        $value = trim((string)$value, '_');

        return preg_replace('/_+/', '_', $value);
    }

    protected function queryFetch(string $sql): bool|array
    {
        $res = $this->queryFetchAll($sql);
        return $res[0] ?? false;
    }

    protected function queryFetchAll(string $sql): array
    {
        global $DB;

        $items = [];
        $dbres = $DB->Query($sql);
        while ($item = $dbres->Fetch()) {
            $items[] = $item;
        }

        return $items;
    }

    protected function forSql(string $value, int $maxLength = 0): string
    {
        global $DB;

        return $DB->ForSql($value, $maxLength);
    }
}
