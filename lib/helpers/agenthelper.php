<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class AgentHelper extends Helper
{

    public function getList($filter = array()) {
        $res = array();
        $dbres = \CAgent::GetList(array("MODULE_ID" => "ASC"), $filter);
        while ($item = $dbres->Fetch()) {
            $res[] = $item;
        }
        return $res;
    }

    public function exportAgents($filter = array()) {
        $agents = $this->getList($filter);

        $exportAgents = array();
        foreach ($agents as $agent) {
            $exportAgents[] = $this->prepareExportAgent($agent);
        }

        return $exportAgents;
    }

    protected function prepareExportAgent($item) {
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

    public function exportAgent($moduleId, $name = '') {
        $agent = $this->getAgent($moduleId, $name);
        if (empty($agent)) {
            return false;
        }

        return $this->prepareExportAgent($agent);
    }

    public function getAgent($moduleId, $name = '') {
        $filter = is_array($moduleId) ? $moduleId : array(
            'MODULE_ID' => $moduleId
        );

        if (!empty($name)) {
            $filter['NAME'] = $name;
        }

        return \CAgent::GetList(array(
            "MODULE_ID" => "ASC"
        ), $filter)->Fetch();
    }

    public function deleteAgent($moduleId, $name) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        \CAgent::RemoveAgent($name, $moduleId);
        return true;
    }

    public function deleteAgentIfExists($moduleId, $name) {
        $item = $this->getAgent($moduleId, $name);
        if (empty($item)) {
            return false;
        }

        return $this->deleteAgent($moduleId, $name);
    }

    //version 2

    public function saveAgent($fields = array()) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('MODULE_ID', 'NAME'));

        $exists = $this->getAgent(array(
            'MODULE_ID' => $fields['MODULE_ID'],
            'NAME' => $fields['NAME']
        ));

        $exportExists = $this->prepareExportAgent($exists);
        $fields = $this->prepareExportAgent($fields);

        if (empty($exists)) {
            $ok = ($this->testMode) ? true : $this->addAgent($fields);
            $this->outNoticeIf($ok, 'Агент %s: добавлен', $fields['NAME']);
            return $ok;
        }

        if (strtotime($fields['NEXT_EXEC']) <= strtotime($exportExists['NEXT_EXEC'])){
            unset($fields['NEXT_EXEC']);
            unset($exportExists['NEXT_EXEC']);
        }

        if ($exportExists != $fields) {
            $ok = ($this->testMode) ? true : $this->updateAgent($fields);
            $this->outNoticeIf($ok, 'Агент %s: обновлен', $fields['NAME']);
            return $ok;
        }


        $ok = ($this->testMode) ? true : $exists['ID'];
        $this->outIf($ok, 'Агент %s: совпадает', $fields['NAME']);
        return $ok;
    }


    public function updateAgent($fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('MODULE_ID', 'NAME'));
        $this->deleteAgent($fields['MODULE_ID'], $fields['NAME']);
        return $this->addAgent($fields);
    }

    public function addAgent($fields) {

        $this->checkRequiredKeys(__METHOD__, $fields, array('MODULE_ID', 'NAME'));

        global $DB;

        $fields = array_merge(array(
            'AGENT_INTERVAL' => 86400,
            'ACTIVE' => 'Y',
            'IS_PERIOD' => 'N',
            'NEXT_EXEC' => $DB->GetNowDate(),
        ), $fields);

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $agentId = \CAgent::AddAgent(
            $fields['NAME'],
            $fields['MODULE_ID'],
            $fields['IS_PERIOD'],
            $fields['AGENT_INTERVAL'],
            '',
            $fields['ACTIVE'],
            $fields['NEXT_EXEC']
        );

        if ($agentId) {
            return $agentId;
        }

        /* @global $APPLICATION \CMain */
        global $APPLICATION;
        if ($APPLICATION->GetException()) {
            $this->throwException(__METHOD__, $APPLICATION->GetException()->GetString());
        } else {
            $this->throwException(__METHOD__, 'Agent %s not added', $fields['NAME']);
        }
    }

    /** @deprecated */
    public function replaceAgent($moduleId, $name, $interval, $nextExec) {
        return $this->saveAgent(array(
            'MODULE_ID' => $moduleId,
            'NAME' => $name,
            'AGENT_INTERVAL' => $interval,
            'NEXT_EXEC' => $nextExec,
        ));
    }

    /** @deprecated */
    public function addAgentIfNotExists($moduleId, $name, $interval, $nextExec) {
        return $this->saveAgent(array(
            'MODULE_ID' => $moduleId,
            'NAME' => $name,
            'AGENT_INTERVAL' => $interval,
            'NEXT_EXEC' => $nextExec,
        ));
    }
}