<?php

namespace Sprint\Migration\Helpers\Traits\Hlblock;

use Bitrix\Highloadblock\HighloadBlockLangTable;
use Bitrix\Highloadblock\HighloadBlockTable;
use Exception;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helpers\UserTypeEntityHelper;
use Sprint\Migration\Locale;

trait HlblockTrait
{
    /**
     * Получает список highload-блоков
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @throws HelperException
     */
    public function exportHlblocks(array $filter = []): array
    {
        return array_map(
            fn($item) => $this->prepareExportHlblock($item),
            $this->getHlblocks($filter)
        );
    }

    /**
     * Получает список highload-блоков
     *
     * @throws HelperException
     */
    public function getHlblocks(array $filter = []): array
    {
        $result = [];
        try {
            $dbres = HighloadBlockTable::getList(
                [
                    'select' => ['*'],
                    'filter' => $filter,
                ]
            );
            while ($hlblock = $dbres->fetch()) {
                $result[] = $this->prepareHlblock($hlblock);
            }
        } catch (Exception $e) {
            throw new HelperException($e);
        }
        return $result;
    }

    /**
     * @throws HelperException
     */
    protected function prepareHlblock(array $item): array
    {
        $langs = $this->getHblockLangs($item['ID']);
        if (!empty($langs)) {
            $item['LANG'] = $langs;
        }

        $lang = Locale::getLang();
        if (!empty($item['LANG'][$lang]['NAME'])) {
            $item['TITLE'] = $item['LANG'][$lang]['NAME'];
        } elseif (!empty($item['LANG']['ru']['NAME'])) {
            $item['TITLE'] = $item['LANG']['ru']['NAME'];
        } else {
            $item['TITLE'] = $item['NAME'];
        }

        return $item;
    }

    /**
     * @throws HelperException
     */
    protected function getHblockLangs(int $hlblockId): array
    {
        $result = [];

        if (!class_exists('\Bitrix\Highloadblock\HighloadBlockLangTable')) {
            return $result;
        }

        try {
            $dbres = HighloadBlockLangTable::getList(
                [
                    'filter' => ['ID' => $hlblockId],
                ]
            );

            while ($item = $dbres->fetch()) {
                $result[$item['LID']] = [
                    'NAME' => $item['NAME'],
                ];
            }
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }

        return $result;
    }

    protected function prepareExportHlblock(array $item): array
    {
        $this->unsetKeys($item, [
            'ID',
            'TITLE',
        ]);

        return $item;
    }

    protected function prepareExportField(array $item): array
    {
        $this->unsetKeys($item, [
            'ID',
            'TITLE',
        ]);

        return $item;
    }

    /**
     * @throws HelperException
     */
    public function getEntityId(array|int|string $hlblockName): string
    {
        $hlblockId = is_numeric($hlblockName) ? $hlblockName : $this->getHlblockId($hlblockName);
        return 'HLBLOCK_' . $hlblockId;
    }

    /**
     * Получает id highload-блока
     *
     * @param $hlblockName - id, имя или фильтр
     *
     * @throws HelperException
     */
    public function getHlblockId(array|int|string $hlblockName): int
    {
        $item = $this->getHlblock($hlblockName);
        return ($item && isset($item['ID'])) ? $item['ID'] : 0;
    }

    /**
     * Получает highload-блок по id, имени или фильтру
     *
     * @throws HelperException
     */
    public function getHlblock(array|int|string $hlblockName): false|array
    {
        if (is_array($hlblockName)) {
            $filter = $hlblockName;
        } elseif (is_numeric($hlblockName)) {
            $filter = ['ID' => $hlblockName];
        } else {
            $filter = ['NAME' => $hlblockName];
        }

        try {
            $hlblock = HighloadBlockTable::getRow([
                'select' => ['*'],
                'filter' => $filter,
            ]);

            return $hlblock ? $this->prepareHlblock($hlblock) : false;
        } catch (Exception $e) {
            throw new HelperException($e->getMessage());
        }
    }

