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
     */
    public function getGroups(array $filter = []): array
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
     * @throws HelperException
     */
    public function exportGroup(int|string $code): array
    {
        $item = $this->getGroup($code);

        if (!empty($item['STRING_ID'])) {
            return $this->prepareExportGroup($item);
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
     */
    public function exportGroups(array $filter = []): array
    {
        $items = array_filter(
            $this->getGroups($filter),
            fn($item) => !empty($item['STRING_ID'])
        );

        return array_map(
            fn($item) => $this->prepareExportGroup($item),
            $items
        );
    }

    /**
     * Получает код группы пользователей по id
     */
    public function getGroupCode(int|string $id): bool|string
    {
        $group = $this->getGroup($id);
        return ($group) ? (string)$group['STRING_ID'] : false;
    }

    /**
     * Получает id группы пользователей по коду
     */
    public function getGroupId(int|string $code): bool|int
    {
        $group = $this->getGroup($code);
        return ($group) ? $group['ID'] : false;
    }

    /**
     * @throws HelperException
     */
    public function getGroupIdIfExists(int|string $code): int
    {
        $group = $this->getGroupIfExists($code);
        return (int)$group['ID'];
    }

    /**
     * @throws HelperException
     */
    public function getGroupCodeIfExists(int|string $id): string
    {
        $group = $this->getGroupIfExists($id);
        return (string)$group['STRING_ID'];
    }

    /**
     * Получает группу пользователей
     */
    public function getGroup(int|string $code): bool|array
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
     * @throws HelperException
     */
    public function getGroupIfExists(int|string $code): array
    {
        $item = $this->getGroup($code);
        if (!empty($item['STRING_ID'])) {
            return $item;
        }
        throw new HelperException("Group with id_or_code=\"$code\" not found or has empty STRING_ID");
    }

    /**
     * Сохраняет группу, создаст если не было, обновит если существует и отличается
     *
     * @throws HelperException
     */
    public function saveGroup(string $code, array $fields = []): int
    {
        $fields['STRING_ID'] = $code;
        $this->checkRequiredKeys($fields, ['STRING_ID', 'NAME']);

        $exists = $this->getGroup($fields['STRING_ID']);
        $fields = $this->prepareExportGroup($fields);

        if (empty($exists)) {
            $ok = $this->addGroup($fields['STRING_ID'], $fields);
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
            $ok = $this->updateGroup($exists['ID'], $fields);
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

        return (int)$exists['ID'];
    }

    /**
     * Добаляет группу пользователей если она не существует
     *
     * @throws HelperException
     */
    public function addGroupIfNotExists(int|string $code, array $fields = []): int
    {
        $groupId = $this->getGroupId($code);
        if ($groupId) {
            return (int)$groupId;
        }

        return $this->addGroup($code, $fields);
    }

    /**
     * Обновляет группу пользователей если она существует
     *
     * @throws HelperException
     */
    public function updateGroupIfExists(int|string $code, array $fields = []): bool|int
    {
        $groupId = $this->getGroupId($code);
        if ($groupId) {
            return $this->updateGroup($groupId, $fields);
        }

        return false;
    }

    /**
     * Добавляет группу пользователей
     *
     * @throws HelperException
     */
    public function addGroup(string $code, array $fields = []): int
    {
        $fields['STRING_ID'] = $code;
        $this->checkRequiredKeys($fields, ['STRING_ID', 'NAME']);

        $group = new CGroup;
        $groupId = $group->Add($this->prepareFields($fields));

        if ($groupId) {
            return (int)$groupId;
        }

        throw new HelperException($group->LAST_ERROR);
    }

    /**
     * Обновляет группу пользователей
     *
     * @throws HelperException
     */
    public function updateGroup(int $groupId, array $fields = []): int
    {
        if (empty($fields)) {
            throw new HelperException(
                Locale::getMessage('ERR_SET_FIELDS_FOR_UPDATE_GROUP')
            );
        }

        $group = new CGroup;
        if ($group->Update($groupId, $this->prepareFields($fields))) {
            return $groupId;
        }

        throw new HelperException($group->LAST_ERROR);
    }

    /**
     * Удаляет группу пользователей
     */
    public function deleteGroup(int|string $code): bool
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
     * Сброс настроек доступа группы
     */
    public function deleteGroupPermissions(int $groupId): void
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
            $APPLICATION->DelGroupRight($moduleId, [$groupId]);
            foreach ($siteIds as $siteId) {
                $APPLICATION->DelGroupRight($moduleId, [$groupId], $siteId);
            }
        }

        CGroup::SetSubordinateGroups($groupId);

        $tasksMap = CGroup::GetTasks($groupId);
        foreach ($tasksMap as $taskId) {
            CTask::Delete($taskId, false);
        }
    }

    protected function prepareExportGroup(array $item): array
    {
        $this->unsetKeys($item, [
            'ID',
            'TIMESTAMP_X',
        ]);

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
