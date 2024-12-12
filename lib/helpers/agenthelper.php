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
        return array_map(function ($agent) {
            return $this->prepareExportAgent($agent);
        }, $this->getList($filter));
    }

    /**
     * Получает агента
     * Данные подготовлены для экспорта в миграцию или схему
     */
    public function exportAgent(string $moduleId, string $name)
    {
        return $this->prepareExportAgent(
            $this->getAgent($moduleId, $name)
        );
    }

    public function exportAgentById(int $agentId)
    {
        return $this->prepareExportAgent(
            $this->getAgentById($agentId)
        );
    }

    /**
     * Получает агента
     */
    public function getAgent(string $moduleId, string $name)
    {
        return CAgent::GetList(
            ['MODULE_ID' => 'ASC'],
            ['MODULE_ID' => $moduleId, 'NAME' => $name],
        )->Fetch();
    }

    public function getAgentById(int $agentId)
    {
        return CAgent::GetById($agentId)->Fetch();
    }

    /**
     * Удаляет агента
     *
     * @param $moduleId
     * @param $name
     *
     * @return bool
     */
    public function deleteAgent($moduleId, $name)
    {
        CAgent::RemoveAgent($name, $moduleId);
        return true;
    }

    /**
     * Удаляет агента если существует
     *
     * @param $moduleId
     * @param $name
     *
     * @return bool
     */
    public function deleteAgentIfExists($moduleId, $name)
    {
        $item = $this->getAgent($moduleId, $name);
        if (empty($item)) {
            return false;
        }

        return $this->deleteAgent($moduleId, $name);
    }

    /**
     * Сохраняет агента
     * Создаст если не было, обновит если существует и отличается
     *
     * @param array $fields
     *
     * @throws HelperException
     * @return bool|mixed
     */
    public function saveAgent(array $fields = [])
    {
        $this->checkRequiredKeys($fields, ['NAME']);

        $exists = $this->getAgent($fields['MODULE_ID'] ?? '', $fields['NAME']);

        $fields = $this->prepareExportAgent($fields);

        if (empty($exists)) {
            $ok = $this->getMode('test') ? true : $this->addAgent($fields);
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
            $ok = $this->getMode('test') ? true : $this->updateAgent($fields);

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

        return $this->getMode('test') ? true : $exists['ID'];
    }

    /**
     * Обновление агента, бросает исключение в случае неудачи
     *
     * @throws HelperException
     */
    public function updateAgent(array $fields)
    {
        $this->checkRequiredKeys($fields, ['NAME']);

        $this->deleteAgent($fields['MODULE_ID'] ?? '', $fields['NAME']);

        return $this->addAgent($fields);
    }

    /**
     * Создание агента, бросает исключение в случае неудачи
     *
     * @throws HelperException
     * @return bool|int
     */
    public function addAgent(array $fields)
    {
        $this->checkRequiredKeys($fields, ['NAME']);

        global $DB;

        $fields = array_merge([
            'AGENT_INTERVAL' => 86400,
            'ACTIVE'         => 'Y',
            'IS_PERIOD'      => 'N',
            'NEXT_EXEC'      => $DB->GetNowDate(),
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
            return $agentId;
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

    /**
     * @param $moduleId
     * @param $name
     * @param $interval
     * @param $nextExec
     *
     * @throws HelperException
     * @return bool|mixed
     * @deprecated
     */
    public function replaceAgent($moduleId, $name, $interval, $nextExec)
    {
        return $this->saveAgent([
            'MODULE_ID'      => $moduleId,
            'NAME'           => $name,
            'AGENT_INTERVAL' => $interval,
            'NEXT_EXEC'      => $nextExec,
        ]);
    }

    /**
     * @param $moduleId
     * @param $name
     * @param $interval
     * @param $nextExec
     *
     * @throws HelperException
     * @return bool|mixed
     * @deprecated
     */
    public function addAgentIfNotExists($moduleId, $name, $interval, $nextExec)
    {
        return $this->saveAgent([
            'MODULE_ID'      => $moduleId,
            'NAME'           => $name,
            'AGENT_INTERVAL' => $interval,
            'NEXT_EXEC'      => $nextExec,
        ]);
    }

    protected function prepareExportAgent($item)
    {
        if (empty($item)) {
            return $item;
        }

        unset($item['ID']);
        unset($item['LOGIN']);
        unset($item['USER_NAME']);
        unset($item['LAST_NAME']);
        unset($item['RUNNING']);
        unset($item['DATE_CHECK']);
        unset($item['LAST_EXEC']);

        return $item;
    }
}
