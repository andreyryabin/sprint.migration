<?php

namespace Sprint\Migration\Helpers;

use CBlogPost;
use CUserFieldEnum;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\WriterTag;
use Sprint\Migration\Interfaces\ReaderHelperInterface;
use Sprint\Migration\Interfaces\WriterHelperInterface;

class BlogExchangeHelper extends BlogHelper implements ReaderHelperInterface, WriterHelperInterface
{
    /**
     * @throws HelperException
     */
    public function getWriterAttributes(...$vars): array
    {
        [$blogId] = $vars;
        $blog = $this->getBlogById((int)$blogId);

        return [
            'blogUrl' => $blog['URL'],
        ];
    }

    public function getWriterRecordsCount(...$vars): int
    {
        [$blogId, $filter] = $vars;

        return count($this->getPosts((int)$blogId, (array)$filter));
    }

    /**
     * @throws HelperException
     */
    public function getWriterRecordsTag(int $offset, int $limit, ...$vars): WriterTag
    {
        [$blogId, $filter] = $vars;

        $tag = new WriterTag('tmp');
        foreach ($this->getPostIds((int)$blogId, (array)$filter, $offset, $limit) as $postId) {
            $tag->addChild($this->createWriterRecordTag($this->exportPostForXml((int)$postId)));
        }

        return $tag;
    }

    /**
     * @throws HelperException
     */
    public function convertReaderRecords(array $attributes, array $records): array
    {
        $this->checkRequiredKeys($attributes, ['blogUrl']);

        $blogId = $this->getBlogIdByUrl($attributes['blogUrl']);
        if (!$blogId) {
            throw new HelperException("Blog \"{$attributes['blogUrl']}\" not found");
        }

        return array_map(
            fn($record) => [
                'blog_id' => $blogId,
                'fields'  => $this->convertReaderRecord($record),
            ],
            $records
        );
    }

    protected function getPostIds(int $blogId, array $filter, int $offset, int $limit): array
    {
        $filter['BLOG_ID'] = $blogId;

        $dbres = CBlogPost::GetList(
            [
                'DATE_PUBLISH' => 'DESC',
                'ID'           => 'DESC',
            ],
            $filter,
            false,
            false,
            ['ID']
        );

        $ids = [];
        $index = 0;
        while ($post = $dbres->Fetch()) {
            if ($index++ < $offset) {
                continue;
            }
            if (count($ids) >= $limit) {
                break;
            }

            $ids[] = (int)$post['ID'];
        }

        return $ids;
    }

    /**
     * @throws HelperException
     */
    protected function exportPostForXml(int $postId): array
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
            $this->exportPostUserFieldsForXml((int)$post['ID'])
        );
    }

    protected function createWriterRecordTag(array $post): WriterTag
    {
        $item = new WriterTag('item');

        foreach ($post as $name => $value) {
            if (str_starts_with((string)$name, 'UF_')) {
                $field = $this->createWriterUserFieldTag((string)$name, $value);
            } else {
                $field = new WriterTag('field', ['name' => $name]);
            }

            if ($name === 'ATTACH_IMG') {
                $field->addFile((int)$value, false);
            } elseif (!str_starts_with((string)$name, 'UF_')) {
                $field->addValue($value, false);
            }

            $item->addChild($field);
        }

        return $item;
    }

    protected function convertReaderRecord(array $record): array
    {
        $fields = [];

        foreach ($record['fields'] as $field) {
            if (empty($field['name'])) {
                continue;
            }

            if (str_starts_with((string)$field['name'], 'UF_')) {
                $fields[$field['name']] = $this->readUserFieldValue($field);
            } else {
                $fields[$field['name']] = $this->readFieldValue($field);
            }
        }

        return $fields;
    }

    protected function readFieldValue(array $field): mixed
    {
        return $field['value'][0]['value'] ?? null;
    }

    protected function exportPostUserFieldsForXml(int $postId): array
    {
        $result = [];
        $fields = $this->getPostUserFieldsWithValues($postId);
        foreach ($fields as $fieldName => $field) {
            $value = $this->exportPostUserFieldValueForXml($field);
            if ($value !== null && $value !== [] && $value !== '' && $value !== false) {
                $result[$fieldName] = $value;
            }
        }

        return $result;
    }

    protected function exportPostUserFieldValueForXml(array $field): mixed
    {
        $value = $field['VALUE'] ?? null;
        $multiple = (($field['MULTIPLE'] ?? 'N') === 'Y');

        if ($field['USER_TYPE_ID'] === 'enumeration') {
            return $this->exportUserFieldEnumValueForXml($field, $value, $multiple);
        }

        if ($field['USER_TYPE_ID'] === 'file') {
            if (empty($value)) {
                return $multiple ? [] : false;
            }

            $values = array_values(array_filter($this->makeNonEmptyArray($value)));
            return $multiple ? $values : ((int)($values[0] ?? 0) ?: false);
        }

        return $value;
    }

    protected function exportUserFieldEnumValueForXml(array $field, mixed $value, bool $multiple): mixed
    {
        if (empty($value)) {
            return $multiple ? [] : '';
        }

        $enumMap = $this->getUserFieldEnumExportMapForXml((int)$field['ID']);
        $values = [];

        foreach ($this->makeNonEmptyArray($value) as $enumId) {
            if (isset($enumMap[(int)$enumId])) {
                $values[] = $enumMap[(int)$enumId];
            }
        }

        return $multiple ? $values : ($values[0] ?? '');
    }

    protected function getUserFieldEnumExportMapForXml(int $fieldId): array
    {
        $result = [];
        $dbres = (new CUserFieldEnum())->GetList([], ['USER_FIELD_ID' => $fieldId]);
        while ($enum = $dbres->Fetch()) {
            $result[(int)$enum['ID']] = !empty($enum['XML_ID']) ? $enum['XML_ID'] : $enum['VALUE'];
        }

        return $result;
    }

    protected function createWriterUserFieldTag(string $fieldName, mixed $value): WriterTag
    {
        $tag = new WriterTag('field', ['name' => $fieldName]);
        $field = $this->getPostUserField($fieldName);
        $multiple = (($field['MULTIPLE'] ?? 'N') === 'Y');

        if (($field['USER_TYPE_ID'] ?? '') === 'file') {
            $tag->addFile($value, $multiple);
        } else {
            $tag->addValue($value, $multiple);
        }

        return $tag;
    }

    protected function readUserFieldValue(array $field): mixed
    {
        $fieldInfo = $this->getPostUserField((string)$field['name']);
        $multiple = (($fieldInfo['MULTIPLE'] ?? 'N') === 'Y');

        $values = [];
        foreach ($field['value'] as $value) {
            $values[] = $value['value'];
        }

        return $multiple ? $values : ($values[0] ?? false);
    }
}
