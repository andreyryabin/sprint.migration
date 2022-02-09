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
     *
     * @param array $filter
     *
     * @return array
     */
    public function getList($filter = [])
    {
        $res = [];
        $dbres = CAgent::GetList(["MODULE_ID" => "ASC"], $filter);
        while ($item = $dbres->Fetch()) {
            $res[] = $item;
        }
        return $res;
    }

    /**
     * Получает список агентов по фильтру
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @param array $filter
     *
     * @return array
     */
    public function exportAgents($filter = [])
    {
        $agents = $this->getList($filter);

        $exportAgents = [];
        foreach ($agents as $agent) {
            $exportAgents[] = $this->prepareExportAgent($agent);
        }

        return $exportAgents;
    }

    /**
     * Получает агента
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @param        $moduleId
     * @param string $name
     *
     * @return bool
     */
    public function exportAgent($moduleId, $name = '')
    {
        $agent = $this->getAgent($moduleId, $name);
        if (empty($agent)) {
            return false;
        }

        return $this->prepareExportAgent($agent);
    }

    /**
     * Получает агента
     *
     * @param        $moduleId
     * @param string $name
     *
     * @return array
     */
    public function getAgent($moduleId, $name = '')
    {
        $filter = is_array($moduleId)
            ? $moduleId
            : [
                'MODULE_ID' => $moduleId,
            ];

        if (!empty($name)) {
            $filter['NAME'] = $name;
        }

        return CAgent::GetList([
            "MODULE_ID" => "ASC",
        ], $filter)->Fetch();
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
    public function saveAgent($fields = [])
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['MODULE_ID', 'NAME']);

        $exists = $this->getAgent([
            'MODULE_ID' => $fields['MODULE_ID'],
            'NAME'      => $fields['NAME'],
        ]);

        $exportExists = $this->prepareExportAgent($exists);
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

        $ok = $this->getMode('test') ? true : $exists['ID'];
        if ($this->getMode('out_equal')) {
            $this->outIf(
                $ok,
                Locale::getMessage(
                    'AGENT_EQUAL',
                    [
                        '#NAME#' => $fields['NAME'],
                    ]
                )
            );
        }
        return $ok;
    }

    /**
     * Обновление агента, бросает исключение в случае неудачи
     *
     * @param $fields
     *
     * @throws HelperException
     * @return bool
     */
    public function updateAgent($fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['MODULE_ID', 'NAME']);
        $this->deleteAgent($fields['MODULE_ID'], $fields['NAME']);
        return $this->addAgent($fields);
    }

    /**
     * Создание агента, бросает исключение в случае неудачи
     *
     * @param $fields
     *
     * @throws HelperException
     * @return bool|int
     */
    public function addAgent($fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['MODULE_ID', 'NAME']);

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
            $fields['MODULE_ID'],
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

        $this->throwApplicationExceptionIfExists(__METHOD__);
        $this->throwException(
            __METHOD__,
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
