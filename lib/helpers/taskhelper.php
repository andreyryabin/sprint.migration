<?php

namespace Sprint\Migration\Helpers;

use CTask;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;

class TaskHelper extends Helper
{
    /**
     * @throws HelperException
     */
    public function getTaskNameById(string $moduleId, int $taskId): string
    {
        $task = CTask::GetList([], ['ID' => $taskId, 'MODULE_ID' => $moduleId])->Fetch();

        if (!empty($task['NAME'])) {
            return (string)$task['NAME'];
        }

        throw new HelperException("Task with id=\"$taskId\" in module=\"$moduleId\ not found");
    }

    /**
     * @throws HelperException
     */
    public function getTaskIdByName(string $moduleId, string $taskName): int
    {
        $task = CTask::GetList([], ['NAME' => $taskName, 'MODULE_ID' => $moduleId])->Fetch();

        if (!empty($task['ID'])) {
            return (int)$task['ID'];
        }

        throw new HelperException("Task with name=\"$taskName\" in module=\"$moduleId\ not found");
    }

    /**
     * @throws HelperException
     */
    public function transformExtendedGroupCode(string $groupCode): string
    {
        $prefix = mb_substr($groupCode, 0, 1);
        if ($prefix === 'G') {
            $groupId = mb_substr($groupCode, 1);

            $groupHelper = new UserGroupHelper();
            $stringId = $groupHelper->getGroupCodeIfExists($groupId);

            return $prefix . $stringId;
        }

        if ($prefix === 'U') {
            $userId = mb_substr($groupCode, 1);

            $userHelper = new UserHelper();
            $userLogin = $userHelper->getUserLoginById($userId);

            return $prefix . $userLogin;
        }

        return $groupCode;
    }

    /**
     * @throws HelperException
     */
    public function revertExtendedGroupCode(string $groupCode): string
    {
        $prefix = mb_substr($groupCode, 0, 1);
        if ($prefix === 'G') {
            $stringId = mb_substr($groupCode, 1);

            $groupHelper = new UserGroupHelper();
            $groupId = $groupHelper->getGroupIdIfExists($stringId);

            return $prefix . $groupId;
        }

        if ($prefix === 'U') {
            $userLogin = mb_substr($groupCode, 1);

            $userHelper = new UserHelper();
            $userId = $userHelper->getUserIdByLogin($userLogin);

            return $prefix . $userId;
        }

        return $groupCode;
    }
}
