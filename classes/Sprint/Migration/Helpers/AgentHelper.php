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

            unset($agent['ID']);
            unset($agent['LOGIN']);
            unset($agent['USER_NAME']);
            unset($agent['LAST_NAME']);
            unset($agent['RUNNING']);
            unset($agent['DATE_CHECK']);

            $exportAgents[] = $agent;
        }

        return $exportAgents;
    }

    public function exportAgent($filter = array()) {
        $agent = $this->getAgent($filter);

        if (empty($agent)) {
            return false;
        }

        unset($agent['ID']);
        unset($agent['LOGIN']);
        unset($agent['USER_NAME']);
        unset($agent['LAST_NAME']);
        unset($agent['RUNNING']);
        unset($agent['DATE_CHECK']);

        return $agent;
    }

    public function getAgent($filter = array()) {
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
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $item = \CAgent::GetList(array("ID" => "DESC"), array(
            'MODULE_ID' => $moduleId,
            'NAME' => $name
        ))->Fetch();

        if (!$item) {
            return false;
        }

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        \CAgent::RemoveAgent($name, $moduleId);
        return true;
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

    //version 2

    public function saveAgent($fields = array()) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('MODULE_ID', 'NAME'));

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */

        $item = \CAgent::GetList(array("ID" => "DESC"), array(
            'MODULE_ID' => $fields['MODULE_ID'],
            'NAME' => $fields['NAME']
        ))->Fetch();

        if ($item) {
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            \CAgent::RemoveAgent($fields['NAME'], $fields['MODULE_ID']);
        }

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
}