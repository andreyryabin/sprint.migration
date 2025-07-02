<?php

namespace Sprint\Migration\Helpers;

use CAgent;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;

class AgentHelper extends Helper
{
    /**
     * Получает список агентов по фильтру
     */
    public function getList(array $filter = []): array
    {
        $dbres = CAgent::GetList(["MODULE_ID" => "ASC"], $filter);
        return $this->fetchAll($dbres);
    }

    /**
     * Получает список агентов по фильтру
     * Данные подготовлены для экспорта в миграцию или схему
     */
    public function exportAgents(array $filter = []): array
    {
        return array_map(function ($item) {
            return $this->prepareExportAgent($item);
        }, $this->getList($filter));
    }

    /**
     * Получает агента
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @throws HelperException
     */
    public function exportAgent(string $moduleId, string $name): array
    {
        $item = $this->getAgent($moduleId, $name);

        if (!empty($item)) {
            return $this->prepareExportAgent($item);
        }

        throw new HelperException("Agent with NAME=$name and MODULE_ID=$moduleId not found");
    }

    /**
     * @throws HelperException
     */
    public function exportAgentById(int $agentId): array
    {
        $item = $this->getAgentById($agentId);

        if (!empty($item)) {
            return $this->prepareExportAgent($item);
        }

        throw new HelperException("Agent with ID=$agentId not found");
    }

    /**
     * Получает агента
     */
    public function getAgent(string $moduleId, string $name): bool|array
    {
        return CAgent::GetList(
            ['MODULE_ID' => 'ASC'],
            ['MODULE_ID' => $moduleId, 'NAME' => $name],
        )->Fetch();
    }

    public function getAgentById(int $agentId): bool|array
    {
        return CAgent::GetById($agentId)->Fetch();
    }

    /**
     * Удаляет агента
     */
    public function deleteAgent(string $moduleId, string $name): bool
    {
        CAgent::RemoveAgent($name, $moduleId);
        return true;
    }

    /**
     * Удаляет агента если существует
     */
    public function deleteAgentIfExists(string $moduleId, string $name): bool
    {
        $item = $this->getAgent($moduleId, $name);
        if (empty($item)) {
            return false;
        }

        return $this->deleteAgent($moduleId, $name);
    }

    /**
     * Сохраняет агента, создаст если не было, обновит если существует и отличается
     *
     * @throws HelperException
     */
    public function saveAgent(array $fields = []): int
    {
        $this->checkRequiredKeys($fields, ['NAME']);

        $exists = $this->getAgent($fields['MODULE_ID'] ?? '', $fields['NAME']);

        $fields = $this->prepareExportAgent($fields);

        if (empty($exists)) {
            $ok = $this->addAgent($fields);
            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'AGENT_CREATED',
                    [
                        '#NAME#' => $fields['NAME'],
                    ]
                )
            );
            return $ok;
        }

        $exportExists = $this->prepareExportAgent($exists);

        if (strtotime($fields['NEXT_EXEC']) <= strtotime($exportExists['NEXT_EXEC'])) {
            unset($fields['NEXT_EXEC']);
            unset($exportExists['NEXT_EXEC']);
        }

        if ($this->hasDiff($exportExists, $fields)) {
            $ok = $this->updateAgent($fields);

            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'AGENT_UPDATED',
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
     * Обновление агента, бросает исключение в случае неудачи
     *
     * @throws HelperException
     */
    public function updateAgent(array $fields): int
    {
        $this->checkRequiredKeys($fields, ['NAME']);

        $this->deleteAgent($fields['MODULE_ID'] ?? '', $fields['NAME']);

        return $this->addAgent($fields);
    }

    /**
     * Создание агента, бросает исключение в случае неудачи
     *
     * @throws HelperException
     */
    public function addAgent(array $fields): int
    {
        $this->checkRequiredKeys($fields, ['NAME']);

        $fields = array_merge([
            'AGENT_INTERVAL' => 86400,
            'ACTIVE'         => 'Y',
            'IS_PERIOD'      => 'N',
            'SORT'           => 100,
        ], $fields);

        $agentId = CAgent::AddAgent(
            $fields['NAME'],
            $fields['MODULE_ID'] ?? '',
            $fields['IS_PERIOD'],
            $fields['AGENT_INTERVAL'],
            '',
            $fields['ACTIVE'],
            $fields['NEXT_EXEC'],
            $fields['SORT']
        );

        if ($agentId) {
            return (int)$agentId;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException(
            Locale::getMessage(
                'ERR_AGENT_NOT_ADDED',
                [
                    '#NAME#' => $fields['NAME'],
                ]
            )
        );
    }

    protected function prepareExportAgent(array $item): array
    {
        $this->unsetKeys($item, [
            'ID',
            'LOGIN',
            'USER_NAME',
            'LAST_NAME',
            'RUNNING',
            'DATE_CHECK',
            'LAST_EXEC',
        ]);

        return $item;
    }
}
