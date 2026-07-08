<?php

namespace Sprint\Migration\Helpers;

use CBlog;
use CBlogCategory;
use CBlogGroup;
use CBlogPost;
use CBlogPostCategory;
use CBlogUserGroup;
use CUserFieldEnum;
use CUserTypeEntity;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;

class BlogHelper extends Helper
{

    protected UserHelper $userHelper;

    public function __construct()
    {
        $this->userHelper = new UserHelper();
    }

    public function isEnabled(): bool
    {
        return $this->checkModules(['blog']);
    }

    public function getGroups(array $filter = []): array
    {
        return $this->fetchAll(CBlogGroup::GetList(
            [
                'SITE_ID' => 'ASC',
                'NAME'    => 'ASC',
                'ID'      => 'ASC',
            ],
            $filter,
            false,
            false,
            [
                'ID',
                'SITE_ID',
                'NAME'
            ]
        ));
    }

    public function getBlogs(array $filter = []): array
    {
        return $this->fetchAll(CBlog::GetList(
            [
                'GROUP_SITE_ID' => 'ASC',
                'GROUP_NAME'    => 'ASC',
                'NAME'          => 'ASC',
                'ID'            => 'ASC',
            ],
            $filter,
            false,
            false,
            [
                'ID',
                'NAME',
                'URL',
                'GROUP_ID',
                'GROUP_NAME',
                'GROUP_SITE_ID',
                'OWNER_ID',
                'OWNER_LOGIN',
                'ACTIVE',
            ]
        ));
    }

    public function getPosts(int $blogId, array $filter = []): array
    {
        $filter['BLOG_ID'] = $blogId;

        return $this->fetchAll(CBlogPost::GetList(
            [
                'DATE_PUBLISH' => 'DESC',
                'ID'           => 'DESC',
            ],
            $filter,
            false,
            false,
            [
                'ID',
                'BLOG_ID',
                'TITLE',
                'CODE',
                'AUTHOR_ID',
                'AUTHOR_LOGIN',
                'DATE_PUBLISH',
                'PUBLISH_STATUS',
            ]
        ));
    }

