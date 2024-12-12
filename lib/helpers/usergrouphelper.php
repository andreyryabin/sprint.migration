<?php

namespace Sprint\Migration\Helpers;

use CGroup;
use CModule;
use CSite;
use CTask;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;

class UserGroupHelper extends Helper
{
    /**
     * Получает список групп пользователей
     *
     * @param array $filter
     *
     * @return array
     */
    public function getGroups($filter = [])
    {
        $by = 'c_sort';
        $order = 'asc';

        $res = [];

        $dbres = CGroup::GetList($by, $order, $filter);
        while ($item = $dbres->Fetch()) {
            $res[] = $this->getGroup($item['ID']);
        }

        return $res;
    }

    /**
     * Получает группу пользователей
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @param $code
     *
     * @throws HelperException
     * @return array|void
     */
    public function exportGroup($code)
    {
        $item = $this->prepareExportGroup(
            $this->getGroup($code)
        );

        if (!empty($item['STRING_ID'])) {
            return $item;
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_USER_GROUP_CODE_NOT_FOUND'
            )
        );
    }

    /**
     * Получает список групп пользователей
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @param array $filter
     *
     * @return array
     */
    public function exportGroups($filter = [])
    {
        $items = $this->getGroups($filter);
        $exports = [];
        foreach ($items as $item) {
            if (!empty($item['STRING_ID'])) {
                $exports[] = $this->prepareExportGroup($item);
            }
        }
        return $exports;
    }

    /**
     * Получает код группы пользователей по id
     *
     * @param int|string  $id
     * @param bool|string $default
     *
     * @return bool|string
     */
    public function getGroupCode($id, $default = false)
    {
        $group = $this->getGroup($id);
        return ($group) ? $group['STRING_ID'] : $default;
    }

    /**
     * Получает id группы пользователей по id
     *
     * @param int|string  $code
     * @param bool|string $default
     *
     * @return bool|string
     */
    public function getGroupId($code, $default = false)
    {
        $group = $this->getGroup($code);
        return ($group) ? $group['ID'] : $default;
    }

    /**
     * Получает группу пользователей
     *
     * @param $code int|string - id или код группы
     *
     * @return array|bool
     */
    public function getGroup($code)
    {
        $groupId = is_numeric($code) ? $code : CGroup::GetIDByCode($code);

        if (empty($groupId)) {
            return false;
        }

        /* extract SECURITY_POLICY */
        $item = CGroup::GetByID($groupId)->Fetch();
        if (empty($item)) {
            return false;
        }

        if (!empty($item['SECURITY_POLICY'])) {
            $item['SECURITY_POLICY'] = unserialize($item['SECURITY_POLICY']);
        }

        if ($item['ID'] == 1) {
            $item['STRING_ID'] = 'administrators';
        } elseif ($item['ID'] == 2) {
            $item['STRING_ID'] = 'everyone';
        }

        return $item;
    }

    /**
     * Сохраняет группу
     * Создаст если не было, обновит если существует и отличается
     *
     * @param string $code
     * @param array  $fields
     *
     * @throws HelperException
     * @return bool|int|mixed
     */
    public function saveGroup($code, $fields = [])
    {
        $fields['STRING_ID'] = $code;
        $this->checkRequiredKeys($fields, ['STRING_ID', 'NAME']);

        $exists = $this->getGroup($fields['STRING_ID']);
        $fields = $this->prepareExportGroup($fields);

        if (empty($exists)) {
            $ok = $this->getMode('test') ? true : $this->addGroup($fields['STRING_ID'], $fields);
            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'USER_GROUP_CREATED',
                    [
                        '#NAME#' => $fields['NAME'],
                    ]
                )
            );
            return $ok;
        }

        $exportExists = $this->prepareExportGroup($exists);

        if ($this->hasDiff($exportExists, $fields)) {
            $ok = $this->getMode('test') ? true : $this->updateGroup($exists['ID'], $fields);
            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'USER_GROUP_UPDATED',
                    [
                        '#NAME#' => $fields['NAME'],
                    ]
                )
            );
            $this->outDiffIf($ok, $exportExists, $fields);
            return $ok;
        }

        return $this->getMode('test') ? true : $exists['ID'];
    }

    /**
     * Добаляет группу пользователей если она не существует
     *
     * @param       $code
     * @param array $fields
     *
     * @throws HelperException
     * @return int
     */
    public function addGroupIfNotExists($code, $fields = [])
    {
        $groupId = $this->getGroupId($code);
        if ($groupId) {
            return intval($groupId);
        }

        return $this->addGroup($code, $fields);
    }

    /**
     * Обновляет группу пользователей если она существует
     *
     * @param       $code
     * @param array $fields
     *
     * @throws HelperException
     * @return bool|int
     */
    public function updateGroupIfExists($code, $fields = [])
    {
        $groupId = $this->getGroupId($code);
        if (!$groupId) {
            return false;
        }

        return $this->updateGroup($groupId, $fields);
    }

    /**
     * Добавляет группу пользователей
     *
     * @param       $code
     * @param array $fields , , обязательные параметры - название групы
     *
     * @throws HelperException
     * @return int|void
     */
    public function addGroup($code, $fields = [])
    {
        $fields['STRING_ID'] = $code;
        $this->checkRequiredKeys($fields, ['STRING_ID', 'NAME']);

        $group = new CGroup;
        $groupId = $group->Add($this->prepareFields($fields));

        if ($groupId) {
            return intval($groupId);
        }

        throw new HelperException($group->LAST_ERROR);
    }

    /**
     * Обновляет группу пользователей
     *
     * @param       $groupId
     * @param array $fields
     *
     * @throws HelperException
     * @return int|void
     */
    public function updateGroup($groupId, $fields = [])
    {
        if (empty($fields)) {
            throw new HelperException(
                Locale::getMessage(
                    'ERR_SET_FIELDS_FOR_UPDATE_GROUP'
                )
            );
        }

        $group = new CGroup;
        if ($group->Update($groupId, $this->prepareFields($fields))) {
            return intval($groupId);
        }

        throw new HelperException($group->LAST_ERROR);
    }

    /**
     * Удаляет группу пользователей
     *
     * @param $code
     *
     * @return bool
     */
    public function deleteGroup($code)
    {
        $groupId = $this->getGroupId($code);
        if (empty($groupId)) {
            return false;
        }

        $group = new CGroup;
        $group->Delete($groupId);
        return true;
    }

    /**
     * @param array $filter
     *
     * @return array
     * @deprecated
     */
    public function getGroupsByFilter($filter = [])
    {
        return $this->getGroups($filter);
    }

    /**
     * Cброс настроек доступа группы
     *
     * @param $groupId
     */
    public function deleteGroupPermissions($groupId)
    {
        global $APPLICATION;

        $moduleIds = [];
        $dbres = CModule::GetList();
        while ($item = $dbres->Fetch()) {
            $moduleIds[] = $item['ID'];
        }

        $by = "sort";
        $order = "asc";

        $siteIds = [];
        $dbres = CSite::GetList($by, $order, ["ACTIVE" => "Y"]);
        while ($item = $dbres->GetNext()) {
            $siteIds[] = $item["ID"];
        }

        foreach ($moduleIds as $moduleId) {
            $APPLICATION->DelGroupRight($moduleId, [$groupId], false);
            foreach ($siteIds as $siteId) {
                $APPLICATION->DelGroupRight($moduleId, [$groupId], $siteId);
            }
        }

        CGroup::SetSubordinateGroups($groupId);

        $tasksMap = CGroup::GetTasks($groupId, true);
        foreach ($tasksMap as $moduleId => $taskId) {
            CTask::Delete($taskId, false);
        }
    }

    protected function prepareExportGroup($item)
    {
        if (empty($item)) {
            return $item;
        }

        unset($item['ID']);
        unset($item['TIMESTAMP_X']);

        return $item;
    }

    protected function prepareFields($fields)
    {
        if (is_array($fields['SECURITY_POLICY'])) {
            $fields['SECURITY_POLICY'] = serialize($fields['SECURITY_POLICY']);
        }

        return $fields;
    }
}
