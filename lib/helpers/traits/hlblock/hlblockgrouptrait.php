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
     * @throws HelperException
     */
    public function exportGroupPermissions(int $hlblockId): array
    {
        $groupHelper = new UserGroupHelper();
        $permissions = $this->getGroupPermissions($hlblockId);

        $result = [];
        foreach ($permissions as $groupId => $letter) {
            $stringId = $groupHelper->getGroupCodeIfExists($groupId);
            $result[$stringId] = $letter;
        }

        return $result;
    }

    /**
     * Получает права доступа к highload-блоку для групп
     * возвращает массив вида [$groupId => $letter]
     *
     * @throws HelperException
     */
    public function getGroupPermissions(int $hlblockId): array
    {
        $permissions = [];
        foreach ($this->getGroupRightsWithLetters($hlblockId) as $right) {
            $permissions[$right['GROUP_ID']] = $right['LETTER'];
        }
        return $permissions;
    }

    /**
     * @throws HelperException
     */
    protected function getGroupRightsWithLetters(int $hlblockId): array
    {
        $result = [];
        foreach ($this->getHlblockRights($hlblockId) as $item) {
            if (!str_starts_with($item['ACCESS_CODE'], 'G')) {
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

    /**
     * @throws HelperException
     */
    public function saveGroupPermissions(int $hlblockId, array $permissions = []): void
    {
        $groupHelper = new UserGroupHelper();

        $result = [];
        foreach ($permissions as $groupCode => $letter) {
            $groupId = $groupHelper->getGroupIdIfExists($groupCode);
            $result[$groupId] = $letter;
        }

        $this->setGroupPermissions($hlblockId, $result);
    }

    /**
     * Устанавливает права доступа к highload-блоку для групп
     * предыдущие права сбрасываются, принимает массив вида [$groupId => $letter]
     *
     * @throws HelperException
     */
    public function setGroupPermissions(int $hlblockId, array $permissions = []): void
    {
        $this->checkHlblockRightsTable();

        try {
            foreach ($this->getGroupRightsWithLetters($hlblockId) as $right) {
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
    }
}
