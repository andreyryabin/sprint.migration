<?php

namespace Sprint\Migration\Helpers\Traits\Iblock;

use CIBlockSection;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\FileExchange;
use Sprint\Migration\Locale;

trait IblockSectionTrait
{
    /**
     * Получает id секции инфоблока
     */
    public function getSectionId(int $iblockId, array|string $code): int
    {
        $item = $this->getSection($iblockId, $code);
        return (int)($item['ID'] ?? 0);
    }

    /**
     * @throws HelperException
     */
    public function getSectionIdIfExists(int $iblockId, array|string $code): int
    {
        $item = $this->getSectionIfExists($iblockId, $code);
        return (int)($item['ID'] ?? 0);
    }

    /**
     * Получает секцию инфоблока
     */
    public function getSection(int $iblockId, string|array $code): array|false
    {
        $filter = is_array($code) ? $code : ['=CODE' => $code];

        $sections = $this->getSections($iblockId, $filter);

        return (isset($sections[0])) ? $sections[0] : false;
    }

    /**
     * @throws HelperException
     */
    public function getSectionIfExists(int $iblockId, string|array $code): array
    {
        $section = $this->getSection($iblockId, $code);

        if (!empty($section['ID'])) {
            return $section;
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_IB_SECTION_ID_NOT_FOUND',
                [
                    '#IBLOCK_ID#'  => $iblockId,
                    '#SECTION_ID#' => print_r($code, true),
                ]
            )
        );
    }

    /**
     * Получает секции инфоблока
     */
    public function getSections(int $iblockId, array $filter = []): array
    {
        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';

        $dbres = CIBlockSection::GetList(
            [
                'SORT' => 'ASC',
            ], $filter, false, [
                'ID',
                'NAME',
                'CODE',
                'IBLOCK_SECTION_ID',
                'SORT',
                'ACTIVE',
                'XML_ID',
                'PICTURE',
                'DESCRIPTION',
                'DESCRIPTION_TYPE',
                'LEFT_MARGIN',
                'RIGHT_MARGIN',
                'DEPTH_LEVEL',
                'DETAIL_PICTURE',
                'UF_*',
            ]
        );

        return $this->fetchAll($dbres);
    }

    /**
     * Сохраняет категорию инфоблока,
     * создаст если не было, обновит если существует (поиск по коду)
     *
     * @throws HelperException
     */
    public function saveSectionByCode(int $iblockId, array $fields): int
    {
        $this->checkRequiredKeys($fields, ['CODE']);

        $item = $this->getSection($iblockId, $fields['CODE']);
        if (!empty($item['ID'])) {
            return $this->updateSection($item['ID'], $fields);
        }

        return $this->addSection($iblockId, $fields);
    }

    /**
     * @throws HelperException
     * @deprecated use saveSectionByCode
     */
    public function saveSection(int $iblockId, array $fields): int
    {
        return $this->saveSectionByCode($iblockId, $fields);
    }

    /**
     * Добавляет секцию инфоблока если она не существует
     *
     * @throws HelperException
     */
    public function addSectionIfNotExists(int $iblockId, array $fields): int
    {
        $this->checkRequiredKeys($fields, ['CODE']);

        $item = $this->getSection($iblockId, $fields['CODE']);
        if ($item) {
            return $item['ID'];
        }

        return $this->addSection($iblockId, $fields);
    }

    /**
     * Добавляет секцию инфоблока
     *
     * @throws HelperException
     */
    public function addSection(int $iblockId, array $fields = []): int
    {
        $default = [
            'ACTIVE'            => 'Y',
            'IBLOCK_SECTION_ID' => false,
            'NAME'              => 'section',
            'CODE'              => '',
            'SORT'              => 100,
            'PICTURE'           => false,
            'DESCRIPTION'       => '',
            'DESCRIPTION_TYPE'  => 'text',
        ];

        $fields = array_replace_recursive($default, $fields);
        $fields['IBLOCK_ID'] = $iblockId;

        $ib = new CIBlockSection;
        $id = $ib->Add($fields);

        if ($id) {
            return $id;
        }

        throw new HelperException($ib->LAST_ERROR);
    }

    /**
     * Обновляет секцию инфоблока если она существует
     *
     * @throws HelperException
     */
    public function updateSectionIfExists(int $iblockId, array $fields): false|int
    {
        $this->checkRequiredKeys($fields, ['CODE']);

        $item = $this->getSection($iblockId, $fields['CODE']);

        if (!empty($item['ID'])) {
            unset($fields['CODE']);
            return $this->updateSection($item['ID'], $fields);
        }

        return false;
    }

    /**
     * Обновляет секцию инфоблока
     *
     * @throws HelperException
     */
    public function updateSection(int $sectionId, array $fields): int
    {
        $ib = new CIBlockSection;
        if ($ib->Update($sectionId, $fields)) {
            return $sectionId;
        }

        throw new HelperException($ib->LAST_ERROR);
    }

    /**
     * Удаляет секцию инфоблока если она существует
     *
     * @throws HelperException
     */
    public function deleteSectionIfExists(int $iblockId, array|string $code): bool
    {
        $item = $this->getSection($iblockId, $code);

        if (!empty($item['ID'])) {
            return $this->deleteSection($item['ID']);
        }

        return false;
    }

    /**
     * Удаляет секцию инфоблока
     *
     * @throws HelperException
     */
    public function deleteSection(int $sectionId): bool
    {
        $ib = new CIBlockSection;
        if ($ib->Delete($sectionId)) {
            return true;
        }

        throw new HelperException($ib->LAST_ERROR);
    }

    /**
     * Возвращает ID категории по пути из названий категорий
     *
     * Пример:
     * ищем Категория3 которая находится по пути Категория1/Категория2/Категория3
     * то $path = ['Категория1','Категория2','Категория3']
     * @throws HelperException
     */
    public function getSectionIdByNamePath(int $iblockId, array $path = []): int
    {
        $sectionId = 0;
        foreach ($path as $name) {
            $sectionId = $this->getSectionIdIfExists($iblockId, [
                '=NAME'      => $name,
                'SECTION_ID' => $sectionId,
            ]);
        }
        return $sectionId;
    }

    /**
     * Возвращает путь из названий категорий до заданной
     * @throws HelperException
     */
    public function getSectionNamePathById(int $iblockId, int $sectionId, string $nameKey = 'NAME'): array
    {
        if ($sectionId <= 0) {
            return [];
        }

        $items = $this->getSectionNavChain($iblockId, $sectionId, ['ID', $nameKey]);

        foreach ($items as $item) {
            if (empty($item[$nameKey])) {
                throw new HelperException("Section with ID={$item['ID']} has empty $nameKey");
            }
        }

        return array_column($items, $nameKey);
    }

    public function getSectionNavChain(int $iblockId, int $sectionId, array $select = []): array
    {
        return CIBlockSection::GetNavChain($iblockId, $sectionId, $select, true);
    }

    /**
     * @throws HelperException
     * @deprecated use saveSectionsFromTree
     */
    public function addSectionsFromTree(int $iblockId, array $tree, $parentId = false): void
    {
        $this->saveSectionsFromTree($iblockId, $tree, $parentId);
    }

    /**
     * @throws HelperException
     */
    public function saveSectionsFromTree(int $iblockId, array $tree, $parentId = false, string $exchangeDir = ''): void
    {
        foreach ($tree as $item) {
            if (empty($item['NAME'])) {
                throw new HelperException(
                    Locale::getMessage(
                        'ERR_IB_SECTION_NAME_NOT_FOUND'
                    )
                );
            }

            $childs = [];
            if (isset($item['CHILDS'])) {
                $childs = is_array($item['CHILDS']) ? $item['CHILDS'] : [];
                unset($item['CHILDS']);
            }

            $item['IBLOCK_SECTION_ID'] = $parentId;
            $item = $this->prepareSectionForSave($iblockId, $item, $exchangeDir);
            $userFields = $this->extractSectionUserFields($item);

            $sectionId = $this->getSectionId(
                $iblockId, [
                    '=NAME'      => $item['NAME'],
                    'SECTION_ID' => $parentId,
                ]
            );

            if ($sectionId) {
                $sectionId = $this->updateSection($sectionId, $item);
            } else {
                $sectionId = $this->addSection($iblockId, $item);
            }

            $this->saveSectionUserFields($iblockId, $sectionId, $userFields);

            if (!empty($childs)) {
                $this->saveSectionsFromTree($iblockId, $childs, $sectionId, $exchangeDir);
            }
        }
    }

    public function getSectionsTree(int $iblockId): array
    {
        $sections = $this->getSections($iblockId);
        return $this->buildSectionsTree($sections);
    }

    public function exportSectionsTree(int $iblockId, string $exchangeDir = ''): array
    {
        $sections = $this->getSections($iblockId);
        return $this->buildSectionsTree($sections, 0, true, $exchangeDir, $iblockId);
    }

    protected function buildSectionsTree(
        array &$sections,
        int $parentId = 0,
        bool $export = false,
        string $exchangeDir = '',
        int $iblockId = 0
    ): array
    {
        $branch = [];
        foreach ($sections as $section) {
            if ((int)$section['IBLOCK_SECTION_ID'] == $parentId) {
                $childs = $this->buildSectionsTree($sections, $section['ID'], $export, $exchangeDir, $iblockId);

                if ($export) {
                    $section = $this->prepareSectionForExport($iblockId, $section, $exchangeDir);
                    $this->unsetKeys($section, [
                        'ID',
                        'IBLOCK_SECTION_ID',
                        'LEFT_MARGIN',
                        'RIGHT_MARGIN',
                        'DEPTH_LEVEL',
                    ]);
                }

                if (!empty($childs)) {
                    $section['CHILDS'] = $childs;
                }
                $branch[] = $section;
            }
        }
        return $branch;
    }

    protected function prepareSectionForExport(int $iblockId, array $section, string $exchangeDir = ''): array
    {
        if ($exchangeDir === '') {
            return $section;
        }

        $fileExchange = new FileExchange();

        foreach (['PICTURE', 'DETAIL_PICTURE'] as $fieldName) {
            if (!empty($section[$fieldName]) && is_numeric($section[$fieldName])) {
                $section[$fieldName] = $fileExchange->exportFileById(
                    (int)$section[$fieldName],
                    $exchangeDir,
                    'iblock_section_files'
                );
            }
        }

        $section = $this->exportSectionTextFileLinks($section, 'DESCRIPTION', $exchangeDir);
        $userFields = $this->getSectionUserFields($iblockId);

        foreach ($section as $fieldName => $value) {
            if (!str_starts_with((string)$fieldName, 'UF_') || empty($userFields[$fieldName])) {
                continue;
            }

            $section[$fieldName] = $this->exportSectionUserFieldValue(
                $userFields[$fieldName],
                $value,
                $exchangeDir
            );

            if ($this->isSectionUserFieldText($userFields[$fieldName])) {
                $section = $this->exportSectionUserFieldTextLinks(
                    $section,
                    $userFields[$fieldName],
                    (string)$fieldName,
                    $exchangeDir
                );
            }
        }

        return $section;
    }

    protected function prepareSectionForSave(int $iblockId, array $section, string $exchangeDir = ''): array
    {
        $fileLinks = [];
        if (isset($section['__FILE_LINKS']) && is_array($section['__FILE_LINKS'])) {
            $fileLinks = $section['__FILE_LINKS'];
        }
        unset($section['__FILE_LINKS']);

        if ($exchangeDir === '') {
            return $section;
        }

        $fileExchange = new FileExchange();

        foreach (['PICTURE', 'DETAIL_PICTURE'] as $fieldName) {
            if (!empty($section[$fieldName]) && is_array($section[$fieldName])) {
                $section[$fieldName] = $fileExchange->makeFileArrayByRef($section[$fieldName], $exchangeDir);
            }
        }

        if (!empty($fileLinks['DESCRIPTION'])) {
            $section['DESCRIPTION'] = $this->importSectionTextFileLinks(
                (string)($section['DESCRIPTION'] ?? ''),
                $fileLinks['DESCRIPTION'],
                $exchangeDir
            );
        }

        $userFields = $this->getSectionUserFields($iblockId);

        foreach ($section as $fieldName => $value) {
            if (!str_starts_with((string)$fieldName, 'UF_') || empty($userFields[$fieldName])) {
                continue;
            }

            $section[$fieldName] = $this->importSectionUserFieldValue(
                $userFields[$fieldName],
                $value,
                $exchangeDir
            );

            if (!empty($fileLinks[$fieldName])) {
                $section[$fieldName] = $this->importSectionUserFieldTextLinks(
                    $section[$fieldName],
                    $fileLinks[$fieldName],
                    $exchangeDir
                );
            }
        }

        return $section;
    }

    protected function exportSectionTextFileLinks(array $section, string $fieldName, string $exchangeDir): array
    {
        if (empty($section[$fieldName])) {
            return $section;
        }

        $value = $section[$fieldName];
        $text = is_array($value) ? (string)($value['TEXT'] ?? '') : (string)$value;
        $links = (new FileExchange())->exportTextFileLinks($text, $exchangeDir, 'iblock_section_text');
        if (!empty($links)) {
            $section['__FILE_LINKS'][$fieldName][0] = $links;
        }

        return $section;
    }

    protected function exportSectionUserFieldTextLinks(
        array $section,
        array $field,
        string $fieldName,
        string $exchangeDir
    ): array {
        if (empty($section[$fieldName])) {
            return $section;
        }

        $multiple = (($field['MULTIPLE'] ?? 'N') === 'Y');
        $values = $multiple ? array_values((array)$section[$fieldName]) : [$section[$fieldName]];

        foreach ($values as $index => $value) {
            $text = is_array($value) ? (string)($value['TEXT'] ?? '') : (string)$value;
            $links = (new FileExchange())->exportTextFileLinks($text, $exchangeDir, 'iblock_section_text');
            if (!empty($links)) {
                $section['__FILE_LINKS'][$fieldName][$index] = $links;
            }
        }

        return $section;
    }

    protected function importSectionTextFileLinks(string $text, array $fileLinks, string $exchangeDir): string
    {
        $linkMap = [];
        foreach ($fileLinks as $indexLinks) {
            foreach ((array)$indexLinks as $oldLink => $fileRef) {
                if (!is_array($fileRef)) {
                    continue;
                }

                $newLink = (new FileExchange())->saveFileRefAndGetPath(
                    $fileRef,
                    $exchangeDir,
                    'sprint_migration/iblock_section_text'
                );
                if ($newLink !== '') {
                    $linkMap[$oldLink] = $newLink;
                }
            }
        }

        return (new FileExchange())->replaceTextFileLinks($text, $linkMap);
    }

    protected function exportSectionUserFieldValue(array $field, mixed $value, string $exchangeDir): mixed
    {
        if ($field['USER_TYPE_ID'] === 'file') {
            return $this->exportSectionUserFieldFileValue($field, $value, $exchangeDir);
        }

        if ($field['USER_TYPE_ID'] === 'enumeration') {
            return $this->exportSectionUserFieldEnumValue($field, $value);
        }

        return $value;
    }

    protected function importSectionUserFieldValue(array $field, mixed $value, string $exchangeDir): mixed
    {
        if ($field['USER_TYPE_ID'] === 'file') {
            return $this->importSectionUserFieldFileValue($field, $value, $exchangeDir);
        }

        if ($field['USER_TYPE_ID'] === 'enumeration') {
            return $this->importSectionUserFieldEnumValue($field, $value);
        }

        return $value;
    }

    protected function exportSectionUserFieldFileValue(array $field, mixed $value, string $exchangeDir): mixed
    {
        $multiple = (($field['MULTIPLE'] ?? 'N') === 'Y');
        if (empty($value)) {
            return $multiple ? [] : false;
        }

        $items = [];
        foreach ($this->makeNonEmptyArray($value) as $fileId) {
            $fileRef = (new FileExchange())->exportFileById((int)$fileId, $exchangeDir, 'iblock_section_files');
            if ($fileRef) {
                $items[] = $fileRef;
            }
        }

        return $multiple ? $items : ($items[0] ?? false);
    }

    protected function importSectionUserFieldFileValue(array $field, mixed $value, string $exchangeDir): mixed
    {
        $multiple = (($field['MULTIPLE'] ?? 'N') === 'Y');
        if (empty($value)) {
            return $multiple ? [] : false;
        }

        $items = [];
        foreach ($this->makeSectionUserFieldFileRefs($value, $multiple) as $fileRef) {
            if (!is_array($fileRef)) {
                continue;
            }

            $file = (new FileExchange())->makeFileArrayByRef($fileRef, $exchangeDir);
            if ($file) {
                $items[] = $file;
            }
        }

        return $multiple ? $items : ($items[0] ?? false);
    }

    protected function makeSectionUserFieldFileRefs(mixed $value, bool $multiple): array
    {
        if (!$multiple && is_array($value) && isset($value['path'])) {
            return [$value];
        }

        return $this->makeNonEmptyArray($value);
    }

    protected function exportSectionUserFieldEnumValue(array $field, mixed $value): mixed
    {
        $multiple = (($field['MULTIPLE'] ?? 'N') === 'Y');
        if (empty($value)) {
            return $multiple ? [] : '';
        }

        $map = $this->getSectionUserFieldEnumExportMap((int)$field['ID']);
        $items = [];
        foreach ($this->makeNonEmptyArray($value) as $enumId) {
            if (isset($map[(int)$enumId])) {
                $items[] = $map[(int)$enumId];
            }
        }

        return $multiple ? $items : ($items[0] ?? '');
    }

    protected function importSectionUserFieldEnumValue(array $field, mixed $value): mixed
    {
        $multiple = (($field['MULTIPLE'] ?? 'N') === 'Y');
        if (empty($value)) {
            return $multiple ? [] : false;
        }

        $map = $this->getSectionUserFieldEnumImportMap((int)$field['ID']);
        $items = [];
        foreach ($this->makeNonEmptyArray($value) as $enumXmlId) {
            if (isset($map[(string)$enumXmlId])) {
                $items[] = $map[(string)$enumXmlId];
            }
        }

        return $multiple ? $items : ($items[0] ?? false);
    }

    protected function importSectionUserFieldTextLinks(mixed $value, array $fileLinks, string $exchangeDir): mixed
    {
        $isFormattedSingle = is_array($value) && (array_key_exists('TEXT', $value) || array_key_exists('TYPE', $value));
        $values = (is_array($value) && !$isFormattedSingle) ? array_values($value) : [$value];

        foreach ($values as $index => $item) {
            if (empty($fileLinks[$index])) {
                continue;
            }

            $text = is_array($item) ? (string)($item['TEXT'] ?? '') : (string)$item;
            $text = $this->importSectionTextFileLinks($text, [$fileLinks[$index]], $exchangeDir);

            if (is_array($item)) {
                $item['TEXT'] = $text;
                $values[$index] = $item;
            } else {
                $values[$index] = $text;
            }
        }

        return (is_array($value) && !$isFormattedSingle) ? $values : ($values[0] ?? $value);
    }

    protected function getSectionUserFields(int $iblockId): array
    {
        $result = [];
        if (!class_exists('\CUserTypeEntity')) {
            return $result;
        }

        $dbres = \CUserTypeEntity::GetList([], ['ENTITY_ID' => 'IBLOCK_' . $iblockId . '_SECTION']);
        while ($field = $dbres->Fetch()) {
            $result[$field['FIELD_NAME']] = $field;
        }

        return $result;
    }

    protected function getSectionUserFieldEnumExportMap(int $fieldId): array
    {
        $result = [];
        $dbres = (new \CUserFieldEnum())->GetList([], ['USER_FIELD_ID' => $fieldId]);
        while ($enum = $dbres->Fetch()) {
            $result[(int)$enum['ID']] = (string)$enum['XML_ID'];
        }

        return $result;
    }

    protected function getSectionUserFieldEnumImportMap(int $fieldId): array
    {
        $result = [];
        $dbres = (new \CUserFieldEnum())->GetList([], ['USER_FIELD_ID' => $fieldId]);
        while ($enum = $dbres->Fetch()) {
            $result[(string)$enum['XML_ID']] = (int)$enum['ID'];
        }

        return $result;
    }

    protected function isSectionUserFieldText(array $field): bool
    {
        return in_array($field['USER_TYPE_ID'], ['string', 'string_formatted', 'html']);
    }

    protected function extractSectionUserFields(array &$section): array
    {
        $userFields = [];
        foreach ($section as $fieldName => $value) {
            if (str_starts_with((string)$fieldName, 'UF_')) {
                $userFields[$fieldName] = $value;
                unset($section[$fieldName]);
            }
        }

        return $userFields;
    }

    protected function saveSectionUserFields(int $iblockId, int $sectionId, array $userFields): void
    {
        if (empty($userFields) || empty($GLOBALS['USER_FIELD_MANAGER'])) {
            return;
        }

        $GLOBALS['USER_FIELD_MANAGER']->Update(
            'IBLOCK_' . $iblockId . '_SECTION',
            $sectionId,
            $userFields
        );
    }
}
