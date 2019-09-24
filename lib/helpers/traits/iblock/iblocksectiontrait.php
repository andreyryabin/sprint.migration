<?php

namespace Sprint\Migration\Helpers\Traits\Iblock;

use CIBlockSection;
use Sprint\Migration\Exceptions\HelperException;

trait IblockSectionTrait
{

    /**
     * Получает секцию инфоблока
     * @param $iblockId
     * @param $code string|array - код или фильтр
     * @return array|false
     */
    public function getSection($iblockId, $code)
    {
        /** @compatibility filter or code */
        $filter = is_array($code) ? $code : [
            '=CODE' => $code,
        ];

        $sections = $this->getSections($iblockId, $filter);
        return (isset($sections[0])) ? $sections[0] : false;
    }

    /**
     * Получает id секции инфоблока
     * @param $iblockId
     * @param $code string|array - код или фильтр
     * @return int|mixed
     */
    public function getSectionId($iblockId, $code)
    {
        $item = $this->getSection($iblockId, $code);
        return ($item && isset($item['ID'])) ? $item['ID'] : 0;
    }

    /**
     * Получает секции инфоблока
     * @param $iblockId
     * @param array $filter
     * @return array
     */
    public function getSections($iblockId, $filter = [])
    {
        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbres = CIBlockSection::GetList([
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
        ]);

        return $this->fetchAll($dbres);
    }

    /**
     * Добавляет секцию инфоблока если она не существует
     * @param $iblockId
     * @param $fields , обязательные параметры - код сеции
     * @throws HelperException
     * @return bool|int|mixed
     */
    public function addSectionIfNotExists($iblockId, $fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['CODE']);

        $item = $this->getSection($iblockId, $fields['CODE']);
        if ($item) {
            return $item['ID'];
        }

        return $this->addSection($iblockId, $fields);

    }

    /**
     * Добавляет секцию инфоблока
     * @param $iblockId
     * @param array $fields
     * @throws HelperException
     * @return int|void
     */
    public function addSection($iblockId, $fields = [])
    {
        $default = [
            'ACTIVE' => 'Y',
            'IBLOCK_SECTION_ID' => false,
            'NAME' => 'section',
            'CODE' => '',
            'SORT' => 100,
            'PICTURE' => false,
            'DESCRIPTION' => '',
            'DESCRIPTION_TYPE' => 'text',
        ];

        $fields = array_replace_recursive($default, $fields);
        $fields['IBLOCK_ID'] = $iblockId;

        $ib = new CIBlockSection;
        $id = $ib->Add($fields);

        if ($id) {
            return $id;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    /**
     * Обновляет секцию инфоблока если она существует
     * @param $iblockId
     * @param $fields , обязательные параметры - код секции
     * @throws HelperException
     * @return int|void
     */
    public function updateSectionIfExists($iblockId, $fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['CODE']);

        $item = $this->getSection($iblockId, $fields['CODE']);
        if (!$item) {
            return false;
        }

        unset($fields['CODE']);

        return $this->updateSection($item['ID'], $fields);

    }

    /**
     * Обновляет секцию инфоблока
     * @param $sectionId
     * @param $fields
     * @throws HelperException
     * @return int|void
     */
    public function updateSection($sectionId, $fields)
    {
        $ib = new CIBlockSection;
        if ($ib->Update($sectionId, $fields)) {
            return $sectionId;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    /**
     * Удаляет секцию инфоблока если она существует
     * @param $iblockId
     * @param $code
     * @throws HelperException
     * @return bool|void
     */
    public function deleteSectionIfExists($iblockId, $code)
    {
        $item = $this->getSection($iblockId, $code);
        if (!$item) {
            return false;
        }

        return $this->deleteSection($item['ID']);

    }

    /**
     * Удаляет секцию инфоблока
     * @param $sectionId
     * @throws HelperException
     * @return bool|void
     */
    public function deleteSection($sectionId)
    {
        $ib = new CIBlockSection;
        if ($ib->Delete($sectionId)) {
            return true;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    /**
     * Возвращает ID категории по пути из названий категорий
     *
     * Пример:
     * ищем Категория3 которая находится по пути Категория1/Категория2/Категория3
     * то $path = ['Категория1','Категория2','Категория3']
     *
     * @param $iblockId
     * @param array $path
     * @return int|mixed
     */
    public function getSectionIdByNamePath($iblockId, $path = [])
    {
        $sectionId = 0;
        foreach ($path as $name) {
            $sectionId = $this->getSectionId($iblockId, [
                '=NAME' => $name,
                'SECTION_ID' => $sectionId,
            ]);
        }
        return $sectionId;
    }

    /**
     * Возвращает путь из названий категорий до заданной
     *
     * @param $iblockId
     * @param $sectionId
     * @return array
     */
    public function getSectionNamePathById($iblockId, $sectionId)
    {
        $sectionId = intval($sectionId);
        if ($sectionId > 0) {
            $items = CIBlockSection::GetNavChain($iblockId, $sectionId, ['ID', 'NAME'], true);
            return array_column($items, 'NAME');
        } else {
            return [];
        }
    }

    /**
     * @param $iblockId
     * @param $tree
     * @param bool $parentId
     * @throws HelperException
     */
    public function addSectionsFromTree($iblockId, $tree, $parentId = false)
    {
        foreach ($tree as $item) {
            if (empty($item['NAME'])) {
                $this->throwException(__METHOD__, 'Section name not found');
            }

            $childs = [];
            if (isset($item['CHILDS'])) {
                $childs = is_array($item['CHILDS']) ? $item['CHILDS'] : [];
                unset($item['CHILDS']);
            }

            $item['IBLOCK_SECTION_ID'] = $parentId;

            $sectionId = $this->getSectionId(
                $iblockId, [
                    '=NAME' => $item['NAME'],
                    'SECTION_ID' => $parentId,
                ]
            );

            if (empty($sectionId)) {
                $sectionId = $this->addSection($iblockId, $item);
            }

            if (!empty($childs)) {
                $this->addSectionsFromTree($iblockId, $childs, $sectionId);
            }
        }
    }

    /**
     * @param $iblockId
     * @return array
     */
    public function getSectionsTree($iblockId)
    {
        $sections = $this->getSections($iblockId);
        return $this->buildSectionsTree($sections, 0, false);
    }

    /**
     * @param $iblockId
     * @return array
     */
    public function exportSectionsTree($iblockId)
    {
        $sections = $this->getSections($iblockId);
        return $this->buildSectionsTree($sections, 0, true);
    }

    protected function buildSectionsTree(array &$sections, $parentId = 0, $export = false)
    {
        $branch = [];
        foreach ($sections as $section) {
            if ((int)$section['IBLOCK_SECTION_ID'] == $parentId) {

                $childs = $this->buildSectionsTree($sections, $section['ID'], $export);

                if ($export) {
                    unset($section['ID']);
                    unset($section['IBLOCK_SECTION_ID']);
                    unset($section['LEFT_MARGIN']);
                    unset($section['RIGHT_MARGIN']);
                    unset($section['DEPTH_LEVEL']);
                    unset($section['PICTURE']);
                    unset($section['DETAIL_PICTURE']);
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
