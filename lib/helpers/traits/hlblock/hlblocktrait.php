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
     * @param array $filter
     *
     * @throws HelperException
     * @return array
     */
    public function exportHlblocks(array $filter = [])
    {
        $items = $this->getHlblocks($filter);

        $export = [];
        foreach ($items as $item) {
            $export[] = $this->prepareExportHlblock($item);
        }

        return $export;
    }

    /**
     * Получает список highload-блоков
     *
     * @param array $filter
     *
     * @throws HelperException
     * @return array
     */
    public function getHlblocks(array $filter = [])
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
    protected function prepareHlblock($item)
    {
        if (empty($item['ID'])) {
            return $item;
        }

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

    protected function prepareField($item)
    {
        if (empty($item['ID'])) {
            return $item;
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

    protected function prepareExportHlblock($item)
    {
        if (empty($item)) {
            return $item;
        }

        unset($item['ID']);
        unset($item['TITLE']);

        return $item;
    }

    protected function prepareExportField($item)
    {
        if (empty($item)) {
            return $item;
        }

        unset($item['ID']);
        unset($item['TITLE']);

        return $item;
    }

    /**
     * @param $hlblockName
     *
     * @throws HelperException
     * @return string
     */
    public function getEntityId($hlblockName)
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
     * @return int|mixed
     */
    public function getHlblockId($hlblockName)
    {
        $item = $this->getHlblock($hlblockName);
        return ($item && isset($item['ID'])) ? $item['ID'] : 0;
    }

    /**
     * Получает highload-блок
     *
     * @param $hlblockName - id, имя или фильтр
     *
     * @throws HelperException
     * @return array|false
     */
    public function getHlblock($hlblockName)
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

            return $this->prepareHlblock($hlblock);
        } catch (Exception $e) {
            throw new HelperException($e->getMessage());
        }
    }

    /**
     * @param $hlblockName
     * @param $field
     *
     * @throws HelperException
     * @return string|void
     */
    public function getFieldUid($hlblockName, $field)
    {
        $entityHelper = new UserTypeEntityHelper();

        if (!is_array($field)) {
            //на вход пришел id или название поля
            if (is_numeric($field)) {
                $field = $entityHelper->getUserTypeEntityById($field);
            } else {
                $field = $entityHelper->getUserTypeEntity(
                    $this->getEntityId($hlblockName),
                    $field
                );
            }
        }

        if (!empty($field['FIELD_NAME'])) {
            return $field['FIELD_NAME'];
        }
        throw new HelperException(Locale::getMessage('ERR_HLBLOCK_FIELD_NOT_FOUND'));
    }

    /**
     * @param $hlblockName
     * @param $fieldName
     *
     * @throws HelperException
     * @return mixed
     */
    public function getFieldType($hlblockName, $fieldName)
    {
        $field = $this->getField($hlblockName, $fieldName);
        return $field['USER_TYPE_ID'];
    }

    /**
     * @throws HelperException
     */
    public function getField($hlblockName, $fieldName): array
    {
        $entityHelper = new UserTypeEntityHelper();
        $field = $entityHelper->getUserTypeEntity(
            $this->getEntityId($hlblockName),
            $fieldName
        );

        if (!empty($field)) {
            return $this->prepareField($field);
        }

        throw new HelperException(Locale::getMessage('ERR_HLBLOCK_FIELD_NOT_FOUND'));
    }

    /**
     * Получает список полей highload-блока
     *
     * @param $hlblockName int|string|array - id, имя или фильтр
     *
     * @throws HelperException
     * @return array
     */
    public function getFields($hlblockName): array
    {
        $entityHelper = new UserTypeEntityHelper();
        $fields = $entityHelper->getUserTypeEntities(
            $this->getEntityId($hlblockName)
        );
        return array_map(fn($field) => $this->prepareField($field), $fields);
    }

    /**
     * @param $hlblockName
     * @param $fieldName
     *
     * @throws HelperException
     * @return bool
     */
    public function isFieldMultiple($hlblockName, $fieldName)
    {
        $field = $this->getField($hlblockName, $fieldName);
        return ($field['MULTIPLE'] == 'Y');
    }

    /**
     * @param $hlblockName
     * @param $fieldName
     * @param $xmlId
     *
     * @throws HelperException
     * @return mixed|string
     */
    public function getFieldEnumIdByXmlId($hlblockName, $fieldName, $xmlId)
    {
        $field = $this->getField($hlblockName, $fieldName);
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
    public function getFieldEnumXmlIdById($hlblockName, $fieldName, $valueId)
    {
        $field = $this->getField($hlblockName, $fieldName);
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
        $valueIds = is_array($valueIds) ? $valueIds : [$valueIds];

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
     * @param $hlblockName
     * @param $fieldUid
     *
     * @throws HelperException
     * @return int
     */
    public function getFieldIdByUid($hlblockName, $fieldUid)
    {
        $fieldId = 0;

        if (empty($fieldUid)) {
            return $fieldId;
        }

        if (is_numeric($fieldUid)) {
            return $fieldUid;
        }

        $field = $this->getField($hlblockName, $fieldUid);

        return ($field) ? (int)$field['ID'] : 0;
    }

    /**
     * Сохраняет поле highload-блока
     * Создаст если не было, обновит если существует и отличается
     *
     * @param       $hlblockName int|string|array - id, имя или фильтр
     * @param array $field
     *
     * @throws HelperException
     * @return bool|int|mixed
     */
    public function saveField($hlblockName, $field = [])
    {
        $field['ENTITY_ID'] = $this->getEntityId($hlblockName);

        $field = $this->prepareExportField($field);

        $entityHelper = new UserTypeEntityHelper();

        return $entityHelper->saveUserTypeEntity($field);
    }

    /**
     * Сохраняет highload-блок
     * Создаст если не было, обновит если существует и отличается
     *
     * @param array $fields
     *
     * @throws HelperException
     * @return bool|int|mixed
     */
    public function saveHlblock($fields)
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
     * @param array $fields
     *
     * @throws HelperException
     * @return int|void
     */
    public function addHlblock($fields)
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
                return $result->getId();
            }

            throw new HelperException(implode(PHP_EOL, $result->getErrorMessages()));
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param int   $hlblockId
     * @param array $lang
     *
     * @throws Exception
     */
    protected function replaceHblockLangs($hlblockId, $lang = [])
    {
        if (!empty($lang) && is_array($lang)) {
            $this->deleteHblockLangs($hlblockId);
            $this->addHblockLangs($hlblockId, $lang);
        }
    }

    /**
     * @param int $hlblockId
     *
     * @throws Exception
     * @return int
     */
    protected function deleteHblockLangs($hlblockId)
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
        } catch (Exception $e) {
            $items = [];
        }

        foreach ($items as $item) {
            HighloadBlockLangTable::delete(['ID' => $item['ID'], 'LID' => $item['LID']]);
            $del++;
        }

        return $del;
    }

    /**
     * @param int   $hlblockId
     * @param array $lang
     *
     * @throws Exception
     * @return int
     */
    protected function addHblockLangs($hlblockId, $lang = [])
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
     * @param $hlblockId
     * @param $fields
     *
     * @throws HelperException
     * @return int|void
     */
    public function updateHlblock($hlblockId, $fields)
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
     * @param $hlblockName
     * @param $fieldName
     *
     * @throws HelperException
     * @return bool
     */
    public function deleteField($hlblockName, $fieldName)
    {
        $entityHelper = new UserTypeEntityHelper();
        return $entityHelper->deleteUserTypeEntity(
            $this->getEntityId($hlblockName),
            $fieldName
        );
    }

    /**
     * Получает список полей highload-блока
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @param $hlblockName
     *
     * @throws HelperException
     * @return array
     */
    public function exportFields($hlblockName)
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
     * @param $hlblockName
     *
     * @throws HelperException
     * @return mixed
     */
    public function exportHlblock($hlblockName)
    {
        return $this->prepareExportHlblock(
            $this->getHlblock($hlblockName)
        );
    }

    /**
     * @param $hlblockName
     *
     * @throws HelperException
     * @return array|void
     */
    public function getHlblockIfExists($hlblockName)
    {
        $item = $this->getHlblock($hlblockName);
        if ($item && isset($item['ID'])) {
            return $item;
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_HLBLOCK_NOT_FOUND',
                ['#HLBLOCK#' => is_array($hlblockName) ? var_export($hlblockName, true) : $hlblockName]
            )
        );
    }

    public function getHlblockTableName($hlblockName)
    {
        $item = $this->getHlblock($hlblockName);
        return ($item && isset($item['TABLE_NAME'])) ? $item['TABLE_NAME'] : '';
    }

    /**
     * Добавляет highload-блок, если его не существует
     *
     * @param array $fields
     *
     * @throws HelperException
     * @return int|mixed
     */
    public function addHlblockIfNotExists($fields)
    {
        $this->checkRequiredKeys($fields, ['NAME']);

        $item = $this->getHlblock($fields['NAME']);
        if ($item) {
            return $item['ID'];
        }

        return $this->addHlblock($fields);
    }

    /**
     * Обновляет highload-блок, если существует
     *
     * @param $hlblockName
     * @param $fields
     *
     * @throws HelperException
     * @return bool|int
     */
    public function updateHlblockIfExists($hlblockName, $fields)
    {
        $item = $this->getHlblock($hlblockName);
        if (!$item) {
            return false;
        }

        return $this->updateHlblock($item['ID'], $fields);
    }

    /**
     * Удаляет highload-блок, если существует
     *
     * @param $hlblockName
     *
     * @throws HelperException
     * @return bool
     */
    public function deleteHlblockIfExists($hlblockName)
    {
        $item = $this->getHlblock($hlblockName);
        if (!$item) {
            return false;
        }

        return $this->deleteHlblock($item['ID']);
    }

    /**
     * Удаляет highload-блок
     *
     * @param $hlblockId
     *
     * @throws HelperException
     * @return bool|void
     */
    public function deleteHlblock($hlblockId)
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
     * @param        $hlblock
     *
     * @throws HelperException
     * @return string|void
     */
    public function getHlblockUid($hlblock)
    {
        if (!is_array($hlblock)) {
            // если хайлоадблок не найден, надо показать что искали
            $getHlblock = $this->getHlblock($hlblock);

            if (false === $getHlblock) {
                throw new HelperException(
                    Locale::getMessage(
                        'ERR_HLBLOCK_NOT_FOUND',
                        ['#HLBLOCK#' => $hlblock]
                    )
                );
            }

            $hlblock = $getHlblock;
        }

        if (!empty($hlblock['NAME'])) {
            return $hlblock['NAME'];
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_HLBLOCK_NOT_FOUND',
                ['#HLBLOCK#' => is_array($hlblock) ? var_export($hlblock, true) : $hlblock]
            )
        );
    }

    /**
     * @param $hlblockUid
     *
     * @throws HelperException
     * @return int
     */
    public function getHlblockIdByUid($hlblockUid)
    {
        if (empty($hlblockUid)) {
            return 0;
        }

        return $this->getHlblockIdIfExists($hlblockUid);
    }

    /**
     * Получает highload-блок, бросает исключение если его не существует
     *
     * @param $hlblockName - id, имя или фильтр
     *
     * @throws HelperException
     * @return int|void
     */
    public function getHlblockIdIfExists($hlblockName)
    {
        $item = $this->getHlblock($hlblockName);
        if ($item && isset($item['ID'])) {
            return $item['ID'];
        }

        if (is_array($hlblockName)) {
            $hlblockUid = var_export($hlblockName, true);
        } else {
            $hlblockUid = $hlblockName;
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_HLBLOCK_NOT_FOUND',
                ['#HLBLOCK#' => $hlblockUid]
            )
        );
    }
}
