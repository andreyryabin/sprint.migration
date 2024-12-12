<?php

namespace Sprint\Migration\Helpers\Traits\Iblock;

use CIBlockType;
use CLanguage;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Locale;

trait IblockTypeTrait
{

    /**
     * Получает тип инфоблока, бросает исключение если его не существует
     * @param $typeId
     * @throws HelperException
     * @return array|void
     */
    public function getIblockTypeIfExists($typeId)
    {
        $item = $this->getIblockType($typeId);
        if ($item && isset($item['ID'])) {
            return $item;
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_IB_TYPE_NOT_FOUND'
            )
        );
    }

    /**
     * Получает id типа инфоблока, бросает исключение если его не существует
     * @param $typeId
     * @throws HelperException
     * @return int|void
     */
    public function getIblockTypeIdIfExists($typeId)
    {
        $item = $this->getIblockType($typeId);
        if ($item && isset($item['ID'])) {
            return $item['ID'];
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_IB_TYPE_NOT_FOUND'
            )
        );
    }


    /**
     * Получает тип инфоблока
     * @param $typeId
     * @return array
     */
    public function getIblockType($typeId)
    {
        /** @compatibility filter or $typeId */
        $filter = is_array($typeId) ? $typeId : [
            '=ID' => $typeId,
        ];

        $filter['CHECK_PERMISSIONS'] = 'N';
        $item = CIBlockType::GetList(['SORT' => 'ASC'], $filter)->Fetch();

        if ($item) {
            $item['LANG'] = $this->getIblockTypeLangs($item['ID']);
        }

        return $item;
    }

    /**
     * Получает id типа инфоблока
     * @param $typeId
     * @return int|mixed
     */
    public function getIblockTypeId($typeId)
    {
        $iblockType = $this->getIblockType($typeId);
        return ($iblockType && isset($iblockType['ID'])) ? $iblockType['ID'] : 0;
    }

    /**
     * Получает типы инфоблоков
     * @param array $filter
     * @return array
     */
    public function getIblockTypes($filter = [])
    {
        $filter['CHECK_PERMISSIONS'] = 'N';
        $dbres = CIBlockType::GetList(['SORT' => 'ASC'], $filter);

        $list = [];
        while ($item = $dbres->Fetch()) {
            $item['LANG'] = $this->getIblockTypeLangs($item['ID']);
            $list[] = $item;
        }
        return $list;
    }

    /**
     * Добавляет тип инфоблока, если его не существует
     * @param array $fields
     * @throws HelperException
     * @return mixed
     */
    public function addIblockTypeIfNotExists($fields = [])
    {
        $this->checkRequiredKeys($fields, ['ID']);

        $iblockType = $this->getIblockType($fields['ID']);
        if ($iblockType) {
            return $iblockType['ID'];
        }

        return $this->addIblockType($fields);
    }

    /**
     * Добавляет тип инфоблока
     * @param array $fields
     * @throws HelperException
     * @return int|void
     */
    public function addIblockType($fields = [])
    {
        $default = [
            'ID' => '',
            'SECTIONS' => 'Y',
            'IN_RSS' => 'N',
            'SORT' => 100,
            'LANG' => [
                'ru' => [
                    'NAME' => 'Catalog',
                    'SECTION_NAME' => 'Sections',
                    'ELEMENT_NAME' => 'Elements',
                ],
                'en' => [
                    'NAME' => 'Catalog',
                    'SECTION_NAME' => 'Sections',
                    'ELEMENT_NAME' => 'Elements',
                ],
            ],
        ];

        $fields = array_replace_recursive($default, $fields);

        $ib = new CIBlockType;
        if ($ib->Add($fields)) {
            return $fields['ID'];
        }

        throw new HelperException($ib->LAST_ERROR);
    }

    /**
     * Обновляет тип инфоблока
     * @param $iblockTypeId
     * @param array $fields
     * @throws HelperException
     * @return int|void
     */
    public function updateIblockType($iblockTypeId, $fields = [])
    {
        $ib = new CIBlockType;
        if ($ib->Update($iblockTypeId, $fields)) {
            return $iblockTypeId;
        }

        throw new HelperException($ib->LAST_ERROR);
    }

    /**
     * Удаляет тип инфоблока, если существует
     * @param $typeId
     * @throws HelperException
     * @return bool
     */
    public function deleteIblockTypeIfExists($typeId)
    {
        $iblockType = $this->getIblockType($typeId);
        if (!$iblockType) {
            return false;
        }

        return $this->deleteIblockType($iblockType['ID']);

    }

    /**
     * Удаляет тип инфоблока
     * @param $typeId
     * @throws HelperException
     * @return bool|void
     */
    public function deleteIblockType($typeId)
    {
        if (CIBlockType::Delete($typeId)) {
            return true;
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_CANT_DELETE_IBLOCK_TYPE', [
                    '#NAME#' => $typeId,
                ]
            )
        );
    }

    /**
     * Получает языковые названия для типа инфоблока
     * @param $typeId
     * @return array
     */
    public function getIblockTypeLangs($typeId)
    {
        $lby = 'sort';
        $lorder = 'asc';

        $result = [];
        $dbres = CLanguage::GetList($lby, $lorder);
        while ($item = $dbres->GetNext()) {
            $values = CIBlockType::GetByIDLang($typeId, $item['LID'], false);
            if (!empty($values)) {
                $result[$item['LID']] = [
                    'NAME' => $values['NAME'],
                    'SECTION_NAME' => $values['SECTION_NAME'],
                    'ELEMENT_NAME' => $values['ELEMENT_NAME'],
                ];
            }
        }
        return $result;
    }

    /**
     * Сохраняет тип инфоблока
     * Создаст если не было, обновит если существует и отличается
     * @param array $fields
     * @throws HelperException
     * @return bool|mixed
     */
    public function saveIblockType($fields = [])
    {
        $this->checkRequiredKeys($fields, ['ID']);

        $exists = $this->getIblockType($fields['ID']);
        $fields = $this->prepareExportIblockType($fields);

        if (empty($exists)) {
            $ok = $this->getMode('test') ? true : $this->addIblockType($fields);
            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'IB_TYPE_CREATED',
                    [
                        '#NAME#' => $fields['ID'],
                    ]
                )
            );
            return $ok;
        }

        $exportExists = $this->prepareExportIblockType($exists);
        if ($this->hasDiff($exportExists, $fields)) {
            $ok = $this->getMode('test') ? true : $this->updateIblockType($exists['ID'], $fields);
            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'IB_TYPE_UPDATED',
                    [
                        '#NAME#' => $fields['ID'],
                    ]
                )
            );
            $this->outDiffIf($ok, $exportExists, $fields);

            return $ok;
        }

        return $this->getMode('test') ? true : $fields['ID'];
    }

    /**
     * Получает тип инфоблока
     * Данные подготовлены для экспорта в миграцию или схему
     * @param $typeId
     * @return mixed
     */
    public function exportIblockType($typeId)
    {
        return $this->prepareExportIblockType(
            $this->getIblockType($typeId)
        );
    }

    /**
     * @param $typeId
     * @throws HelperException
     * @return array
     * @deprecated
     */
    public function findIblockType($typeId)
    {
        return $this->getIblockTypeIfExists($typeId);
    }

    /**
     * @param $typeId
     * @throws HelperException
     * @return mixed
     * @deprecated
     */
    public function findIblockTypeId($typeId)
    {
        return $this->getIblockTypeIdIfExists($typeId);
    }

    protected function prepareExportIblockType($item)
    {
        if (empty($item)) {
            return $item;
        }

        return $item;
    }
}
