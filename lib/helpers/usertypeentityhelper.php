<?php

namespace Sprint\Migration\Helpers;

use CUserFieldEnum;
use CUserTypeEntity;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;

class UserTypeEntityHelper extends Helper
{
    /**
     * Добавляет пользовательские поля к объекту
     *
     * @param       $entityId
     * @param array $fields
     *
     * @throws HelperException
     */
    public function addUserTypeEntitiesIfNotExists($entityId, array $fields)
    {
        foreach ($fields as $field) {
            $this->addUserTypeEntityIfNotExists($entityId, $field["FIELD_NAME"], $field);
        }
    }

    /**
     * Удаляет пользовательские поля у объекта
     *
     * @param       $entityId
     * @param array $fields
     *
     * @throws HelperException
     */
    public function deleteUserTypeEntitiesIfExists($entityId, array $fields)
    {
        foreach ($fields as $fieldName) {
            $this->deleteUserTypeEntityIfExists($entityId, $fieldName);
        }
    }

    /**
     * Добавляет пользовательское поле к объекту если его не существует
     *
     * @param $entityId
     * @param $fieldName
     * @param $fields
     *
     * @throws HelperException
     * @return int
     */
    public function addUserTypeEntityIfNotExists($entityId, $fieldName, $fields)
    {
        $item = $this->getUserTypeEntity(
            $this->revertEntityId($entityId),
            $fieldName
        );
        if ($item) {
            return $item['ID'];
        }

        return $this->addUserTypeEntity($entityId, $fieldName, $fields);
    }

