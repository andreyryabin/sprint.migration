<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\WriterTag;
use Sprint\Migration\Interfaces\ReaderHelperInterface;
use Sprint\Migration\Interfaces\WriterHelperInterface;

class IblockSectionExchangeHelper extends IblockHelper implements ReaderHelperInterface, WriterHelperInterface
{
    protected array $cachedFields = [];

    /**
     * @throws HelperException
     */
    public function getWriterAttributes(...$vars): array
    {
        [$iblockId] = $vars;

        return [
            'iblockUid' => $this->getIblockUid((int)$iblockId),
        ];
    }

    public function getWriterRecordsCount(...$vars): int
    {
        [$iblockId] = $vars;

        return count($this->getSections((int)$iblockId));
    }

    /**
     * @throws HelperException
     */
    public function getWriterRecordsTag(int $offset, int $limit, ...$vars): WriterTag
    {
        [$iblockId] = $vars;

        $tag = new WriterTag('tmp');
        foreach ($this->getSectionSlice((int)$iblockId, $offset, $limit) as $section) {
            $tag->addChild($this->createWriterRecordTag((int)$iblockId, $section));
        }

        return $tag;
    }

    /**
     * @throws HelperException
     */
    public function convertReaderRecords(array $attributes, array $records): array
    {
        $iblockId = $this->getIblockIdByUid($attributes['iblockUid']);

        return array_map(
            fn($record) => [
                'iblock_id' => $iblockId,
                'fields'    => $this->convertReaderRecord($iblockId, $record),
            ],
            $records
        );
    }

    /**
     * @throws HelperException
     */
    protected function getSectionSlice(int $iblockId, int $offset, int $limit): array
    {
        $sections = $this->ensureSectionXmlIds($this->getSections($iblockId));
        usort($sections, fn($a, $b) => ((int)$a['LEFT_MARGIN'] <=> (int)$b['LEFT_MARGIN']));

        return array_slice($sections, $offset, $limit);
    }

    /**
     * @throws HelperException
     */
    protected function ensureSectionXmlIds(array $sections): array
    {
        foreach ($sections as $index => $section) {
            $sections[$index] = $this->ensureSectionXmlId($section);
        }

        return $sections;
    }

    /**
     * @throws HelperException
     */
    protected function ensureSectionXmlId(array $section): array
    {
        if (!empty($section['XML_ID'])) {
            return $section;
        }

        $section['XML_ID'] = $this->makeSectionXmlId($section);
        $this->updateSection((int)$section['ID'], ['XML_ID' => $section['XML_ID']]);

        return $section;
    }

    protected function makeSectionXmlId(array $section): string
    {
        $slug = $this->makeCodeSlug((string)($section['NAME'] ?? ''));
        if ($slug === '') {
            $slug = 'SECTION';
        }

        return sprintf('IBLOCK_SECTION_%d_%s', (int)$section['ID'], $slug);
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

        return (string)preg_replace('/_+/', '_', $value);
    }

    /**
     * @throws HelperException
     */
    protected function createWriterRecordTag(int $iblockId, array $section): WriterTag
    {
        $item = new WriterTag('item');

        foreach ($this->prepareSectionForXml($section) as $name => $value) {
            if (str_starts_with((string)$name, 'UF_')) {
                $field = $this->createWriterUserFieldTag($iblockId, (string)$name, $value);
            } else {
                $field = new WriterTag('field', ['name' => $name]);
                $this->writeSectionFieldValue($field, $iblockId, (string)$name, $value);
            }

            $item->addChild($field);
        }

        return $item;
    }

    protected function prepareSectionForXml(array $section): array
    {
        $this->unsetKeys($section, [
            'ID',
            'IBLOCK_ID',
            'LEFT_MARGIN',
            'RIGHT_MARGIN',
            'DEPTH_LEVEL',
        ]);

        return $section;
    }

    /**
     * @throws HelperException
     */
    protected function writeSectionFieldValue(WriterTag $tag, int $iblockId, string $name, mixed $value): void
    {
        if ($name === 'PICTURE' || $name === 'DETAIL_PICTURE') {
            $tag->addFile((int)$value, false);
        } elseif ($name === 'IBLOCK_SECTION_ID') {
            if (!empty($value)) {
                $this->writeParentSectionRef($tag, $iblockId, (int)$value);
            }
        } else {
            $tag->addValue($value, false);
        }
    }

    /**
     * @throws HelperException
     */
    protected function writeParentSectionRef(WriterTag $tag, int $iblockId, int $sectionId): void
    {
        $this->writeSectionRef($tag, $iblockId, $sectionId);
    }

