<?php

namespace Sprint\Migration\Helpers\Traits\Iblock;

use CIBlock;
use CIBlockRights;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helpers\TaskHelper;
use Sprint\Migration\Helpers\UserGroupHelper;

trait IblockPermissionsTrait
{
    /**
     * Получает права доступа к инфоблоку для групп
     * возвращает массив вида [$groupCode => $letter]
     */
    public function getGroupPermissions(int $iblockId): array
    {
        return CIBlock::GetGroupPermissions($iblockId);
    }

    public function exportGroupPermissions(int $iblockId): array
    {
        $groupHelper = new UserGroupHelper();

        $permissions = $this->getGroupPermissions($iblockId);

        $result = [];
        foreach ($permissions as $groupId => $letter) {
            $groupCode = $groupHelper->getGroupCode($groupId);
            $groupCode = !empty($groupCode) ? $groupCode : $groupId;
            $result[$groupCode] = $letter;
        }

        return $result;
    }

    /**
     * @throws HelperException
     */
    public function exportExtendedPermissions(int $iblockId): array
    {
        $permissions = (new CIBlockRights($iblockId))->GetRights();
        $taskHelper = new TaskHelper();

        $result = [];
        foreach ($permissions as $permissionId => $permission) {
            $permission['ID'] = $permissionId;
            $permission['TASK_ID'] = $taskHelper->getTaskNameById('iblock', $permission['TASK_ID']);
            $permission['GROUP_CODE'] = $taskHelper->transformExtendedGroupCode($permission['GROUP_CODE']);

            $this->unsetKeys($permission, ['ENTITY_TYPE', 'ENTITY_ID', 'ID']);
            $this->unsetItem($permission, $this->getDefaultExtendedPermission());

            $result[] = $permission;
        }

        return $result;
    }

    /**
     * @throws HelperException
     */
    public function saveExtendedPermissions(int $iblockId, array $permissions = []): bool
    {
        $taskHelper = new TaskHelper();

        $result = [];
        foreach ($permissions as $index => $permission) {
            $permission = $this->merge($permission, $this->getDefaultExtendedPermission());

            $permission['TASK_ID'] = $taskHelper->getTaskIdByName('iblock', $permission['TASK_ID']);
            $permission['GROUP_CODE'] = $taskHelper->revertExtendedGroupCode($permission['GROUP_CODE']);

            $result['n' . $index] = $permission;
        }

        return (new CIBlockRights($iblockId))->SetRights($result);
    }

    public function saveGroupPermissions(int $iblockId, array $permissions = []): void
    {
        $groupHelper = new UserGroupHelper();

        $result = [];
        foreach ($permissions as $groupCode => $letter) {
            $groupId = is_numeric($groupCode) ? $groupCode : $groupHelper->getGroupId($groupCode);
            $result[$groupId] = $letter;
        }

        $this->setGroupPermissions($iblockId, $result);
    }

    /**
     * Устанавливает права доступа к инфоблоку для групп,
     * предыдущие права сбрасываются,
     * принимает массив вида [$groupCode => $letter]
     */
    public function setGroupPermissions(int $iblockId, array $permissions = []): void
    {
        CIBlock::SetPermission($iblockId, $permissions);
    }

    protected function getDefaultExtendedPermission(): array
    {
        return [
            'DO_INHERIT'   => 'Y',
            'IS_INHERITED' => 'N',
            'OVERWRITED'   => 0,
            'XML_ID'       => null,
        ];
    }
}
