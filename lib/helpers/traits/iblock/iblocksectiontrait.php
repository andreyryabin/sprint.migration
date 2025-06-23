<?php

namespace Sprint\Migration\Helpers\Traits\Iblock;

use CIBlockSection;
use Sprint\Migration\Exceptions\HelperException;
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
    public function saveSection(int $iblockId, array $fields): int
    {
        $this->checkRequiredKeys($fields, ['CODE']);

        $item = $this->getSection($iblockId, $fields['CODE']);
        if (!empty($item['ID'])) {
            return $this->updateSection($item['ID'], $fields);
        }

        return $this->addSection($iblockId, $fields);
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
     */
    public function getSectionIdByNamePath(int $iblockId, array $path = []): int
    {
        $sectionId = 0;
        foreach ($path as $name) {
            $sectionId = $this->getSectionId($iblockId, [
                '=NAME'      => $name,
                'SECTION_ID' => $sectionId,
            ]);
        }
        return $sectionId;
    }

    /**
     * Возвращает путь из названий категорий до заданной
     */
    public function getSectionNamePathById(int $iblockId, int $sectionId): array
    {
        if ($sectionId > 0) {
            $items = CIBlockSection::GetNavChain($iblockId, $sectionId, ['ID', 'NAME'], true);
            return array_column($items, 'NAME');
        } else {
            return [];
        }
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
    public function saveSectionsFromTree(int $iblockId, array $tree, $parentId = false): void
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

            if (!empty($childs)) {
                $this->saveSectionsFromTree($iblockId, $childs, $sectionId);
            }
        }
    }

    public function getSectionsTree(int $iblockId): array
    {
        $sections = $this->getSections($iblockId);
        return $this->buildSectionsTree($sections);
    }

    public function exportSectionsTree(int $iblockId): array
    {
        $sections = $this->getSections($iblockId);
        return $this->buildSectionsTree($sections, 0, true);
    }

    protected function buildSectionsTree(array &$sections, int $parentId = 0, bool $export = false): array
    {
        $branch = [];
        foreach ($sections as $section) {
            if ((int)$section['IBLOCK_SECTION_ID'] == $parentId) {
                $childs = $this->buildSectionsTree($sections, $section['ID'], $export);

                if ($export) {
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
}