    /**
     * @throws HelperException
     */
    protected function writeSectionRef(WriterTag $tag, int $iblockId, int $sectionId): void
    {
        $section = $this->getSectionIfExists($iblockId, ['ID' => $sectionId]);
        $section = $this->ensureSectionXmlId($section);

        $tag->addValueTag(
            $section['NAME'],
            array_filter([
                'section_xml_id' => $section['XML_ID'] ?? '',
                'section_code'   => $section['CODE'] ?? '',
            ])
        );
    }

    /**
     * @throws HelperException
     */
    protected function convertReaderRecord(int $iblockId, array $record): array
    {
        $fields = [];

        foreach ($record['fields'] as $field) {
            if (empty($field['name'])) {
                continue;
            }

            if (str_starts_with((string)$field['name'], 'UF_')) {
                $fields[$field['name']] = $this->readUserFieldValue($iblockId, $field);
            } elseif ($field['name'] === 'IBLOCK_SECTION_ID') {
                $fields[$field['name']] = $this->readParentSection($iblockId, $field);
            } else {
                $fields[$field['name']] = $this->readFieldValue($field);
            }
        }

        return $fields;
    }

    /**
     * @throws HelperException
     */
    protected function readParentSection(int $iblockId, array $field): int|false
    {
        if (empty($field['value'])) {
            return false;
        }

        return $this->readSectionRef($iblockId, $field['value'][0]);
    }

    /**
     * @throws HelperException
     */
    protected function readSectionRef(int $iblockId, array $value): int
    {
        if (!empty($value['section_xml_id'])) {
            return $this->getSectionIdIfExists($iblockId, ['=XML_ID' => $value['section_xml_id']]);
        }

        if (!empty($value['section_code'])) {
            return $this->getSectionIdIfExists($iblockId, ['=CODE' => $value['section_code']]);
        }

        throw new HelperException("Section reference is empty");
    }

    protected function readFieldValue(array $field): mixed
    {
        return $field['value'][0]['value'] ?? null;
    }

    /**
     * @throws HelperException
     */
    protected function createWriterUserFieldTag(int $iblockId, string $fieldName, mixed $value): WriterTag
    {
        $tag = new WriterTag('field', ['name' => $fieldName]);
        $field = $this->getSectionUserField($iblockId, $fieldName);
        $multiple = (($field['MULTIPLE'] ?? 'N') === 'Y');

        if (($field['USER_TYPE_ID'] ?? '') === 'enumeration') {
            $tag->addValue($this->getUserFieldEnumXmlIdsByIds($field, $value), true);
        } elseif (($field['USER_TYPE_ID'] ?? '') === 'file') {
            $tag->addFile($value, $multiple);
        } elseif (($field['USER_TYPE_ID'] ?? '') === 'iblock_element') {
            $this->writeUserFieldIblockElement($tag, $field, $value);
        } elseif (($field['USER_TYPE_ID'] ?? '') === 'iblock_section') {
            $this->writeUserFieldIblockSection($tag, $field, $value);
        } elseif (($field['USER_TYPE_ID'] ?? '') === 'hlblock') {
            $this->writeUserFieldHlblockElement($tag, $field, $value);
        } else {
            $tag->addValue($value, $multiple);
        }

        return $tag;
    }

    /**
     * @throws HelperException
     */
    protected function readUserFieldValue(int $iblockId, array $field): mixed
    {
        $fieldInfo = $this->getSectionUserField($iblockId, (string)$field['name']);
        $fieldType = $fieldInfo['USER_TYPE_ID'] ?? '';

        if ($fieldType === 'enumeration') {
            return $this->readUserFieldEnumeration($fieldInfo, $field);
        }

        if ($fieldType === 'iblock_element') {
            return $this->readUserFieldIblockElement($fieldInfo, $field);
        }

        if ($fieldType === 'iblock_section') {
            return $this->readUserFieldIblockSection($fieldInfo, $field);
        }

        if ($fieldType === 'hlblock') {
            return $this->readUserFieldHlblockElement($fieldInfo, $field);
        }

        return $this->readUserFieldRawValue($fieldInfo, $field);
    }

    protected function readUserFieldRawValue(array $fieldInfo, array $field): mixed
    {
        $values = array_map(fn($value) => $value['value'], $field['value']);

        return (($fieldInfo['MULTIPLE'] ?? 'N') === 'Y') ? $values : ($values[0] ?? false);
    }

    protected function readUserFieldEnumeration(array $fieldInfo, array $field): mixed
    {
        $values = array_map(
            fn($value) => $this->getUserFieldEnumIdByXmlId($fieldInfo, $value['value']),
            $field['value']
        );

        return (($fieldInfo['MULTIPLE'] ?? 'N') === 'Y') ? $values : ($values[0] ?? false);
    }

