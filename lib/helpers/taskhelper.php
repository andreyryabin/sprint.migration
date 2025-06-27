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
    public function getTaskNameByIdIfExists(string $moduleId, int $taskId)
    {
        $task = CTask::GetList([], ['ID' => $taskId, 'MODULE_ID' => $moduleId])->Fetch();

        if (!empty($task['NAME'])) {
            return $task['NAME'];
        }

        throw new HelperException("Task with id=\"$taskId\" in module=\"$moduleId\ not found");
    }

    /**
     * @throws HelperException
     */
    public function getTaskIdByNameIfExists(string $moduleId, string $taskName)
    {
        $task = CTask::GetList([], ['NAME' => $taskName, 'MODULE_ID' => $moduleId])->Fetch();

        if (!empty($task['ID'])) {
            return $task['ID'];
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
            $stringId = $groupHelper->getGroupCodeByIdIfExists($groupId);

            return $prefix . $stringId;
        }

        if ($prefix === 'U') {
            $userId = mb_substr($groupCode, 1);

            $userHelper = new UserHelper();
            $userLogin = $userHelper->getUserLoginByIdIfExists($userId);

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
            $groupId = $groupHelper->getGroupIdByCodeIfExists($stringId);

            return $prefix . $groupId;
        }

        if ($prefix === 'U') {
            $userLogin = mb_substr($groupCode, 1);

            $userHelper = new UserHelper();
            $userId = $userHelper->getUserIdByLoginIfExists($userLogin);

            return $prefix . $userId;
        }

        return $groupCode;
    }
}
