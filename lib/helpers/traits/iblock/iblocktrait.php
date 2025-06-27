<?php

namespace Sprint\Migration\Helpers\Traits\Iblock;

use Bitrix\Iblock\InheritedProperty\IblockTemplates;
use CIBlock;
use CIBlockRights;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helpers\TaskHelper;
use Sprint\Migration\Helpers\UserGroupHelper;
use Sprint\Migration\Locale;

trait IblockTrait
{
    /**
     * Получает инфоблок, бросает исключение если его не существует
     *
     * @throws HelperException
     */
    public function getIblockIfExists(array|int|string $code, string $typeId = ''): array
    {
        $item = $this->getIblock($code, $typeId);
        if (!empty($item['ID'])) {
            return $item;
        }
        throw new HelperException(
            Locale::getMessage(
                'ERR_IB_NOT_FOUND',
                ['#IBLOCK#' => print_r($code, true)]
            )
        );
    }

    /**
     * Получает id инфоблока, бросает исключение если его не существует
     *
     * @throws HelperException
     */
    public function getIblockIdIfExists(array|int|string $code, string $typeId = ''): int
    {
        $item = $this->getIblockIfExists($code, $typeId);
        return (int)$item['ID'];
    }

    /**
     * Получает инфоблок по id, коду или фильтру
     */
    public function getIblock(array|int|string $code, string $typeId = ''): bool|array
    {
        if (is_array($code)) {
            $filter = $code;
        } elseif (is_numeric($code)) {
            $filter = ['ID' => $code];
        } else {
            $filter = ['=CODE' => $code];
        }

        if (!empty($typeId)) {
            $filter['=TYPE'] = $typeId;
        }

        $filter['CHECK_PERMISSIONS'] = 'N';

        $item = CIBlock::GetList(['SORT' => 'ASC'], $filter)->Fetch();

        return $item ? $this->prepareIblock($item) : false;
    }

    /**
     * Получает список сайтов для инфоблока
     */
    public function getIblockSites(int $iblockId): array
    {
        $dbres = CIBlock::GetSite($iblockId);
        return $this->fetchAll($dbres, false, 'LID');
    }

    /**
     * Получает id инфоблока
     */
    public function getIblockId(array|int|string $code, string $typeId = ''): int
    {
        $iblock = $this->getIblock($code, $typeId);
        return !empty($iblock['ID']) ? (int)$iblock['ID'] : 0;
    }

    /**
     * Получает список инфоблоков
     */
    public function getIblocks(array $filter = []): array
    {
        $filter['CHECK_PERMISSIONS'] = 'N';

        $dbres = CIBlock::GetList(['SORT' => 'ASC'], $filter);

        return array_map(
            fn($item) => $this->prepareIblock($item),
            $this->fetchAll($dbres)
        );
    }

    /**
     * Добавляет инфоблок если его не существует
     *
     * @throws HelperException
     */
    public function addIblockIfNotExists(array $fields = []): int
    {
        $this->checkRequiredKeys($fields, ['CODE', 'IBLOCK_TYPE_ID', 'LID']);

        $typeId = false;
        if (!empty($fields['IBLOCK_TYPE_ID'])) {
            $typeId = $fields['IBLOCK_TYPE_ID'];
        }

        $iblock = $this->getIblock($fields['CODE'], $typeId);
        if ($iblock) {
            return (int)$iblock['ID'];
        }

        return $this->addIblock($fields);
    }

    /**
     * Добавляет инфоблок
     *
     * @throws HelperException
     */
    public function addIblock(array $fields): int
    {
        $this->checkRequiredKeys($fields, ['CODE', 'IBLOCK_TYPE_ID', 'LID']);

        $default = [
            'ACTIVE'           => 'Y',
            'NAME'             => '',
            'CODE'             => '',
            'LIST_PAGE_URL'    => '',
            'DETAIL_PAGE_URL'  => '',
            'SECTION_PAGE_URL' => '',
            'IBLOCK_TYPE_ID'   => 'main',
            'LID'              => ['s1'],
            'SORT'             => 500,
            'GROUP_ID'         => ['2' => 'R'],
            'VERSION'          => 2,
            'BIZPROC'          => 'N',
            'WORKFLOW'         => 'N',
            'INDEX_ELEMENT'    => 'N',
            'INDEX_SECTION'    => 'N',
        ];

        $fields = array_replace_recursive($default, $fields);

        $ib = new CIBlock;
        $iblockId = $ib->Add($fields);

        if ($iblockId) {
            return (int)$iblockId;
        }

        throw new HelperException($ib->LAST_ERROR);
    }