    /**
     * Добавляет пользовательское поле к объекту
     *
     * @param $entityId
     * @param $fieldName
     * @param $fields
     *
     * @throws HelperException
     * @return int|void
     */
    public function addUserTypeEntity($entityId, $fieldName, $fields)
    {
        $default = [
            "ENTITY_ID"         => '',
            "FIELD_NAME"        => '',
            "USER_TYPE_ID"      => '',
            "XML_ID"            => '',
            "SORT"              => 500,
            "MULTIPLE"          => 'N',
            "MANDATORY"         => 'N',
            "SHOW_FILTER"       => 'I',
            "SHOW_IN_LIST"      => '',
            "EDIT_IN_LIST"      => '',
            "IS_SEARCHABLE"     => '',
            "SETTINGS"          => [],
            "EDIT_FORM_LABEL"   => ['ru' => '', 'en' => ''],
            "LIST_COLUMN_LABEL" => ['ru' => '', 'en' => ''],
            "LIST_FILTER_LABEL" => ['ru' => '', 'en' => ''],
            "ERROR_MESSAGE"     => '',
            "HELP_MESSAGE"      => '',
        ];

        $fields = array_replace_recursive($default, $fields);
        $fields['FIELD_NAME'] = $fieldName;
        $fields['ENTITY_ID'] = $this->revertEntityId($entityId);

        $this->revertSettings($fields);
        $enums = $this->revertEnums($fields);

        $obUserField = new CUserTypeEntity;
        $userFieldId = $obUserField->Add($fields);

        $enumsCreated = true;
        if ($userFieldId && $fields['USER_TYPE_ID'] == 'enumeration') {
            $enumsCreated = $this->setUserTypeEntityEnumValues($userFieldId, $enums);
        }

        if ($userFieldId && $enumsCreated) {
            return $userFieldId;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException(
            Locale::getMessage(
                'ERR_USERTYPE_NOT_ADDED',
                [
                    '#NAME#' => $fieldName,
                ]
            )
        );
    }

    /**
     * Обновление пользовательского поля у объекта
     *
     * @param $fieldId
     * @param $fields
     *
     * @throws HelperException
     * @return int|void
     */
    public function updateUserTypeEntity($fieldId, $fields)
    {
        unset($fields["ENTITY_ID"]);
        unset($fields["FIELD_NAME"]);
        unset($fields["MULTIPLE"]);

        $this->revertSettings($fields);
        $enums = $this->revertEnums($fields);

        $entity = new CUserTypeEntity;
        $userFieldUpdated = $entity->Update($fieldId, $fields);

        $enumsCreated = true;
        if ($userFieldUpdated && $fields['USER_TYPE_ID'] == 'enumeration') {
            $enumsCreated = $this->setUserTypeEntityEnumValues($fieldId, $enums);
        }

        if ($userFieldUpdated && $enumsCreated) {
            return $fieldId;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException(
            Locale::getMessage(
                'ERR_USERTYPE_NOT_UPDATED',
                [
                    '#NAME#' => $fieldId,
                ]
            )
        );
    }

    /**
     * Обновление пользовательского поля у объекта если оно существует
     *
     * @param $entityId
     * @param $fieldName
     * @param $fields
     *
     * @throws HelperException
     * @return bool|mixed
     */
    public function updateUserTypeEntityIfExists($entityId, $fieldName, $fields)
    {
        $item = $this->getUserTypeEntity(
            $this->revertEntityId($entityId),
            $fieldName
        );
        if (!$item) {
            return false;
        }

        return $this->updateUserTypeEntity($item['ID'], $fields);
    }

    /**
     * Получает пользовательские поля у объекта
     *
     * @param bool $entityId
     *
     * @return array
     */
    public function getUserTypeEntities($entityId = false)
    {
        if (!empty($entityId)) {
            $filter = is_array($entityId)
                ? $entityId
                : [
                    'ENTITY_ID' => $entityId,
                ];
        } else {
            $filter = [];
        }

        return array_map(function ($item) {
            return $this->getUserTypeEntityById($item['ID']);
        }, $this->getList($filter));
    }

    public function getList(array $filter = []): array
    {
        $dbres = CUserTypeEntity::GetList([], $filter);
        return $this->fetchAll($dbres);
    }

    /**
     * Получает пользовательское поле у объекта
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @param $fieldId
     *
     * @throws HelperException
     * @return mixed
     */
    public function exportUserTypeEntity($fieldId)
    {
        $item = $this->getUserTypeEntityById($fieldId);
        return $this->prepareExportUserTypeEntity($item);
    }

    /**
     * Получает пользовательские поля у объекта
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @param bool $entityId
     *
     * @throws HelperException
     * @return array
     */
    public function exportUserTypeEntities($entityId = false)
    {
        $items = $this->getUserTypeEntities($entityId);
        $export = [];
        foreach ($items as $item) {
            $export[] = $this->prepareExportUserTypeEntity($item);
        }
        return $export;
    }

    /**
     * Получает пользовательское поле у объекта
     *
     * @param $entityId
     * @param $fieldName
     *
     * @return array|bool
     */
    public function getUserTypeEntity($entityId, $fieldName)
    {
        $item = CUserTypeEntity::GetList(
            [],
            [
                'ENTITY_ID'  => $entityId,
                'FIELD_NAME' => $fieldName,
            ]
        )->Fetch();

        return (!empty($item)) ? $this->getUserTypeEntityById($item['ID']) : false;
    }

    /**
     * Получает пользовательское поле у объекта
     *
     * @param $fieldId
     *
     * @return array|bool
     */
    public function getUserTypeEntityById($fieldId)
    {
        $item = CUserTypeEntity::GetByID($fieldId);
        if (empty($item)) {
            return false;
        }

        if ($item['USER_TYPE_ID'] == 'enumeration') {
            $item['ENUM_VALUES'] = $this->getEnumValues($fieldId);
        }

        return $item;
    }

    /**
     * Сохраняет значения списков для пользовательского поля
     *
     * @param $fieldId
     * @param $newenums
     *
     * @return bool
     */
    public function setUserTypeEntityEnumValues($fieldId, $newenums)
    {
        $newenums = is_array($newenums) ? $newenums : [];
        $oldenums = $this->getEnumValues($fieldId);

        $index = 0;

        $updates = [];
        foreach ($oldenums as $oldenum) {
            $newenum = $this->searchEnum($oldenum, $newenums);
            if ($newenum) {
                $updates[$oldenum['ID']] = $newenum;
            } else {
                $oldenum['DEL'] = 'Y';
                $updates[$oldenum['ID']] = $oldenum;
            }
        }

        foreach ($newenums as $newenum) {
            $oldenum = $this->searchEnum($newenum, $oldenums);
            if ($oldenum) {
                $updates[$oldenum['ID']] = $newenum;
            } else {
                $updates['n' . $index++] = $newenum;
            }
        }

        $obEnum = new CUserFieldEnum();
        return $obEnum->SetEnumValues($fieldId, $updates);
    }

    /**
     * Удаляет пользовательское поле у объекта если оно существует
     *
     * @param $entityId
     * @param $fieldName
     *
     * @throws HelperException
     * @return bool|void
     */
    public function deleteUserTypeEntityIfExists($entityId, $fieldName)
    {
        $item = $this->getUserTypeEntity(
            $this->revertEntityId($entityId),
            $fieldName
        );

        if (empty($item)) {
            return false;
        }

        $entity = new CUserTypeEntity();
        if ($entity->Delete($item['ID'])) {
            return true;
        }
        throw new HelperException(
            Locale::getMessage(
                'ERR_USERTYPE_NOT_DELETED',
                [
                    '#NAME#' => $fieldName,
                ]
            )
        );
    }

    /**
     * Удаляет пользовательское поле у объекта
     *
     * @param $entityId
     * @param $fieldName
     *
     * @throws HelperException
     * @return bool
     */
    public function deleteUserTypeEntity($entityId, $fieldName)
    {
        return $this->deleteUserTypeEntityIfExists($entityId, $fieldName);
    }

    /**
     * Декодирует название объекта в оригинальный вид
     *
     * @param $entityId
     *
     * @throws HelperException
     * @return string
     */
    public function revertEntityId($entityId)
    {
        if (0 === strpos($entityId, 'HLBLOCK_')) {
            $hlblockId = substr($entityId, 8);
            if (!is_numeric($hlblockId)) {
                $hlblockId = (new HlblockHelper())->getHlblockIdByUid($hlblockId);
            }
            return 'HLBLOCK_' . $hlblockId;
        }

        $matches = [];
        if (preg_match('/^IBLOCK_(.+)_SECTION$/', $entityId, $matches)) {
            $iblockId = $matches[1];
            if (!is_numeric($iblockId)) {
                $iblockId = (new IblockHelper())->getIblockIdByUid($iblockId);
            }
            return 'IBLOCK_' . $iblockId . '_SECTION';
        }

        return $entityId;
    }

    /**
     * Кодирует название объекта в вид удобный для экспорта в миграцию или схему
     *
     * @param $entityId
     *
     * @throws HelperException
     * @return string
     */
    public function transformEntityId($entityId)
    {
        if (0 === strpos($entityId, 'HLBLOCK_')) {
            $hlblockId = substr($entityId, 8);
            if (is_numeric($hlblockId)) {
                $hlblockId = (new HlblockHelper())->getHlblockUid($hlblockId);
            }
            return 'HLBLOCK_' . $hlblockId;
        }

        $matches = [];
        if (preg_match('/^IBLOCK_(.+)_SECTION$/', $entityId, $matches)) {
            $iblockId = $matches[1];
            if (is_numeric($iblockId)) {
                $iblockId = (new IblockHelper())->getIblockUid($iblockId);
            }
            return 'IBLOCK_' . $iblockId . '_SECTION';
        }

        return $entityId;
    }

    /**
     * @throws HelperException
     */
    public function getEntityTitle($entityId)
    {
        $title = Locale::getMessage('ENTITY_TITLE_' . $entityId, [], $entityId);

        if (0 === strpos($entityId, 'HLBLOCK_')) {
            $hlblockId = substr($entityId, 8);
            if (is_numeric($hlblockId)) {
                $hlblock = (new HlblockHelper())->getHlblock($hlblockId);
                if ($hlblock['NAME']) {
                    $title = Locale::getMessage('ENTITY_TITLE_HLBLOCK', $hlblock);
                }
            }
        }

        $matches = [];
        if (preg_match('/^IBLOCK_(.+)_SECTION$/', $entityId, $matches)) {
            $iblockId = $matches[1];
            if (is_numeric($iblockId)) {
                $iblock = (new IblockHelper())->getIblock($iblockId);
                if ($iblock['NAME']) {
                    $title = Locale::getMessage('ENTITY_TITLE_IBLOCK_SECTION', $iblock);
                }

            }
        }

        return ($title == $entityId) ? $entityId : '[' . $entityId . '] ' . $title;
    }


    /**
     * Сохраняет пользовательское поле
     * Создаст если не было, обновит если существует и отличается
     *
     * @param array $fields
     *
     * @throws HelperException
     * @return bool|int|mixed
     */
    public function saveUserTypeEntity($fields = [])
    {
        if (func_num_args() > 1) {
            /** @compability */
            [$entityId, $fieldName, $fields] = func_get_args();
            $fields['ENTITY_ID'] = $entityId;
            $fields['FIELD_NAME'] = $fieldName;
        }

        $this->checkRequiredKeys($fields, ['ENTITY_ID', 'FIELD_NAME']);

        $exists = $this->getUserTypeEntity(
            $this->revertEntityId($fields['ENTITY_ID']),
            $fields['FIELD_NAME']
        );

        $fields = $this->prepareExportUserTypeEntity($fields);

        if (empty($exists)) {
            $ok = $this->getMode('test')
                ? true
                : $this->addUserTypeEntity(
                    $fields['ENTITY_ID'],
                    $fields['FIELD_NAME'],
                    $fields
                );

            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'USER_TYPE_ENTITY_CREATED',
                    [
                        '#NAME#' => $fields['FIELD_NAME'],
                    ]
                )
            );
            return $ok;
        }

        try {
            $exportExists = $this->prepareExportUserTypeEntity($exists);
        } catch (HelperException $e) {
            $exportExists = [];
        }

        unset($exportExists['MULTIPLE']);
        unset($fields['MULTIPLE']);

        if ($this->hasDiff($exportExists, $fields)) {
            $ok = $this->getMode('test') ? true : $this->updateUserTypeEntity($exists['ID'], $fields);
            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'USER_TYPE_ENTITY_UPDATED',
                    [
                        '#NAME#' => $fields['FIELD_NAME'],
                    ]
                )
            );
            $this->outDiffIf($ok, $exportExists, $fields);
            return $ok;
        }

        return $this->getMode('test') ? true : $exists['ID'];
    }

    /**
     * @param $fields
     *
     * @throws HelperException
     * @return mixed
     */
    protected function prepareExportUserTypeEntity($fields)
    {
        if (empty($fields)) {
            return $fields;
        }

        // Расширенные ошибки экспорта пользовательских полей
        try {
            $this->transformSettings($fields);
            $this->transformEnums($fields);

            $fields['ENTITY_ID'] = $this->transformEntityId($fields['ENTITY_ID']);
        } catch (HelperException $e) {
            $userTypeMessage = Locale::getMessage(
                'ERR_USERTYPE_EXPORT',
                ['#USER_TYPE_ID#' => $fields['ID']]
            );

            $extendedMessage = $userTypeMessage . PHP_EOL . $e->getMessage();

            throw new HelperException($extendedMessage);
        }

        unset($fields['ID']);
        return $fields;
    }

    /**
     * @param $fieldId
     *
     * @return array
     */
    protected function getEnumValues($fieldId)
    {
        $obEnum = new CUserFieldEnum;
        $dbres = $obEnum->GetList([], ["USER_FIELD_ID" => $fieldId]);
        return $this->fetchAll($dbres);
    }

    /**
     * @param       $enum
     * @param array $haystack
     *
     * @return bool|mixed
     */
    protected function searchEnum($enum, $haystack = [])
    {
        foreach ($haystack as $item) {
            if (isset($item['XML_ID']) && strlen($item['XML_ID']) > 0 && $item['XML_ID'] == $enum['XML_ID']) {
                return $item;
            }
        }
        return false;
    }

    /**
     * @param $fields
     *
     * @throws HelperException
     */
    private function transformSettings(&$fields)
    {
        if ($fields['USER_TYPE_ID'] == 'iblock_element') {
            if (!empty($fields['SETTINGS']['IBLOCK_ID'])) {
                $fields['SETTINGS']['IBLOCK_ID'] = (new IblockHelper())->getIblockUid(
                    $fields['SETTINGS']['IBLOCK_ID']
                );
            }
        }
        if ($fields['USER_TYPE_ID'] == 'hlblock') {
            if (!empty($fields['SETTINGS']['HLBLOCK_ID'])) {
                $fields['SETTINGS']['HLBLOCK_ID'] = (new HlblockHelper())->getHlblockUid(
                    $fields['SETTINGS']['HLBLOCK_ID']
                );
                if (!empty($fields['SETTINGS']['HLFIELD_ID'])) {
                    $fields['SETTINGS']['HLFIELD_ID'] = (new HlblockHelper())->getFieldUid(
                        $fields['SETTINGS']['HLBLOCK_ID'],
                        $fields['SETTINGS']['HLFIELD_ID']
                    );
                }
            }
        }
    }

    /**
     * @param $fields
     *
     * @throws HelperException
     */
    private function revertSettings(&$fields)
    {
        if ($fields['USER_TYPE_ID'] == 'iblock_element') {
            if (!empty($fields['SETTINGS']['IBLOCK_ID'])) {
                $fields['SETTINGS']['IBLOCK_ID'] = (new IblockHelper())->getIblockIdByUid(
                    $fields['SETTINGS']['IBLOCK_ID']
                );
            }
        }
        if ($fields['USER_TYPE_ID'] == 'hlblock') {
            if (!empty($fields['SETTINGS']['HLBLOCK_ID'])) {
                $fields['SETTINGS']['HLBLOCK_ID'] = (new HlblockHelper())->getHlblockIdByUid(
                    $fields['SETTINGS']['HLBLOCK_ID']
                );
                if (!empty($fields['SETTINGS']['HLFIELD_ID'])) {
                    $fields['SETTINGS']['HLFIELD_ID'] = (new HlblockHelper())->getFieldIdByUid(
                        $fields['SETTINGS']['HLBLOCK_ID'],
                        $fields['SETTINGS']['HLFIELD_ID']
                    );
                }
            }
        }
    }

    private function transformEnums(&$fields)
    {
        if (!empty($fields['ENUM_VALUES']) && is_array($fields['ENUM_VALUES'])) {
            $exportValues = [];
            foreach ($fields['ENUM_VALUES'] as $item) {
                $exportValues[] = [
                    'VALUE'  => $item['VALUE'],
                    'DEF'    => $item['DEF'],
                    'SORT'   => $item['SORT'],
                    'XML_ID' => $item['XML_ID'],
                ];
            }
            $fields['ENUM_VALUES'] = $exportValues;
        }
    }

    private function revertEnums(&$fields)
    {
        $enums = [];
        if (isset($fields['ENUM_VALUES'])) {
            $enums = $fields['ENUM_VALUES'];
            unset($fields['ENUM_VALUES']);
        }

        return $enums;
    }
}