    /**
     * @throws HelperException
     */
    protected function readUserFieldIblockElement(array $fieldInfo, array $field): mixed
    {
        $linkedIblockId = (int)($fieldInfo['SETTINGS']['IBLOCK_ID'] ?? 0);
        if (!$linkedIblockId) {
            return (($fieldInfo['MULTIPLE'] ?? 'N') === 'Y') ? [] : false;
        }

        $values = array_map(
            fn($value) => (new IblockExchangeHelper())->readValueIblockElement($linkedIblockId, $value),
            $field['value']
        );

        return (($fieldInfo['MULTIPLE'] ?? 'N') === 'Y') ? $values : ($values[0] ?? false);
    }

    /**
     * @throws HelperException
     */
    protected function readUserFieldIblockSection(array $fieldInfo, array $field): mixed
    {
        $linkedIblockId = (int)($fieldInfo['SETTINGS']['IBLOCK_ID'] ?? 0);
        if (!$linkedIblockId) {
            return (($fieldInfo['MULTIPLE'] ?? 'N') === 'Y') ? [] : false;
        }

        $values = array_map(
            fn($value) => $this->readSectionRef($linkedIblockId, $value),
            $field['value']
        );

        return (($fieldInfo['MULTIPLE'] ?? 'N') === 'Y') ? $values : ($values[0] ?? false);
    }

    /**
     * @throws HelperException
     */
    protected function readUserFieldHlblockElement(array $fieldInfo, array $field): mixed
    {
        $linkedHlblockId = (int)($fieldInfo['SETTINGS']['HLBLOCK_ID'] ?? 0);
        if (!$linkedHlblockId) {
            return (($fieldInfo['MULTIPLE'] ?? 'N') === 'Y') ? [] : false;
        }

        $values = array_map(
            fn($value) => (new HlblockExchangeHelper())->readValueHlblockElement($linkedHlblockId, $value),
            $field['value']
        );

        return (($fieldInfo['MULTIPLE'] ?? 'N') === 'Y') ? $values : ($values[0] ?? false);
    }

    /**
     * @throws HelperException
     */
    protected function writeUserFieldIblockElement(WriterTag $tag, array $field, mixed $value): void
    {
        $linkedIblockId = (int)($field['SETTINGS']['IBLOCK_ID'] ?? 0);
        if (!$linkedIblockId) {
            return;
        }

        foreach ($this->makeNonEmptyArray($value) as $elementId) {
            (new IblockExchangeHelper())->writeValueIblockElement($tag, $linkedIblockId, (int)$elementId);
        }
    }

    /**
     * @throws HelperException
     */
    protected function writeUserFieldIblockSection(WriterTag $tag, array $field, mixed $value): void
    {
        $linkedIblockId = (int)($field['SETTINGS']['IBLOCK_ID'] ?? 0);
        if (!$linkedIblockId) {
            return;
        }

        foreach ($this->makeNonEmptyArray($value) as $sectionId) {
            $this->writeSectionRef($tag, $linkedIblockId, (int)$sectionId);
        }
    }

    /**
     * @throws HelperException
     */
    protected function writeUserFieldHlblockElement(WriterTag $tag, array $field, mixed $value): void
    {
        $linkedHlblockId = (int)($field['SETTINGS']['HLBLOCK_ID'] ?? 0);
        if (!$linkedHlblockId) {
            return;
        }

        foreach ($this->makeNonEmptyArray($value) as $elementId) {
            (new HlblockExchangeHelper())->writeValueHlblockElement($tag, $linkedHlblockId, (int)$elementId);
        }
    }

    protected function getUserFieldEnumXmlIdsByIds(array $fieldInfo, mixed $enumIds): array
    {
        $values = [];
        foreach ($this->makeNonEmptyArray($enumIds) as $enumId) {
            foreach (($fieldInfo['ENUM_VALUES'] ?? []) as $enum) {
                if ((int)$enum['ID'] === (int)$enumId) {
                    $values[] = !empty($enum['XML_ID']) ? $enum['XML_ID'] : $enum['VALUE'];
                    break;
                }
            }
        }

        return $values;
    }

    protected function getUserFieldEnumIdByXmlId(array $fieldInfo, mixed $xmlId): int|false
    {
        foreach (($fieldInfo['ENUM_VALUES'] ?? []) as $enum) {
            if (!empty($enum['XML_ID']) && (string)$enum['XML_ID'] === (string)$xmlId) {
                return (int)$enum['ID'];
            }
            if ((string)$enum['VALUE'] === (string)$xmlId) {
                return (int)$enum['ID'];
            }
        }

        return false;
    }

    /**
     * @throws HelperException
     */
    protected function getSectionUserField(int $iblockId, string $fieldName): array
    {
        $key = $iblockId . ':' . $fieldName;

        if (!isset($this->cachedFields[$key])) {
            $field = (new UserTypeEntityHelper())->getUserTypeEntity(
                'IBLOCK_' . $iblockId . '_SECTION',
                $fieldName
            );

            $this->cachedFields[$key] = is_array($field) ? $field : [];
        }

        return $this->cachedFields[$key];
    }
}
