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
     *
     * @throws HelperException
     */
    public function getIblockTypeIfExists($typeId): array
    {
        $item = $this->getIblockType($typeId);
        if (!empty($item['ID'])) {
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
     *
     * @throws HelperException
     */
    public function getIblockTypeIdIfExists(array|string $typeId): string
    {
        $item = $this->getIblockType($typeId);
        if (!empty($item['ID'])) {
            return (string)$item['ID'];
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_IB_TYPE_NOT_FOUND'
            )
        );
    }

    /**
     * Получает тип инфоблока
     */
    public function getIblockType(array|string $typeId): bool|array
    {
        $filter = is_array($typeId) ? $typeId : ['=ID' => $typeId];

        $filter['CHECK_PERMISSIONS'] = 'N';
        $item = CIBlockType::GetList(['SORT' => 'ASC'], $filter)->Fetch();

        if (is_array($item) && !empty($item['ID'])) {
            $item['LANG'] = $this->getIblockTypeLangs($item['ID']);
            return $item;
        }

        return false;
    }

    /**
     * Получает id типа инфоблока
     */
    public function getIblockTypeId($typeId): string
    {
        $item = $this->getIblockType($typeId);
        return !empty($item['ID']) ? (string)$item['ID'] : '';
    }

    /**
     * Получает типы инфоблоков
     */
    public function getIblockTypes(array $filter = []): array
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
     *
     * @throws HelperException
     */
    public function addIblockTypeIfNotExists(array $fields = []): string
    {
        $this->checkRequiredKeys($fields, ['ID']);

        $item = $this->getIblockType($fields['ID']);
        if (!empty($item['ID'])) {
            return (string)$item['ID'];
        }

        return $this->addIblockType($fields);
    }

    /**
     * Добавляет тип инфоблока
     *
     * @throws HelperException
     */
    public function addIblockType(array $fields = []): string
    {
        $default = [
            'ID'       => '',
            'SECTIONS' => 'Y',
            'IN_RSS'   => 'N',
            'SORT'     => 100,
            'LANG'     => [
                'ru' => [
                    'NAME'         => 'Catalog',
                    'SECTION_NAME' => 'Sections',
                    'ELEMENT_NAME' => 'Elements',
                ],
                'en' => [
                    'NAME'         => 'Catalog',
                    'SECTION_NAME' => 'Sections',
                    'ELEMENT_NAME' => 'Elements',
                ],
            ],
        ];

        $fields = array_replace_recursive($default, $fields);

        $ib = new CIBlockType;
        if ($ib->Add($fields)) {
            return (string)$fields['ID'];
        }

        throw new HelperException($ib->LAST_ERROR);
    }

    /**
     * Обновляет тип инфоблока
     *
     * @throws HelperException
     */
    public function updateIblockType(string $typeId, array $fields): string
    {
        $ib = new CIBlockType;
        if ($ib->Update($typeId, $fields)) {
            return $typeId;
        }

        throw new HelperException($ib->LAST_ERROR);
    }

    /**
     * Удаляет тип инфоблока, если существует
     *
     * @throws HelperException
     */
    public function deleteIblockTypeIfExists(array|string $typeId): bool
    {
        $item = $this->getIblockType($typeId);
        if (!empty($item['ID'])) {
            return $this->deleteIblockType($item['ID']);
        }
        return false;
    }

    /**
     * Удаляет тип инфоблока
     *
     * @throws HelperException
     */
    public function deleteIblockType(string $typeId): bool
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
     */
    public function getIblockTypeLangs(string $typeId): array
    {
        $lby = 'sort';
        $lorder = 'asc';

        $result = [];
        $dbres = CLanguage::GetList($lby, $lorder);
        while ($item = $dbres->GetNext()) {
            $values = CIBlockType::GetByIDLang($typeId, $item['LID'], false);
            if (!empty($values)) {
                $result[$item['LID']] = [
                    'NAME'         => $values['NAME'],
                    'SECTION_NAME' => $values['SECTION_NAME'],
                    'ELEMENT_NAME' => $values['ELEMENT_NAME'],
                ];
            }
        }
        return $result;
    }

    /**
     * Сохраняет тип инфоблока, создаст если не было, обновит если существует и отличается
     *
     * @throws HelperException
     */
    public function saveIblockType(array $fields = []): string
    {
        $this->checkRequiredKeys($fields, ['ID']);

        $exists = $this->getIblockType($fields['ID']);

        if (empty($exists)) {
            $ok = $this->addIblockType($fields);
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

        if ($this->hasDiff($exists, $fields)) {
            $ok = $this->updateIblockType($exists['ID'], $fields);
            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'IB_TYPE_UPDATED',
                    [
                        '#NAME#' => $fields['ID'],
                    ]
                )
            );
            $this->outDiffIf($ok, $exists, $fields);

            return $ok;
        }

        return (string)$fields['ID'];
    }

    /**
     * Получает тип инфоблока.
     */
    public function exportIblockType(array|string $typeId): bool|array
    {
        return $this->getIblockType($typeId);
    }
}