    /**
     * Обновляет инфоблок
     *
     * @throws HelperException
     */
    public function updateIblock(int $iblockId, array $fields = []): int
    {
        $ib = new CIBlock;
        if ($ib->Update($iblockId, $fields)) {
            return $iblockId;
        }

        throw new HelperException($ib->LAST_ERROR);
    }

    /**
     * Обновляет инфоблок если он существует
     *
     * @throws HelperException
     */
    public function updateIblockIfExists(array|int|string $code, array $fields = []): bool|int
    {
        $iblock = $this->getIblock($code);
        if (!$iblock) {
            return false;
        }
        return $this->updateIblock($iblock['ID'], $fields);
    }

    /**
     * Удаляет инфоблок если он существует
     *
     * @throws HelperException
     */
    public function deleteIblockIfExists(array|int|string $code, string $typeId = ''): bool
    {
        $iblock = $this->getIblock($code, $typeId);
        if (!$iblock) {
            return false;
        }
        return $this->deleteIblock($iblock['ID']);
    }

    /**
     * Удаляет инфоблок
     *
     * @throws HelperException
     */
    public function deleteIblock(int $iblockId): bool
    {
        if (CIBlock::Delete($iblockId)) {
            return true;
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_CANT_DELETE_IBLOCK', [
                    '#NAME#' => $iblockId,
                ]
            )
        );
    }

    /**
     * Сохраняет инфоблок, создаст если не было, обновит если существует и отличается
     *
     * @throws HelperException
     */
    public function saveIblock(array $fields = []): int
    {
        $this->checkRequiredKeys($fields, ['CODE', 'IBLOCK_TYPE_ID', 'LID']);

        $item = $this->getIblock($fields['CODE'], $fields['IBLOCK_TYPE_ID']);

        $fields = $this->prepareExportIblock($fields);

        if (empty($item)) {
            $ok = $this->addIblock($fields);
            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'IB_CREATED',
                    [
                        '#NAME#' => $fields['CODE'],
                    ]
                )
            );
            return $ok;
        }

        $exists = $this->prepareExportIblock($item);
        if ($this->hasDiff($exists, $fields)) {
            $ok = $this->updateIblock($item['ID'], $fields);
            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'IB_UPDATED',
                    [
                        '#NAME#' => $fields['CODE'],
                    ]
                )
            );
            $this->outDiffIf($ok, $exists, $fields);
            return $ok;
        }

        return $item['ID'];
    }

    /**
     * Получает инфоблок
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @throws HelperException
     */
    public function exportIblock(int $iblockId): array
    {
        $item = $this->getIblock($iblockId);

        if (!empty($item['CODE'])) {
            return $this->prepareExportIblock($item);
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_IB_CODE_NOT_FOUND',
                ['#IBLOCK_ID#' => $iblockId]
            )
        );
    }

    /**
     * Получает список инфоблоков
     * Данные подготовлены для экспорта в миграцию или схему
     */
    public function exportIblocks(array $filter = []): array
    {
        $filter['!CODE'] = false;

        return array_map(
            fn($item) => $this->prepareExportIblock($item),
            $this->getIblocks($filter)
        );
    }

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

    /**
     * @throws HelperException
     */
    public function getIblockUid(int $iblockId): string
    {
        $iblock = $this->getIblockIfExists($iblockId);

        if (empty($iblock['CODE'])) {
            throw new HelperException(
                Locale::getMessage(
                    'ERR_IB_CODE_NOT_FOUND',
                    ['#IBLOCK_ID#' => $iblock['ID']]
                )
            );
        }

        return $iblock['IBLOCK_TYPE_ID'] . ':' . $iblock['CODE'];
    }

    /**
     * @throws HelperException
     */
    public function getIblockIdByUid(string $iblockUid): int
    {
        $iblockId = 0;

        if (empty($iblockUid)) {
            return $iblockId;
        }

        [$type, $code] = explode(':', $iblockUid);
        if (!empty($type) && !empty($code)) {
            $iblockId = $this->getIblockIdIfExists($code, $type);
        }

        return $iblockId;
    }

    protected function prepareIblock(array $item): array
    {
        $item['LID'] = $this->getIblockSites($item['ID']);

        $messages = CIBlock::GetMessages($item['ID']);

        $iblockTemlates = new IblockTemplates($item['ID']);

        $item['IPROPERTY_TEMPLATES'] = array_column(
            $iblockTemlates->findTemplates(),
            'TEMPLATE',
            'CODE'
        );

        return array_merge($item, $messages);
    }

    protected function prepareExportIblock(array $iblock): array
    {
        $this->unsetKeys($iblock, [
            'ID',
            'TIMESTAMP_X',
            'TMP_ID',
            'SERVER_NAME',
        ]);

        return $iblock;
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
