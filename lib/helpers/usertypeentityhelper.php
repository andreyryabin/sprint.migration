<?php

namespace Sprint\Migration\Helpers;

use CUserFieldEnum;
use CUserTypeEntity;
use CUserTypeManager;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;

class UserTypeEntityHelper extends Helper
{
    /**
     * @throws HelperException
     */
    public function getUserTypes(): array
    {
        return array_map(
            fn($userType) => $this->prepareUserType($userType),
            $this->getUserFieldManager()->GetUserType()
        );
    }

    private function prepareUserType(array $userType): array
    {
        return $userType;
    }

    /**
     * @throws HelperException
     */
    private function getUserFieldManager(): CUserTypeManager
    {
        if (isset($GLOBALS['USER_FIELD_MANAGER']) && $GLOBALS['USER_FIELD_MANAGER'] instanceof CUserTypeManager) {
            return $GLOBALS['USER_FIELD_MANAGER'];
        }

        throw new HelperException("USER_FIELD_MANAGER not initialized");
    }

    /**
     * Добавляет пользовательские поля к объекту
     *
     * @throws HelperException
     */
    public function addUserTypeEntitiesIfNotExists(string $entityId, array $fields): void
    {
        foreach ($fields as $field) {
            $this->addUserTypeEntityIfNotExists($entityId, $field["FIELD_NAME"], $field);
        }
    }

    /**
     * Удаляет пользовательские поля у объекта
     *
     * @throws HelperException
     */
    public function deleteUserTypeEntitiesIfExists(string $entityId, array $fieldNames): void
    {
        foreach ($fieldNames as $fieldName) {
            $this->deleteUserTypeEntityIfExists($entityId, $fieldName);
        }
    }

    /**
     * Добавляет пользовательское поле к объекту если его не существует
     *
     * @throws HelperException
     */
    public function addUserTypeEntityIfNotExists(string $entityId, string $fieldName, array $fields): int
    {
        $item = $this->getUserTypeEntity(
            $this->revertEntityId($entityId),
            $fieldName
        );
        if ($item) {
            return (int)$item['ID'];
        }

        return $this->addUserTypeEntity($entityId, $fieldName, $fields);
    }