    /**
     * @throws HelperException
     */
    public function exportGroupById(int $groupId): array
    {
        $group = $this->getGroupById($groupId);

        return $this->export(
            $group,
            $this->getDefaultGroup(),
            ['ID']
        );
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
    public function exportBlogById(int $blogId): array
    {
        $blog = $this->getBlogById($blogId);

        if ((int)($blog['SOCNET_GROUP_ID'] ?? 0) > 0) {
            throw new HelperException("Socialnetwork blog \"$blogId\" is not supported");
        }

        $blog['OWNER_LOGIN'] = $this->userHelper->getUserLoginById((int)$blog['OWNER_ID']);

        $blog['USER_GROUPS'] = $this->exportUserGroups($blog);

        return $this->prepareExportBlog($blog);
    }

    /**
     * @throws HelperException
     */
    public function exportBlogs(array $blogIds): array
    {
        return array_map(
            fn($blogId) => $this->exportBlogById((int)$blogId),
            $this->makeNonEmptyArray($blogIds)
        );
    }

    /**
     * @throws HelperException
     */
    protected function exportPostById(int $postId): array
    {
        $post = $this->getPostById($postId);

        if (empty($post['CODE'])) {
            throw new HelperException("Blog post \"$postId\" has empty CODE");
        }

        $post['AUTHOR_LOGIN'] = $this->userHelper->getUserLoginById((int)$post['AUTHOR_ID']);

        $post['CATEGORIES'] = $this->exportPostCategories((int)$post['BLOG_ID'], (int)$post['ID']);
        $post['PERMS_POST'] = $this->exportPostPerms((int)$post['BLOG_ID'], (int)$post['ID'], BLOG_PERMS_POST);
        $post['PERMS_COMMENT'] = $this->exportPostPerms((int)$post['BLOG_ID'], (int)$post['ID'], BLOG_PERMS_COMMENT);

        return array_merge(
            $this->prepareExportPost($post),
            $this->exportPostUserFields((int)$post['ID'])
        );
    }

    /**
     * @throws HelperException
     */
    public function saveGroup(array $fields): int
    {
        $this->checkRequiredKeys($fields, ['SITE_ID', 'NAME']);

        $groupId = $this->getGroupIdBySiteAndName($fields['SITE_ID'], $fields['NAME']);

        if (!$groupId) {
            return $this->addGroup($fields);
        }

        $exists = $this->exportGroupById($groupId);

        if ($this->checkDiff($exists, $fields)) {
            return $this->updateGroup($groupId, $fields);
        }

        return $groupId;
    }

    /**
     * @throws HelperException
     */
    public function saveBlog(int $groupId, array $fields): int
    {
        $this->checkRequiredKeys($fields, ['NAME', 'URL', 'OWNER_LOGIN']);

        if (empty($this->getGroupById($groupId))) {
            throw new HelperException("Blog group \"$groupId\" not found");
        }

        $blogId = $this->getBlogIdByUrl($fields['URL']);

        if (!$blogId) {
            return $this->addBlog($groupId, $fields);
        }

        $exists = $this->exportBlogById($blogId);

        if ($this->checkDiff($exists, $fields)) {
            return $this->updateBlog($blogId, $fields);
        }

        return $blogId;
    }

    /**
     * @throws HelperException
     */
    public function savePost(int $blogId, array $fields): int
    {
        $this->checkRequiredKeys($fields, ['TITLE', 'DETAIL_TEXT', 'DATE_CREATE', 'DATE_PUBLISH', 'AUTHOR_LOGIN', 'CODE']);

        if (empty($this->getBlogById($blogId))) {
            throw new HelperException("Blog \"$blogId\" not found");
        }

        $fieldsForSave = $this->preparePostFieldsForSave($blogId, $fields);
        $postId = $this->getPostIdByCode($blogId, $fieldsForSave['CODE']);

        if (!$postId) {
            $postId = $this->addPost($fieldsForSave);
        } else {
            $exists = $this->exportPostById($postId);
            $export = $this->prepareExportPost(array_merge($fieldsForSave, [
                'AUTHOR_LOGIN'  => $fields['AUTHOR_LOGIN'],
                'CATEGORIES'    => $fields['CATEGORIES'] ?? [],
                'PERMS_POST'    => $fields['PERMS_POST'] ?? [],
                'PERMS_COMMENT' => $fields['PERMS_COMMENT'] ?? [],
                'ATTACH_IMG'    => $fields['ATTACH_IMG'] ?? false,
            ])) + $this->extractPostUserFields($fields);

            if ($this->checkDiff($exists, $export)) {
                $postId = $this->updatePost($postId, $fieldsForSave);
            }
        }

        $this->savePostCategories($blogId, $postId, $fields['CATEGORIES'] ?? []);
        return $postId;
    }

    public function getGroupIdBySiteAndName(string $siteId, string $name): int
    {
        $group = CBlogGroup::GetList(
            ['ID' => 'ASC'],
            [
                'SITE_ID' => $siteId,
                'NAME'    => $name,
            ],
            false,
            false,
            ['ID']
        )->Fetch();

        return (int)($group['ID'] ?? 0);
    }

    public function getBlogIdByUrl(string $url): int
    {
        $blog = CBlog::GetList(
            ['ID' => 'ASC'],
            ['URL' => $url],
            false,
            false,
            ['ID']
        )->Fetch();

        return (int)($blog['ID'] ?? 0);
    }

    public function getPostIdByCode(int $blogId, string $code): int
    {
        $post = CBlogPost::GetList(
            ['ID' => 'ASC'],
            [
                'BLOG_ID' => $blogId,
                'CODE'    => $code,
            ],
            false,
            false,
            ['ID']
        )->Fetch();

        return (int)($post['ID'] ?? 0);
    }

    /**
     * @throws HelperException
     */
    public function getGroupById(int $groupId): array
    {
        $group = CBlogGroup::GetList(
            ['ID' => 'ASC'],
            ['ID' => $groupId],
            false,
            false,
            ['ID', 'SITE_ID', 'NAME']
        )->Fetch();
        if (empty($group)) {
            throw new HelperException("Blog group \"$groupId\" not found");
        }

        return $this->filterKeys($group, array_keys($this->getDefaultGroup()));
    }

    /**
     * @throws HelperException
     */
    public function getBlogById(int $blogId): array
    {
        $blog = CBlog::GetList(
            ['ID' => 'ASC'],
            ['ID' => $blogId],
            false,
            false,
            $this->getBlogKeys()
        )->Fetch();
        if (empty($blog)) {
            throw new HelperException("Blog \"$blogId\" not found");
        }

        return $this->filterKeys($blog, $this->getBlogKeys());
    }

    /**
     * @throws HelperException
     */
    public function getPostById(int $postId): array
    {
        $post = CBlogPost::GetList(
            ['ID' => 'ASC'],
            ['ID' => $postId],
            false,
            false,
            $this->getPostKeys()
        )->Fetch();
        if (empty($post)) {
            throw new HelperException("Blog post \"$postId\" not found");
        }

        return $this->filterKeys($post, $this->getPostKeys());
    }

    /**
     * @throws HelperException
     */
    protected function addGroup(array $fields): int
    {
        $groupId = CBlogGroup::Add($fields);
        if ($groupId) {
            $this->outNotice(Locale::getMessage('BLOG_GROUP_CREATED', ['#NAME#' => $fields['NAME']]));
            return (int)$groupId;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException("Blog group \"{$fields['NAME']}\" not added");
    }

    /**
     * @throws HelperException
     */
    protected function updateGroup(int $groupId, array $fields): int
    {
        $result = CBlogGroup::Update($groupId, $fields);
        if ($result) {
            $this->outNotice(Locale::getMessage('BLOG_GROUP_UPDATED', ['#NAME#' => $fields['NAME']]));
            return $groupId;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException("Blog group \"{$fields['NAME']}\" not updated");
    }

    /**
     * @throws HelperException
     */
    public function addBlog(int $groupId, array $fields): int
    {
        $fields['GROUP_ID'] = $groupId;

        if (isset($fields['OWNER_LOGIN'])) {
            $fields['OWNER_ID'] = $this->userHelper->getUserIdByLogin((string)$fields['OWNER_LOGIN']);
            unset($fields['OWNER_LOGIN']);
        }

        $userGroups = [];
        if (isset($fields['USER_GROUPS'])) {
            $userGroups = $fields['USER_GROUPS'];
            unset($fields['USER_GROUPS']);
        }

        global $DB;

        if (empty($fields['=DATE_CREATE'])) {
            $fields['=DATE_CREATE'] = $DB->CurrentTimeFunction();
        }

        if (empty($fields['=DATE_UPDATE'])) {
            $fields['=DATE_UPDATE'] = $DB->CurrentTimeFunction();
        }

        $blogId = CBlog::Add($fields);
        if ($blogId) {

            $this->saveUserGroups($blogId, $userGroups);

            $this->outNotice(Locale::getMessage('BLOG_CREATED', ['#NAME#' => $fields['NAME']]));
            return (int)$blogId;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException("Blog \"{$fields['NAME']}\" not added");
    }

    /**
     * @throws HelperException
     */
    protected function updateBlog(int $blogId, array $fields): int
    {
        global $DB;

        if (isset($fields['OWNER_LOGIN'])) {
            $fields['OWNER_ID'] = $this->userHelper->getUserIdByLogin((string)$fields['OWNER_LOGIN']);
            unset($fields['OWNER_LOGIN']);
        }

        $userGroups = [];
        if (isset($fields['USER_GROUPS'])) {
            $userGroups = $fields['USER_GROUPS'];
            unset($fields['USER_GROUPS']);
        }

        if (empty($fields['=DATE_UPDATE'])) {
            $fields['=DATE_UPDATE'] = $DB->CurrentTimeFunction();
        }

        $result = CBlog::Update($blogId, $fields);
        if ($result) {
            $this->saveUserGroups($blogId, $userGroups);

            $this->outNotice(Locale::getMessage('BLOG_UPDATED', ['#NAME#' => $fields['NAME']]));
            return $blogId;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException("Blog \"{$fields['NAME']}\" not updated");
    }

    /**
     * @throws HelperException
     */
    protected function addPost(array $fields): int
    {
        $postId = CBlogPost::Add($fields);
        if ($postId) {
            $this->outNotice(Locale::getMessage('BLOG_POST_UPDATED', ['#NAME#' => $fields['TITLE']]));
            return (int)$postId;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException("Blog post \"{$fields['TITLE']}\" not added");
    }

    /**
     * @throws HelperException
     */
    protected function updatePost(int $postId, array $fields): int
    {
        $result = CBlogPost::Update($postId, $fields);
        if ($result) {
            $this->outNotice(Locale::getMessage('BLOG_POST_UPDATED', ['#NAME#' => $fields['TITLE']]));
            return $postId;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException("Blog post \"{$fields['TITLE']}\" not updated");
    }

    protected function prepareGroupFields(array $fields): array
    {
        return array_intersect_key(
            $fields,
            array_flip(array_keys($this->getDefaultGroup()))
        );
    }

    /**
     * @throws HelperException
     */
    protected function preparePostFieldsForSave(int $blogId, array $fields): array
    {
        $hasAttachImg = array_key_exists('ATTACH_IMG', $fields);
        $fields = array_merge($this->getDefaultPost(), $fields);
        $fields['BLOG_ID'] = $blogId;
        $fields['AUTHOR_ID'] = $this->userHelper->getUserIdByLogin((string)$fields['AUTHOR_LOGIN']);

        $fields['CATEGORY_ID'] = implode(',', $this->getCategoryIdsByNames($blogId, $fields['CATEGORIES'] ?? []));
        $fields['PERMS_POST'] = $this->revertPostPerms($blogId, $fields['PERMS_POST'] ?? []);
        $fields['PERMS_COMMENT'] = $this->revertPostPerms($blogId, $fields['PERMS_COMMENT'] ?? []);
        $userFields = $this->revertPostUserFields($this->extractPostUserFields($fields));

        if (!$hasAttachImg) {
            unset($fields['ATTACH_IMG']);
        }

        $this->unsetKeys($fields, [
            'AUTHOR_LOGIN',
            'CATEGORIES',
        ]);

        $fields = array_intersect_key(
            $fields,
            array_flip($this->getPostSaveKeys())
        );

        foreach ($userFields as $code => $value) {
            $fields[$code] = $value;
        }

        return $fields;
    }

    protected function extractPostUserFields(array $fields): array
    {
        $result = [];
        foreach ($fields as $name => $value) {
            if (str_starts_with((string)$name, 'UF_')) {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    protected function prepareExportBlog(array $fields): array
    {
        return $this->export(
            $fields,
            $this->getDefaultBlog(),
            [
                'ID',
                'GROUP_ID',
                'OWNER_ID',
                'SOCNET_GROUP_ID',
                'DATE_CREATE',
                'DATE_UPDATE',
                'LAST_POST_ID',
                'LAST_POST_DATE',
                'AUTO_GROUPS',
            ]
        );
    }

    protected function prepareExportPost(array $fields): array
    {
        $fields = array_merge($this->getDefaultPost(), $fields);

        return $this->export(
            $fields,
            $this->getDefaultPost(),
            [
                'ID',
                'BLOG_ID',
                'AUTHOR_ID',
                'CATEGORY_ID',
                'NUM_COMMENTS',
                'NUM_COMMENTS_ALL',
                'NUM_TRACKBACKS',
                'VIEWS',
                'HAS_IMAGES',
                'HAS_PROPS',
                'HAS_TAGS',
                'HAS_COMMENT_IMAGES',
                'HAS_SOCNET_ALL',
            ]
        );
    }

    protected function normalizeUserGroups(array $userGroups): array
    {
        $defaults = [
            'NAME'          => '',
            'AUTO'          => 'N',
            'PERMS_POST'    => false,
            'PERMS_COMMENT' => false,
        ];

        return array_map(
            fn($item) => array_merge($defaults, array_intersect_key($item, $defaults)),
            $userGroups
        );

    }

    protected function exportUserGroups(array $blog): array
    {
        $autoGroupIds = $this->unserializeIds($blog['AUTO_GROUPS'] ?? '');
        $groups = [
            1 => CBlogUserGroup::GetByID(1),
            2 => CBlogUserGroup::GetByID(2),
        ];

        $dbres = CBlogUserGroup::GetList(
            ['ID' => 'ASC'],
            ['BLOG_ID' => (int)$blog['ID']],
            false,
            false,
            ['ID', 'BLOG_ID', 'NAME']
        );
        while ($group = $dbres->Fetch()) {
            $groups[(int)$group['ID']] = $group;
        }

        $result = [];
        foreach ($groups as $groupId => $group) {
            if (empty($group['NAME'])) {
                continue;
            }

            $item = [
                'NAME'          => $group['NAME'],
                'AUTO'          => in_array((int)$groupId, $autoGroupIds, true) ? 'Y' : 'N',
                'PERMS_POST'    => CBlogUserGroup::GetGroupPerms((int)$groupId, (int)$blog['ID'], 0, BLOG_PERMS_POST),
                'PERMS_COMMENT' => CBlogUserGroup::GetGroupPerms((int)$groupId, (int)$blog['ID'], 0, BLOG_PERMS_COMMENT),
            ];

            if ($groupId <= 2 && $item['AUTO'] === 'N' && $item['PERMS_POST'] === false && $item['PERMS_COMMENT'] === false) {
                continue;
            }

            $result[] = $item;
        }

        return $result;
    }

    protected function saveUserGroups(int $blogId, array $userGroups, bool $deleteOldGroups = true): void
    {
        $userGroups = $this->normalizeUserGroups($userGroups);
        $currentGroups = $this->getUserGroups($blogId);
        $updatedIds = [];
        $autoGroupIds = [];

        foreach ($userGroups as $userGroup) {
            if (empty($userGroup['NAME'])) {
                continue;
            }

            $groupId = $this->getUserGroupId($blogId, $userGroup['NAME']);

            if (!$groupId) {
                $groupId = (int)CBlogUserGroup::Add([
                    'BLOG_ID' => $blogId,
                    'NAME'    => $userGroup['NAME'],
                ]);
            }

            if (!$groupId) {
                continue;
            }

            if ($userGroup['PERMS_POST'] !== false) {
                CBlogUserGroup::SetGroupPerms($groupId, $blogId, 0, $userGroup['PERMS_POST'], BLOG_PERMS_POST);
            }

            if ($userGroup['PERMS_COMMENT'] !== false) {
                CBlogUserGroup::SetGroupPerms($groupId, $blogId, 0, $userGroup['PERMS_COMMENT'], BLOG_PERMS_COMMENT);
            }

            if ($userGroup['AUTO'] === 'Y') {
                $autoGroupIds[] = $groupId;
            }

            $updatedIds[] = $groupId;
        }

        if ($deleteOldGroups) {
            foreach ($currentGroups as $currentGroup) {
                $currentGroupId = (int)$currentGroup['ID'];
                if ($currentGroupId > 2 && !in_array($currentGroupId, $updatedIds, true)) {
                    CBlogUserGroup::Delete($currentGroupId);
                }
            }
        }

        CBlog::Update($blogId, [
            'AUTO_GROUPS' => !empty($autoGroupIds) ? serialize($autoGroupIds) : '',
        ]);
    }

    protected function exportPostUserFields(int $postId): array
    {
        $result = [];
        $fields = $this->getPostUserFieldsWithValues($postId);
        foreach ($fields as $fieldName => $field) {
            $value = $this->exportPostUserFieldValue($field);
            if ($value !== null && $value !== [] && $value !== '') {
                $result[$fieldName] = $value;
            }
        }

        return $result;
    }

    /**
     * @throws HelperException
     */
    protected function getPostUserFieldsWithValues(int $postId): array
    {
        $post = CBlogPost::GetList(
            ['ID' => 'ASC'],
            ['ID' => $postId],
            false,
            false,
            ['ID', 'UF_*']
        )->Fetch();

        if (empty($post)) {
            return [];
        }

        $fields = [];
        foreach ((new UserTypeEntityHelper())->getUserTypeEntities('BLOG_POST') as $field) {
            $fieldName = (string)($field['FIELD_NAME'] ?? '');
            if (!str_starts_with($fieldName, 'UF_') || !array_key_exists($fieldName, $post)) {
                continue;
            }

            $field['VALUE'] = $post[$fieldName];
            $fields[$fieldName] = $field;
        }

        return $fields;
    }

    protected function exportPostUserFieldValue(array $field): mixed
    {
        $value = $field['VALUE'] ?? null;
        $multiple = (($field['MULTIPLE'] ?? 'N') === 'Y');

        if ($field['USER_TYPE_ID'] === 'enumeration') {
            return $this->exportUserFieldEnumValue($field, $value, $multiple);
        }

        if ($field['USER_TYPE_ID'] === 'file') {
            if (empty($value)) {
                return $multiple ? [] : false;
            }

            $values = array_filter($this->makeNonEmptyArray($value));
            return $multiple ? $values : ((int)($values[0] ?? 0) ?: false);
        }

        return $value;
    }

    protected function exportUserFieldEnumValue(array $field, mixed $value, bool $multiple): mixed
    {
        if (empty($value)) {
            return $multiple ? [] : '';
        }

        $enumMap = $this->getUserFieldEnumExportMap((int)$field['ID']);
        $ids = $this->makeNonEmptyArray($value);
        $values = [];

        foreach ($ids as $enumId) {
            if (isset($enumMap[(int)$enumId])) {
                $values[] = $enumMap[(int)$enumId];
            }
        }

        return $multiple ? $values : ($values[0] ?? '');
    }

    protected function revertPostUserFields(array $fields): array
    {
        $result = [];
        foreach ($fields as $fieldName => $value) {
            $field = $this->getPostUserField($fieldName);
            if (empty($field)) {
                continue;
            }

            if ($field['USER_TYPE_ID'] === 'enumeration') {
                $value = $this->revertUserFieldEnumValue($field, $value);
            } elseif ($field['USER_TYPE_ID'] === 'file') {
                $value = $this->revertUserFieldFileValue($field, $value);
            }

            $result[$fieldName] = $value;
        }

        return $result;
    }

    protected function revertUserFieldEnumValue(array $field, mixed $value): mixed
    {
        $multiple = (($field['MULTIPLE'] ?? 'N') === 'Y');
        if (empty($value)) {
            return $multiple ? [] : false;
        }

        $enumMap = $this->getUserFieldEnumImportMap((int)$field['ID']);
        $values = [];

        foreach ($this->makeNonEmptyArray($value) as $enumRef) {
            $key = (string)$enumRef;
            if (isset($enumMap[$key])) {
                $values[] = $enumMap[$key];
            }
        }

        return $multiple ? $values : ($values[0] ?? false);
    }

    protected function revertUserFieldFileValue(array $field, mixed $value): mixed
    {
        $multiple = (($field['MULTIPLE'] ?? 'N') === 'Y');
        if (empty($value)) {
            return $multiple ? [] : false;
        }

        return $multiple ? $this->makeNonEmptyArray($value) : $value;
    }

    protected function getPostUserField(string $fieldName): array
    {
        $field = CUserTypeEntity::GetList(
            [],
            [
                'ENTITY_ID'  => 'BLOG_POST',
                'FIELD_NAME' => $fieldName,
            ]
        )->Fetch();

        return is_array($field) ? $field : [];
    }

    protected function getUserFieldEnumExportMap(int $fieldId): array
    {
        $result = [];
        $dbres = (new CUserFieldEnum())->GetList([], ['USER_FIELD_ID' => $fieldId]);
        while ($enum = $dbres->Fetch()) {
            $result[(int)$enum['ID']] = !empty($enum['XML_ID']) ? $enum['XML_ID'] : $enum['VALUE'];
        }

        return $result;
    }

    protected function getUserFieldEnumImportMap(int $fieldId): array
    {
        $result = [];
        $dbres = (new CUserFieldEnum())->GetList([], ['USER_FIELD_ID' => $fieldId]);
        while ($enum = $dbres->Fetch()) {
            if (!empty($enum['XML_ID'])) {
                $result[(string)$enum['XML_ID']] = (int)$enum['ID'];
            }
            $result[(string)$enum['VALUE']] = (int)$enum['ID'];
        }

        return $result;
    }

    protected function exportPostCategories(int $blogId, int $postId): array
    {
        $dbres = CBlogPostCategory::GetList(
            ['NAME' => 'ASC'],
            [
                'BLOG_ID' => $blogId,
                'POST_ID' => $postId,
            ],
            false,
            false,
            ['CATEGORY_ID', 'NAME']
        );

        $items = [];
        while ($category = $dbres->Fetch()) {
            if (!empty($category['NAME'])) {
                $items[] = $category['NAME'];
            }
        }

        return $items;
    }

    protected function savePostCategories(int $blogId, int $postId, array $names): void
    {
        $categoryIds = $this->getCategoryIdsByNames($blogId, $names);

        CBlogPostCategory::DeleteByPostID($postId);
        foreach ($categoryIds as $categoryId) {
            CBlogPostCategory::Add([
                'BLOG_ID'     => $blogId,
                'POST_ID'     => $postId,
                'CATEGORY_ID' => $categoryId,
            ]);
        }
    }

    protected function getCategoryIdsByNames(int $blogId, array $names): array
    {
        $result = [];
        foreach ($names as $name) {
            $name = trim((string)$name);
            if ($name === '') {
                continue;
            }

            $result[] = $this->getCategoryIdByName($blogId, $name) ?: (int)CBlogCategory::Add([
                'BLOG_ID' => $blogId,
                'NAME'    => $name,
            ]);
        }

        return array_values(array_filter($result));
    }

    protected function getCategoryIdByName(int $blogId, string $name): int
    {
        $category = CBlogCategory::GetList(
            ['ID' => 'ASC'],
            [
                'BLOG_ID' => $blogId,
                'NAME'    => $name,
            ],
            false,
            false,
            ['ID']
        )->Fetch();

        return (int)($category['ID'] ?? 0);
    }

    protected function exportPostPerms(int $blogId, int $postId, string $permsType): array
    {
        $result = [];
        foreach ($this->getUserGroups($blogId) as $groupId => $group) {
            if (empty($group['NAME'])) {
                continue;
            }

            $perms = CBlogUserGroup::GetGroupPerms((int)$groupId, $blogId, $postId, $permsType);
            if ($perms !== false) {
                $result[$group['NAME']] = $perms;
            }
        }

        return $result;
    }

    protected function revertPostPerms(int $blogId, array $perms): array
    {
        $result = [];
        foreach ($perms as $groupName => $permission) {
            $groupId = $this->getUserGroupId($blogId, (string)$groupName);
            if ($groupId) {
                $result[$groupId] = $permission;
            }
        }

        return $result;
    }

    protected function getUserGroups(int $blogId): array
    {
        $groups = [];
        foreach ([1, 2] as $groupId) {
            $group = CBlogUserGroup::GetByID($groupId);
            if (!empty($group)) {
                $groups[$groupId] = $group;
            }
        }

        $dbres = CBlogUserGroup::GetList(
            ['ID' => 'ASC'],
            ['BLOG_ID' => $blogId],
            false,
            false,
            ['ID', 'BLOG_ID', 'NAME']
        );
        while ($group = $dbres->Fetch()) {
            $groups[(int)$group['ID']] = $group;
        }

        return $groups;
    }

    protected function getUserGroupId(int $blogId, string $name): int
    {
        foreach ($this->getUserGroups($blogId) as $group) {
            if ($group['NAME'] === $name) {
                return (int)$group['ID'];
            }
        }

        return 0;
    }

    protected function unserializeIds(mixed $value): array
    {
        if (empty($value) || !is_string($value)) {
            return [];
        }

        $ids = @unserialize($value, ['allowed_classes' => false]);
        if (!is_array($ids)) {
            return [];
        }

        return array_values(array_map('intval', $ids));
    }

    protected function filterKeys(array $item, array $keys): array
    {
        return array_intersect_key($item, array_flip($keys));
    }

    protected function getDefaultGroup(): array
    {
        return [
            'SITE_ID' => '',
            'NAME'    => '',
        ];
    }

    protected function getDefaultBlog(): array
    {
        return [
            'OWNER_LOGIN'       => '',
            'NAME'              => '',
            'DESCRIPTION'       => '',
            'ACTIVE'            => 'Y',
            'URL'               => '',
            'REAL_URL'          => '',
            'ENABLE_COMMENTS'   => 'Y',
            'ENABLE_IMG_VERIF'  => 'N',
            'EMAIL_NOTIFY'      => 'Y',
            'ENABLE_RSS'        => 'Y',
            'ALLOW_HTML'        => 'N',
            'SEARCH_INDEX'      => 'Y',
            'USE_SOCNET'        => 'N',
            'EDITOR_USE_FONT'   => 'N',
            'EDITOR_USE_LINK'   => 'N',
            'EDITOR_USE_IMAGE'  => 'N',
            'EDITOR_USE_FORMAT' => 'N',
            'EDITOR_USE_VIDEO'  => 'N',
            'USER_GROUPS'       => [],
        ];
    }

    protected function getDefaultPost(): array
    {
        return [
            'AUTHOR_LOGIN'      => '',
            'TITLE'             => '',
            'PREVIEW_TEXT'      => '',
            'PREVIEW_TEXT_TYPE' => 'text',
            'DETAIL_TEXT'       => '',
            'DETAIL_TEXT_TYPE'  => 'text',
            'DATE_CREATE'       => '',
            'DATE_PUBLISH'      => '',
            'KEYWORDS'          => '',
            'PUBLISH_STATUS'    => BLOG_PUBLISH_STATUS_PUBLISH,
            'ATRIBUTE'          => '',
            'ENABLE_TRACKBACK'  => 'Y',
            'ENABLE_COMMENTS'   => 'Y',
            'ATTACH_IMG'        => false,
            'FAVORITE_SORT'     => false,
            'PATH'              => '',
            'CODE'              => '',
            'MICRO'             => 'N',
            'SEO_TITLE'         => '',
            'SEO_TAGS'          => '',
            'SEO_DESCRIPTION'   => '',
            'CATEGORIES'        => [],
            'PERMS_POST'        => [],
            'PERMS_COMMENT'     => [],
        ];
    }

    protected function getBlogKeys(): array
    {
        return [
            'ID',
            'NAME',
            'DESCRIPTION',
            'DATE_CREATE',
            'DATE_UPDATE',
            'ACTIVE',
            'OWNER_ID',
            'SOCNET_GROUP_ID',
            'URL',
            'REAL_URL',
            'GROUP_ID',
            'ENABLE_COMMENTS',
            'ENABLE_IMG_VERIF',
            'EMAIL_NOTIFY',
            'ENABLE_RSS',
            'LAST_POST_ID',
            'LAST_POST_DATE',
            'AUTO_GROUPS',
            'ALLOW_HTML',
            'SEARCH_INDEX',
            'USE_SOCNET',
            'EDITOR_USE_FONT',
            'EDITOR_USE_LINK',
            'EDITOR_USE_IMAGE',
            'EDITOR_USE_FORMAT',
            'EDITOR_USE_VIDEO',
        ];
    }

    protected function getBlogSaveKeys(): array
    {
        return [
            'NAME',
            'DESCRIPTION',
            'ACTIVE',
            'OWNER_ID',
            'URL',
            'REAL_URL',
            'GROUP_ID',
            'ENABLE_COMMENTS',
            'ENABLE_IMG_VERIF',
            'EMAIL_NOTIFY',
            'ENABLE_RSS',
            'ALLOW_HTML',
            'SEARCH_INDEX',
            'USE_SOCNET',
            'EDITOR_USE_FONT',
            'EDITOR_USE_LINK',
            'EDITOR_USE_IMAGE',
            'EDITOR_USE_FORMAT',
            'EDITOR_USE_VIDEO',
        ];
    }

    protected function getPostKeys(): array
    {
        return [
            'ID',
            'TITLE',
            'BLOG_ID',
            'AUTHOR_ID',
            'PREVIEW_TEXT',
            'PREVIEW_TEXT_TYPE',
            'DETAIL_TEXT',
            'DETAIL_TEXT_TYPE',
            'DATE_CREATE',
            'DATE_PUBLISH',
            'KEYWORDS',
            'PUBLISH_STATUS',
            'CATEGORY_ID',
            'ATRIBUTE',
            'ENABLE_TRACKBACK',
            'ENABLE_COMMENTS',
            'ATTACH_IMG',
            'NUM_COMMENTS',
            'NUM_COMMENTS_ALL',
            'NUM_TRACKBACKS',
            'VIEWS',
            'FAVORITE_SORT',
            'PATH',
            'CODE',
            'MICRO',
            'HAS_IMAGES',
            'HAS_PROPS',
            'HAS_TAGS',
            'HAS_COMMENT_IMAGES',
            'HAS_SOCNET_ALL',
            'SEO_TITLE',
            'SEO_TAGS',
            'SEO_DESCRIPTION',
        ];
    }

    protected function getPostSaveKeys(): array
    {
        return [
            'TITLE',
            'BLOG_ID',
            'AUTHOR_ID',
            'PREVIEW_TEXT',
            'PREVIEW_TEXT_TYPE',
            'DETAIL_TEXT',
            'DETAIL_TEXT_TYPE',
            'DATE_CREATE',
            'DATE_PUBLISH',
            'KEYWORDS',
            'PUBLISH_STATUS',
            'CATEGORY_ID',
            'ATRIBUTE',
            'ENABLE_TRACKBACK',
            'ENABLE_COMMENTS',
            'ATTACH_IMG',
            'FAVORITE_SORT',
            'PATH',
            'CODE',
            'MICRO',
            'SEO_TITLE',
            'SEO_TAGS',
            'SEO_DESCRIPTION',
            'PERMS_POST',
            'PERMS_COMMENT',
        ];
    }
}