    /**
     * @throws HelperException
     */
    public function getFieldNameById(int $hlblockId, int $fieldId): string
    {
        $entityHelper = new UserTypeEntityHelper();

        $field = $entityHelper->getUserTypeEntityById($fieldId);

        $entityId = $this->getEntityId($hlblockId);

        if (!empty($field['FIELD_NAME']) && $entityId == $field['ENTITY_ID']) {
            return $field['FIELD_NAME'];
        }

        throw new HelperException(Locale::getMessage('ERR_HLBLOCK_FIELD_NOT_FOUND'));
    }

    /**
     * @throws HelperException
     */
    public function getFieldType(int $hlblockId, string $fieldName)
    {
        $field = $this->getField($hlblockId, $fieldName);
        return $field['USER_TYPE_ID'];
    }

    /**
     * @throws HelperException
     */
    public function getFieldSettings(int $hlblockName, string $fieldName)
    {
        $field = $this->getField($hlblockName, $fieldName);
        return $field['SETTINGS'];
    }

    /**
     * @throws HelperException
     */
    public function getField(array|int|string $hlblockName, string $fieldName): array
    {
        $entityHelper = new UserTypeEntityHelper();
        $field = $entityHelper->getUserTypeEntity(
            $this->getEntityId($hlblockName),
            $fieldName
        );

        if (!empty($field)) {
            return $field;
        }

        throw new HelperException(Locale::getMessage('ERR_HLBLOCK_FIELD_NOT_FOUND'));
    }

    /**
     * Получает список полей highload-блока
     *
     * @throws HelperException
     */
    public function getFields(array|int|string $hlblockName, array $filter = []): array
    {
        $entityHelper = new UserTypeEntityHelper();
        $entityId = $this->getEntityId($hlblockName);

        return $entityHelper->getUserTypeEntities($entityId, $filter);
    }

    /**
     * @throws HelperException
     */
    public function isFieldMultiple(int $hlblockId, string $fieldName): bool
    {
        $field = $this->getField($hlblockId, $fieldName);
        return ($field['MULTIPLE'] == 'Y');
    }

    /**
     * @throws HelperException
     */
    public function getFieldEnumIdByXmlId(int $hlblockId, string $fieldName, $xmlId)
    {
        $field = $this->getField($hlblockId, $fieldName);
        if (empty($field['ENUM_VALUES']) || !is_array($field['ENUM_VALUES'])) {
            return '';
        }

        foreach ($field['ENUM_VALUES'] as $val) {
            if ($val['XML_ID'] == $xmlId) {
                return $val['ID'];
            }
        }

        return '';
    }

    /**
     * @throws HelperException
     */
    public function getFieldEnumXmlIdById(int $hlblockId, string $fieldName, $valueId)
    {
        $field = $this->getField($hlblockId, $fieldName);
        if (empty($field['ENUM_VALUES']) || !is_array($field['ENUM_VALUES'])) {
            return '';
        }

        foreach ($field['ENUM_VALUES'] as $val) {
            if ($val['ID'] == $valueId) {
                return $val['XML_ID'];
            }
        }
        return '';
    }

    /**
     * @throws HelperException
     */
    public function getFieldEnumXmlIdsByIds($hlblockName, $fieldName, $valueIds): array
    {
        $valueIds = $this->makeNonEmptyArray($valueIds);

        $field = $this->getField($hlblockName, $fieldName);
        if (empty($field['ENUM_VALUES']) || !is_array($field['ENUM_VALUES'])) {
            return [];
        }

        $xmlIds = [];
        foreach ($field['ENUM_VALUES'] as $val) {
            if (in_array($val['ID'], $valueIds)) {
                $xmlIds[] = $val['XML_ID'];
            }
        }
        return $xmlIds;
    }

    /**
     * @throws HelperException
     */
    public function getFieldIdByName(string $hlblockName, string $fieldName): int
    {
        $field = $this->getField($hlblockName, $fieldName);

        return !empty($field['ID']) ? (int)$field['ID'] : 0;
    }

