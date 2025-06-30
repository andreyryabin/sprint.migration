<?php

namespace Sprint\Migration\Helpers\Traits\Hlblock;

use Bitrix\Highloadblock\HighloadBlockRightsTable;
use Exception;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helpers\TaskHelper;

trait HlblockPermissionsTrait
{
    /**
     * @throws HelperException
     */
    public function exportExtendedPermissions(int $hlblockId): array
    {
        $permissions = $this->getHlblockRights($hlblockId);
        $taskHelper = new TaskHelper();

        $result = [];
        foreach ($permissions as $permission) {
            $permission['TASK_ID'] = $taskHelper->getTaskNameById('highloadblock', $permission['TASK_ID']);
            $permission['ACCESS_CODE'] = $taskHelper->transformExtendedGroupCode($permission['ACCESS_CODE']);

            $this->unsetKeys($permission, ['ID', 'HL_ID']);

            $result[] = $permission;
        }
        return $result;
    }

    /**
     * @throws HelperException
     */
    public function saveExtendedPermissions(int $hlblockId, array $permissions = []): void
    {
        $this->checkHlblockRightsTable();

        $taskHelper = new TaskHelper();

        try {
            foreach ($this->getHlblockRights($hlblockId) as $right) {
                HighloadBlockRightsTable::delete($right['ID']);
            }

            foreach ($permissions as $permission) {
                HighloadBlockRightsTable::add(
                    [
                        'HL_ID'       => $hlblockId,
                        'TASK_ID'     => $taskHelper->getTaskIdByName('highloadblock', $permission['TASK_ID']),
                        'ACCESS_CODE' => $taskHelper->revertExtendedGroupCode($permission['ACCESS_CODE']),
                    ]
                );
            }
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
