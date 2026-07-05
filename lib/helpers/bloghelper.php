<?php

namespace Sprint\Migration\Helpers;

use CBlog;
use CBlogCategory;
use CBlogGroup;
use CBlogImage;
use CBlogPost;
use CBlogPostCategory;
use CBlogUserGroup;
use CUserFieldEnum;
use CUserTypeEntity;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\FileExchange;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;

class BlogHelper extends Helper
{

    private UserHelper $userHelper;
    private FileExchange $fileExchange;

    public function __construct()
    {
        $this->userHelper = new UserHelper();
        $this->fileExchange = new FileExchange();
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
    public function exportPostById(int $postId, string $exchangeDir = ''): array
    {
        $post = $this->getPostById($postId);

        if (empty($post['CODE'])) {
            throw new HelperException("Blog post \"$postId\" has empty CODE");
        }

        $post['AUTHOR_LOGIN'] = $this->userHelper->getUserLoginById((int)$post['AUTHOR_ID']);

        $post['CATEGORIES'] = $this->exportPostCategories((int)$post['BLOG_ID'], (int)$post['ID']);
        $post['PERMS_POST'] = $this->exportPostPerms((int)$post['BLOG_ID'], (int)$post['ID'], BLOG_PERMS_POST);
        $post['PERMS_COMMENT'] = $this->exportPostPerms((int)$post['BLOG_ID'], (int)$post['ID'], BLOG_PERMS_COMMENT);
        $post['ATTACH_IMG'] = $this->fileExchange->exportFileById((int)$post['ATTACH_IMG'], $exchangeDir, 'blog_post_files');
        $post['UF_VALUES'] = $this->exportPostUserFields((int)$post['ID'], $exchangeDir);
        $post['TEXT_IMAGES'] = $this->exportPostTextImages($post, $exchangeDir);

        return $this->prepareExportPost($post);
    }

    /**
     * @throws HelperException
     */
    public function exportPosts(array $postIds, string $exchangeDir = ''): array
    {
        return array_map(
            fn($postId) => $this->exportPostById((int)$postId, $exchangeDir),
            $this->makeNonEmptyArray($postIds)
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
    public function savePost(int $blogId, array $fields, string $exchangeDir = ''): int
    {
        $this->checkRequiredKeys($fields, ['TITLE', 'DETAIL_TEXT', 'DATE_CREATE', 'DATE_PUBLISH', 'AUTHOR_LOGIN', 'CODE']);

        if (empty($this->getBlogById($blogId))) {
            throw new HelperException("Blog \"$blogId\" not found");
        }

        $fieldsForSave = $this->preparePostFieldsForSave($blogId, $fields, $exchangeDir);
        $postId = $this->getPostIdByCode($blogId, $fieldsForSave['CODE']);

        if (!$postId) {
            $postId = $this->addPost($fieldsForSave);
        } else {
            $exists = $this->exportPostById($postId, $exchangeDir);
            $export = $this->prepareExportPost(array_merge($fieldsForSave, [
                'AUTHOR_LOGIN'  => $fields['AUTHOR_LOGIN'],
                'CATEGORIES'    => $fields['CATEGORIES'] ?? [],
                'PERMS_POST'    => $fields['PERMS_POST'] ?? [],
                'PERMS_COMMENT' => $fields['PERMS_COMMENT'] ?? [],
                'ATTACH_IMG'    => $fields['ATTACH_IMG'] ?? false,
                'UF_VALUES'     => $fields['UF_VALUES'] ?? [],
                'TEXT_IMAGES'   => $fields['TEXT_IMAGES'] ?? [],
            ]));

            if ($this->checkDiff($exists, $export)) {
                $postId = $this->updatePost($postId, $fieldsForSave);
            }
        }

        $this->savePostCategories($blogId, $postId, $fields['CATEGORIES'] ?? []);
        $this->savePostTextImages($blogId, $postId, $fieldsForSave, $fields['TEXT_IMAGES'] ?? [], $exchangeDir);

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
        return array_intersect_key($fields, $this->getDefaultGroup());
    }

    /**
     * @throws HelperException
     */
    protected function preparePostFieldsForSave(int $blogId, array $fields, string $exchangeDir = ''): array
    {
        $hasAttachImg = array_key_exists('ATTACH_IMG', $fields);
        $fields = array_merge($this->getDefaultPost(), $fields);
        $fields['BLOG_ID'] = $blogId;
        $fields['AUTHOR_ID'] = $this->userHelper->getUserIdByLogin((string)$fields['AUTHOR_LOGIN']);

        $fields['CATEGORY_ID'] = implode(',', $this->getCategoryIdsByNames($blogId, $fields['CATEGORIES'] ?? []));
        $fields['PERMS_POST'] = $this->revertPostPerms($blogId, $fields['PERMS_POST'] ?? []);
        $fields['PERMS_COMMENT'] = $this->revertPostPerms($blogId, $fields['PERMS_COMMENT'] ?? []);
        $userFields = $this->revertPostUserFields($fields['UF_VALUES'] ?? [], $exchangeDir);

        if (!$hasAttachImg) {
            unset($fields['ATTACH_IMG']);
        } elseif (!empty($fields['ATTACH_IMG']) && is_array($fields['ATTACH_IMG'])) {
            $fields['ATTACH_IMG'] = $this->fileExchange->makeFileArrayByRef($fields['ATTACH_IMG'], $exchangeDir);
        }

        $this->unsetKeys($fields, [
            'AUTHOR_LOGIN',
            'CATEGORIES',
            'UF_VALUES',
            'TEXT_IMAGES',
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

    protected function exportPostUserFields(int $postId, string $exchangeDir = ''): array
    {
        global $USER_FIELD_MANAGER;

        if (empty($USER_FIELD_MANAGER)) {
            return [];
        }

        $result = [];
        $fields = $USER_FIELD_MANAGER->GetUserFields('BLOG_POST', $postId, LANGUAGE_ID);
        foreach ($fields as $fieldName => $field) {
            if (!str_starts_with($fieldName, 'UF_')) {
                continue;
            }

            $value = $this->exportPostUserFieldValue($field, $exchangeDir);
            if ($value !== null && $value !== [] && $value !== '') {
                $result[$fieldName] = $value;
            }
        }

        return $result;
    }

    protected function exportPostUserFieldValue(array $field, string $exchangeDir = ''): mixed
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
            $items = array_map(
                fn($fileId) => $this->fileExchange->exportFileById((int)$fileId, $exchangeDir, 'blog_post_files'),
                $values
            );
            $items = array_values(array_filter($items));
            return $multiple ? $items : ($items[0] ?? false);
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

    protected function revertPostUserFields(array $fields, string $exchangeDir = ''): array
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
                $value = $this->revertUserFieldFileValue($field, $value, $exchangeDir);
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

    protected function revertUserFieldFileValue(array $field, mixed $value, string $exchangeDir = ''): mixed
    {
        $multiple = (($field['MULTIPLE'] ?? 'N') === 'Y');
        if (empty($value)) {
            return $multiple ? [] : false;
        }

        $values = [];

        foreach ($this->makePostUserFieldFileRefs($value, $multiple) as $fileRef) {
            if (is_array($fileRef)) {
                $file = $this->fileExchange->makeFileArrayByRef($fileRef, $exchangeDir);
                if ($file) {
                    $values[] = $file;
                }
            }
        }

        return $multiple ? $values : ($values[0] ?? false);
    }

    protected function makePostUserFieldFileRefs(mixed $value, bool $multiple): array
    {
        if (!$multiple && is_array($value) && isset($value['path'])) {
            return [$value];
        }

        return $this->makeNonEmptyArray($value);
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

    protected function exportPostTextImages(array $post, string $exchangeDir = ''): array
    {
        $texts = [
            $post['DETAIL_TEXT'] ?? '',
            $post['PREVIEW_TEXT'] ?? '',
        ];

        return array_filter([
            'BLOG_IMAGES' => $this->exportPostBlogImages($post, $texts, $exchangeDir),
            'FILE_LINKS'  => $this->exportPostTextFileLinks($texts, $exchangeDir),
        ]);
    }

    protected function exportPostBlogImages(array $post, array $texts, string $exchangeDir = ''): array
    {
        $imageIds = [];
        foreach ($texts as $text) {
            if (preg_match_all('/\[IMG\s+ID=(\d+)[^\]]*\]/i', (string)$text, $matches)) {
                foreach ($matches[1] as $imageId) {
                    $imageIds[(int)$imageId] = (int)$imageId;
                }
            }
        }

        $result = [];
        foreach ($imageIds as $imageId) {
            $image = CBlogImage::GetByID($imageId);
            if (empty($image['FILE_ID'])) {
                continue;
            }

            if ((int)$image['POST_ID'] !== (int)$post['ID'] || (int)$image['BLOG_ID'] !== (int)$post['BLOG_ID']) {
                continue;
            }

            $file = $this->fileExchange->exportFileById((int)$image['FILE_ID'], $exchangeDir, 'blog_post_text_images');
            if ($file) {
                $result[$imageId] = [
                    'FILE'       => $file,
                    'TITLE'      => $image['TITLE'] ?? '',
                    'USER_LOGIN' => $this->userHelper->getUserLoginById((int)($image['USER_ID'] ?? $post['AUTHOR_ID'])),
                    'IMAGE_SIZE' => $image['IMAGE_SIZE'] ?? '',
                ];
            }
        }

        return $result;
    }

    protected function exportPostTextFileLinks(array $texts, string $exchangeDir = ''): array
    {
        return $this->fileExchange->exportTextFileLinks($texts, $exchangeDir, 'blog_post_text_links');
    }

    /**
     * @throws HelperException
     */
    protected function savePostTextImages(
        int    $blogId,
        int    $postId,
        array  $fieldsForSave,
        array  $textImages,
        string $exchangeDir = ''
    ): void
    {
        if (empty($textImages)) {
            return;
        }

        $updates = [];
        foreach (['DETAIL_TEXT', 'PREVIEW_TEXT'] as $fieldName) {
            if (!array_key_exists($fieldName, $fieldsForSave)) {
                continue;
            }

            $updates[$fieldName] = (string)$fieldsForSave[$fieldName];
        }

        if (empty($updates)) {
            return;
        }

        $imageMap = $this->importPostBlogImages(
            $blogId,
            $postId,
            (int)$fieldsForSave['AUTHOR_ID'],
            $textImages['BLOG_IMAGES'] ?? [],
            $exchangeDir
        );
        $linkMap = $this->importPostTextFileLinks($textImages['FILE_LINKS'] ?? [], $exchangeDir);

        foreach ($updates as $fieldName => $text) {
            $text = $this->replacePostBlogImageIds($text, $imageMap);
            $text = $this->fileExchange->replaceTextFileLinks($text, $linkMap);
            $updates[$fieldName] = $text;
        }

        $result = CBlogPost::Update($postId, $updates);
        if (!$result) {
            $this->throwApplicationExceptionIfExists();
            throw new HelperException("Blog post \"$postId\" text images not updated");
        }
    }

    protected function importPostBlogImages(
        int    $blogId,
        int    $postId,
        int    $authorId,
        array  $blogImages,
        string $exchangeDir = ''
    ): array
    {
        if (empty($blogImages)) {
            return [];
        }

        $this->deletePostBlogImages($blogId, $postId);

        $result = [];
        foreach ($blogImages as $oldImageId => $image) {
            if (empty($image['FILE']) || !is_array($image['FILE'])) {
                continue;
            }

            $file = $this->fileExchange->makeFileArrayByRef($image['FILE'], $exchangeDir);
            if (!$file) {
                continue;
            }

            $userId = $authorId;
            if (!empty($image['USER_LOGIN'])) {
                $userId = $this->userHelper->getUserIdByLogin((string)$image['USER_LOGIN']);
            }

            $newImageId = CBlogImage::Add([
                'BLOG_ID'    => $blogId,
                'POST_ID'    => $postId,
                'USER_ID'    => $userId,
                'TITLE'      => $image['TITLE'] ?? '',
                'IMAGE_SIZE' => $image['IMAGE_SIZE'] ?? ($file['size'] ?? ''),
                'IS_COMMENT' => 'N',
                'FILE_ID'    => $file,
            ]);

            if ($newImageId) {
                $result[(int)$oldImageId] = (int)$newImageId;
            }
        }

        return $result;
    }

    protected function deletePostBlogImages(int $blogId, int $postId): void
    {
        $dbres = CBlogImage::GetList(
            ['ID' => 'ASC'],
            [
                'BLOG_ID'    => $blogId,
                'POST_ID'    => $postId,
                'IS_COMMENT' => 'N',
            ],
            false,
            false,
            ['ID']
        );

        while ($image = $dbres->Fetch()) {
            CBlogImage::Delete((int)$image['ID']);
        }
    }

    protected function replacePostBlogImageIds(string $text, array $imageMap): string
    {
        if (empty($imageMap)) {
            return $text;
        }

        return preg_replace_callback(
            '/\[IMG\s+ID=(\d+)([^\]]*)\]/i',
            function ($matches) use ($imageMap) {
                $oldImageId = (int)$matches[1];
                if (empty($imageMap[$oldImageId])) {
                    return $matches[0];
                }

                return '[IMG ID=' . $imageMap[$oldImageId] . ($matches[2] ?? '') . ']';
            },
            $text
        );
    }

    protected function importPostTextFileLinks(array $fileLinks, string $exchangeDir = ''): array
    {
        $result = [];
        foreach ($fileLinks as $oldLink => $fileRef) {
            if (!is_array($fileRef)) {
                continue;
            }

            $newLink = $this->fileExchange->saveFileRefAndGetPath(
                $fileRef,
                $exchangeDir,
                'sprint_migration/blog_post_text'
            );
            if ($newLink) {
                $result[$oldLink] = $newLink;
            }
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
            'UF_VALUES'         => [],
            'TEXT_IMAGES'       => [],
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