    /**
     * Добавляет пользовательское поле к объекту
     *
     * @throws HelperException
     */
    public function addUserTypeEntity($entityId, $fieldName, $fields): int
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
     * @throws HelperException
     */
    public function updateUserTypeEntity(int $fieldId, array $fields): int
    {
        $this->unsetKeys($fields, [
            'ENTITY_ID',
            'FIELD_NAME',
            'MULTIPLE',
        ]);

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
     * @throws HelperException
     */
    public function updateUserTypeEntityIfExists(string $entityId, string $fieldName, array $fields): false|int
    {
        $item = $this->getUserTypeEntity(
            $this->revertEntityId($entityId),
            $fieldName
        );
        if (!empty($item['ID'])) {
            return $this->updateUserTypeEntity($item['ID'], $fields);
        }
        return false;
    }

    /**
     * Получает пользовательские поля у объекта
     */
    public function getUserTypeEntities(string $entityId, array $filter = []): array
    {
        $filter['ENTITY_ID'] = $entityId;

        return $this->getUserTypeEntitiesByFilter($filter);
    }

    public function getUserTypeEntitiesByFilter(array $filter = []): array
    {
        $dbres = CUserTypeEntity::GetList([], $filter);
        return array_map(
            fn($item) => $this->getUserTypeEntityById($item['ID']),
            $this->fetchAll($dbres)
        );
    }

    /**
     * @deprecated
     */
    public function getList(array $filter = []): array
    {
        return $this->getUserTypeEntitiesByFilter($filter);
    }

    /**
     * Получает пользовательское поле у объекта
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @throws HelperException
     */
    public function exportUserTypeEntity(int $fieldId): bool|array
    {
        $item = $this->getUserTypeEntityById($fieldId);

        return $item ? $this->prepareExportUserTypeEntity($item) : false;
    }

    /**
     * @throws HelperException
     */
    public function exportUserTypeEntitiesByIds(array $fieldIds): array
    {
        $result = array_map(
            fn($fieldId) => $this->exportUserTypeEntity($fieldId),
            $fieldIds
        );

        return array_filter($result);
    }

    /**
     * Получает пользовательские поля у объекта
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @throws HelperException
     */
    public function exportUserTypeEntities(string $entityId): array
    {
        return array_map(
            fn($item) => $this->prepareExportUserTypeEntity($item),
            $this->getUserTypeEntities($entityId)
        );
    }

    /**
     * Получает пользовательское поле у объекта
     */
    public function getUserTypeEntity(string $entityId, string $fieldName): false|array
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
     */
    public function getUserTypeEntityById(int $fieldId): false|array
    {
        $item = CUserTypeEntity::GetByID($fieldId);
        if (empty($item)) {
            return false;
        }

        if ($item['USER_TYPE_ID'] == 'enumeration') {
            $item['ENUM_VALUES'] = $this->getEnumValues($fieldId);
        }

        $lang = Locale::getLang();
        if (!empty($item['EDIT_FORM_LABEL'][$lang])) {
            $item['TITLE'] = $item['EDIT_FORM_LABEL'][$lang];
        } elseif (!empty($item['EDIT_FORM_LABEL']['ru'])) {
            $item['TITLE'] = $item['EDIT_FORM_LABEL']['ru'];
        } else {
            $item['TITLE'] = $item['FIELD_NAME'];
        }

        return $item;
    }

    /**
     * Сохраняет значения списков для пользовательского поля
     */
    public function setUserTypeEntityEnumValues(int $fieldId, array $newenums): bool
    {
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
     * @throws HelperException
     */
    public function deleteUserTypeEntityIfExists(string $entityId, string $fieldName): bool
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
     * @throws HelperException
     */
    public function deleteUserTypeEntity(string $entityId, string $fieldName): bool
    {
        return $this->deleteUserTypeEntityIfExists($entityId, $fieldName);
    }

    /**
     * Декодирует название объекта в оригинальный вид
     *
     * @throws HelperException
     */
    public function revertEntityId(string $entityId): string
    {
        if (str_starts_with($entityId, 'HLBLOCK_')) {
            $hlblockId = substr($entityId, 8);
            if (!is_numeric($hlblockId)) {
                $hlblockId = (new HlblockHelper())->getHlblockIdByName($hlblockId);
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
     * Кодирует название объекта в вид удобный для экспорта в миграцию
     *
     * @throws HelperException
     */
    public function transformEntityId(string $entityId): string
    {
        if (str_starts_with($entityId, 'HLBLOCK_')) {
            $hlblockId = substr($entityId, 8);
            if (is_numeric($hlblockId)) {
                $hlblockId = (new HlblockHelper())->getHlblockNameById($hlblockId);
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
    public function getEntityTitle(string $entityId): string
    {
        static $cache = [];

        if (isset($cache[$entityId])) {
            return $cache[$entityId];
        }

        $title = Locale::getMessage('ENTITY_TITLE_' . $entityId, [], $entityId);

        if (str_starts_with($entityId, 'HLBLOCK_')) {
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

        $cache[$entityId] = ($title == $entityId) ? $entityId : '[' . $entityId . '] ' . $title;

        return $cache[$entityId];
    }

    /**
     * Сохраняет пользовательское поле,
     * создаст если не было, обновит если существует и отличается.
     *
     * @throws HelperException
     */
    public function saveUserTypeEntity(array $fields = []): int
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

        if (empty($exists)) {
            $ok = $this->addUserTypeEntity(
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
        } catch (HelperException) {
            $exportExists = [];
        }

        $this->unsetKeys($exportExists, ['MULTIPLE']);
        $this->unsetKeys($fields, ['MULTIPLE']);

        if ($this->hasDiff($exportExists, $fields)) {
            $ok = $this->updateUserTypeEntity($exists['ID'], $fields);
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

        return (int)$exists['ID'];
    }

    /**
     * @throws HelperException
     */
    protected function prepareExportUserTypeEntity(array $fields): array
    {
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

        $this->unsetKeys($fields, ['ID','TITLE']);

        return $fields;
    }

    protected function getEnumValues(int $fieldId): array
    {
        $obEnum = new CUserFieldEnum;
        $dbres = $obEnum->GetList([], ["USER_FIELD_ID" => $fieldId]);
        return $this->fetchAll($dbres);
    }

    protected function searchEnum(array $enum, array $haystack = []): array|false
    {
        foreach ($haystack as $item) {
            if (isset($item['XML_ID']) && strlen($item['XML_ID']) > 0 && $item['XML_ID'] == $enum['XML_ID']) {
                return $item;
            }
        }
        return false;
    }

    /**
     * @throws HelperException
     */
    private function transformSettings(&$fields): void
    {
        //USER_TYPE_ID = iblock_element|iblock_section|hlblock|...

        if (!empty($fields['SETTINGS']['IBLOCK_ID'])) {
            $iblockId = $fields['SETTINGS']['IBLOCK_ID'];
            $fields['SETTINGS']['IBLOCK_ID'] = (new IblockHelper())->getIblockUid($iblockId);
        }

        if (!empty($fields['SETTINGS']['HLBLOCK_ID'])) {
            $hlblockId = $fields['SETTINGS']['HLBLOCK_ID'];
            $fields['SETTINGS']['HLBLOCK_ID'] = (new HlblockHelper())->getHlblockNameById($hlblockId);

            if (!empty($fields['SETTINGS']['HLFIELD_ID'])) {
                $fieldId = $fields['SETTINGS']['HLFIELD_ID'];
                $fields['SETTINGS']['HLFIELD_ID'] = (new HlblockHelper())->getFieldNameById($hlblockId, $fieldId);
            }
        }
    }

    /**
     * @throws HelperException
     */
    private function revertSettings(&$fields): void
    {
        //USER_TYPE_ID = iblock_element|iblock_section|hlblock|...

        if (!empty($fields['SETTINGS']['IBLOCK_ID'])) {
            $iblockUid = $fields['SETTINGS']['IBLOCK_ID'];
            $fields['SETTINGS']['IBLOCK_ID'] = (new IblockHelper())->getIblockIdByUid($iblockUid);
        }

        if (!empty($fields['SETTINGS']['HLBLOCK_ID'])) {
            $hlblockName = $fields['SETTINGS']['HLBLOCK_ID'];
            $fields['SETTINGS']['HLBLOCK_ID'] = (new HlblockHelper())->getHlblockIdByName($hlblockName);

            if (!empty($fields['SETTINGS']['HLFIELD_ID'])) {
                $fieldName = $fields['SETTINGS']['HLFIELD_ID'];
                $fields['SETTINGS']['HLFIELD_ID'] = (new HlblockHelper())->getFieldIdByName($hlblockName, $fieldName);
            }
        }
    }

    private function transformEnums(&$fields): void
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
