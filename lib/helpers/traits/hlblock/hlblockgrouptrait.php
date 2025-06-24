<?php

namespace Sprint\Migration\Helpers\Traits\Hlblock;

use Bitrix\Highloadblock\HighloadBlockRightsTable;
use CTask;
use Exception;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helpers\UserGroupHelper;

trait HlblockGroupTrait
{
    /**
     * @param $hlblockId
     *
     * @throws HelperException
     * @return array
     */
    public function exportGroupPermissions($hlblockId)
    {
        $groupHelper = new UserGroupHelper();
        $permissions = $this->getGroupPermissions($hlblockId);

        $result = [];
        foreach ($permissions as $groupId => $letter) {
            $groupCode = $groupHelper->getGroupCode($groupId);
            $groupCode = !empty($groupCode) ? $groupCode : $groupId;
            $result[$groupCode] = $letter;
        }

        return $result;
    }

    /**
     * Получает права доступа к highload-блоку для групп
     * возвращает массив вида [$groupId => $letter]
     *
     * @param $hlblockId
     *
     * @return array
     */
    public function getGroupPermissions($hlblockId)
    {
        $permissions = [];
        $rights = $this->getGroupRights($hlblockId);
        foreach ($rights as $right) {
            $permissions[$right['GROUP_ID']] = $right['LETTER'];
        }
        return $permissions;
    }

    /**
     * @param int $hlblockId
     *
     * @throws HelperException
     * @return array
     */
    protected function getGroupRights($hlblockId)
    {
        $result = [];
        if (!class_exists('\Bitrix\Highloadblock\HighloadBlockRightsTable')) {
            return $result;
        }

        try {
            $items = HighloadBlockRightsTable::getList(
                [
                    'filter' => [
                        'HL_ID' => $hlblockId,
                    ],
                ]
            )->fetchAll();
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }

        foreach ($items as $item) {
            if (strpos($item['ACCESS_CODE'], 'G') !== 0) {
                continue;
            }

            $groupId = (int)substr($item['ACCESS_CODE'], 1);
            if (empty($groupId)) {
                continue;
            }

            $letter = CTask::GetLetter($item['TASK_ID']);
            if (empty($letter)) {
                continue;
            }

            $item['LETTER'] = $letter;
            $item['GROUP_ID'] = $groupId;

            $result[] = $item;
        }

        return $result;
    }

    public function saveGroupPermissions($hlblockId, $permissions = [])
    {
        $groupHelper = new UserGroupHelper();

        $result = [];
        foreach ($permissions as $groupCode => $letter) {
            $groupId = is_numeric($groupCode) ? $groupCode : $groupHelper->getGroupId($groupCode);
            $result[$groupId] = $letter;
        }

        $this->setGroupPermissions($hlblockId, $result);
    }

    /**
     * Устанавливает права доступа к highload-блоку для групп
     * предыдущие права сбрасываются
     * принимает массив вида [$groupId => $letter]
     *
     * @param int   $hlblockId
     * @param array $permissions
     *
     * @throws HelperException
     * @return bool
     */
    public function setGroupPermissions($hlblockId, $permissions = [])
    {
        if (!class_exists('\Bitrix\Highloadblock\HighloadBlockRightsTable')) {
            return false;
        }

        $rights = $this->getGroupRights($hlblockId);

        try {
            foreach ($rights as $right) {
                HighloadBlockRightsTable::delete($right['ID']);
            }

            foreach ($permissions as $groupId => $letter) {
                $taskId = CTask::GetIdByLetter($letter, 'highloadblock');

                if (!empty($taskId)) {
                    HighloadBlockRightsTable::add(
                        [
                            'HL_ID'       => $hlblockId,
                            'TASK_ID'     => $taskId,
                            'ACCESS_CODE' => 'G' . $groupId,
                        ]
                    );
                }
            }
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }

        return true;
    }
}