    /**
     * Сохраняет поле highload-блока, создаст если не было, обновит если существует и отличается
     *
     * @throws HelperException
     */
    public function saveField($hlblockName, array $field = []): int
    {
        $field = $this->prepareExportField($field);

        $entityHelper = new UserTypeEntityHelper();

        $field['ENTITY_ID'] = $entityHelper->transformEntityId(
            $this->getEntityId($hlblockName)
        );

        return $entityHelper->saveUserTypeEntity($field);
    }

    /**
     * Сохраняет highload-блок, создаст если не было, обновит если существует и отличается.
     *
     * @throws HelperException
     */
    public function saveHlblock(array $fields): int
    {
        $this->checkRequiredKeys($fields, ['NAME']);

        $exists = $this->getHlblock($fields['NAME']);
        $fields = $this->prepareExportHlblock($fields);

        if (empty($exists)) {
            $ok = $this->addHlblock($fields);

            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'HLBLOCK_CREATED',
                    [
                        '#NAME#' => $fields['NAME'],
                    ]
                )
            );

            return $ok;
        }

        $exportExists = $this->prepareExportHlblock($exists);

        if ($this->hasDiff($exportExists, $fields)) {
            $ok = $this->updateHlblock($exists['ID'], $fields);
            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'HLBLOCK_UPDATED',
                    [
                        '#NAME#' => $fields['NAME'],
                    ]
                )
            );

            $this->outDiffIf($ok, $exportExists, $fields);
            return $ok;
        }

        return $exists['ID'];
    }

    /**
     * Добавляет highload-блок
     *
     * @throws HelperException
     */
    public function addHlblock(array $fields): int
    {
        $this->checkRequiredKeys($fields, ['NAME', 'TABLE_NAME']);
        $fields['NAME'] = ucfirst($fields['NAME']);

        $lang = [];
        if (isset($fields['LANG'])) {
            $lang = $fields['LANG'];
            unset($fields['LANG']);
        }

        try {
            $result = HighloadBlockTable::add($fields);
            if ($result->isSuccess()) {
                $this->replaceHblockLangs($result->getId(), $lang);
                return (int)$result->getId();
            }

            throw new HelperException(implode(PHP_EOL, $result->getErrorMessages()));
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws Exception
     */
    protected function replaceHblockLangs(int $hlblockId, array $lang = []): void
    {
        if (!empty($lang) && is_array($lang)) {
            $this->deleteHblockLangs($hlblockId);
            $this->addHblockLangs($hlblockId, $lang);
        }
    }

    /**
     * @throws HelperException
     */
    protected function deleteHblockLangs(int $hlblockId): int
    {
        $del = 0;

        if (!class_exists('\Bitrix\Highloadblock\HighloadBlockLangTable')) {
            return $del;
        }

        try {
            $items = HighloadBlockLangTable::getList(
                [
                    'filter' => ['ID' => $hlblockId],
                ]
            )->fetchAll();

            foreach ($items as $item) {
                HighloadBlockLangTable::delete(['ID' => $item['ID'], 'LID' => $item['LID']]);
                $del++;
            }
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }

        return $del;
    }

    /**
     * @throws Exception
     */
    protected function addHblockLangs(int $hlblockId, array $lang = []): int
    {
        $add = 0;

        if (!class_exists('\Bitrix\Highloadblock\HighloadBlockLangTable')) {
            return $add;
        }

        foreach ($lang as $lid => $item) {
            if (!empty($item['NAME'])) {
                HighloadBlockLangTable::add(
                    [
                        'ID'   => $hlblockId,
                        'LID'  => $lid,
                        'NAME' => $item['NAME'],
                    ]
                );

                $add++;
            }
        }

        return $add;
    }

    /**
     * Обновляет highload-блок
     *
     * @throws HelperException
     */
    public function updateHlblock(int $hlblockId, array $fields): int
    {
        $lang = [];
        if (isset($fields['LANG'])) {
            $lang = $fields['LANG'];
            unset($fields['LANG']);
        }

        try {
            $result = HighloadBlockTable::update($hlblockId, $fields);

            if ($result->isSuccess()) {
                $this->replaceHblockLangs($hlblockId, $lang);
                return $hlblockId;
            }

            throw new HelperException(implode(PHP_EOL, $result->getErrorMessages()));
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Удаляет поле highload-блока
     *
     * @throws HelperException
     */
    public function deleteField(int|string $hlblockName, string $fieldName): bool
    {
        $entityHelper = new UserTypeEntityHelper();

        return $entityHelper->deleteUserTypeEntity(
            $this->getEntityId($hlblockName),
            $fieldName
        );
    }

    /**
     * Получает список полей highload-блока
     * Данные подготовлены для экспорта в миграцию
     *
     * @throws HelperException
     */
    public function exportFields(int|string $hlblockName): array
    {
        $entityHelper = new UserTypeEntityHelper();
        $fields = $entityHelper->exportUserTypeEntities(
            $this->getEntityId($hlblockName)
        );

        foreach ($fields as $index => $field) {
            unset($field['ENTITY_ID']);
            $fields[$index] = $field;
        }

        return $fields;
    }

    /**
     * Получает highload-блок
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @throws HelperException
     */
    public function exportHlblock(int|string $hlblockName): false|array
    {
        $item = $this->getHlblock($hlblockName);

        return $item ? $this->prepareExportHlblock($item) : false;
    }

    /**
     * @throws HelperException
     */
    public function getHlblockIfExists($hlblockName): array
    {
        $item = $this->getHlblock($hlblockName);
        if (!empty($item['ID'])) {
            return $item;
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_HLBLOCK_NOT_FOUND',
                ['#HLBLOCK#' => is_array($hlblockName) ? print_r($hlblockName, true) : $hlblockName]
            )
        );
    }

    /**
     * @throws HelperException
     */
    public function getHlblockTableName($hlblockName)
    {
        $item = $this->getHlblock($hlblockName);
        return ($item && isset($item['TABLE_NAME'])) ? $item['TABLE_NAME'] : '';
    }

    /**
     * Добавляет highload-блок, если его не существует
     *
     * @throws HelperException
     */
    public function addHlblockIfNotExists(array $fields): int
    {
        $this->checkRequiredKeys($fields, ['NAME']);

        $item = $this->getHlblock($fields['NAME']);
        if ($item) {
            return (int)$item['ID'];
        }

        return $this->addHlblock($fields);
    }

    /**
     * Обновляет highload-блок, если существует
     *
     * @throws HelperException
     */
    public function updateHlblockIfExists($hlblockName, array $fields): false|int
    {
        $item = $this->getHlblock($hlblockName);
        if (!empty($item['ID'])) {
            return $this->updateHlblock($item['ID'], $fields);
        }
        return false;
    }

    /**
     * Удаляет highload-блок, если существует
     *
     * @throws HelperException
     */
    public function deleteHlblockIfExists($hlblockName): bool
    {
        $item = $this->getHlblock($hlblockName);
        if (!empty($item['ID'])) {
            return $this->deleteHlblock($item['ID']);
        }
        return false;
    }

    /**
     * Удаляет highload-блок
     *
     * @throws HelperException
     */
    public function deleteHlblock(int $hlblockId): bool
    {
        try {
            $result = HighloadBlockTable::delete($hlblockId);
            if ($result->isSuccess()) {
                return true;
            }

            throw new HelperException(implode(PHP_EOL, $result->getErrorMessages()));
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function getHlblockNameById(int $hlblockId): string
    {
        $hlblock = $this->getHlblockIfExists($hlblockId);
        return $hlblock['NAME'];
    }

    /**
     * @throws HelperException
     */
    public function getHlblockIdByName(string $hlblockName): int
    {
        $hlblock = $this->getHlblockIfExists($hlblockName);
        return $hlblock['ID'];
    }

    /**
     * Получает highload-блок, бросает исключение если его не существует
     *
     * @throws HelperException
     */
    public function getHlblockIdIfExists($hlblockName): int
    {
        $hlblock = $this->getHlblockIfExists($hlblockName);
        return $hlblock['ID'];
    }
}
