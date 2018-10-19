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

    public function getAgent($filter = array()) {
        return \CAgent::GetList(array(
            "MODULE_ID" => "ASC"
        ), $filter)->Fetch();
    }

    public function deleteAgentIfExists($moduleName, $name) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $aAgent = \CAgent::GetList(array("ID" => "DESC"), array(
            'MODULE_ID' => $moduleName,
            'NAME' => $name
        ))->Fetch();

        if (!$aAgent) {
            return false;
        }

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        \CAgent::RemoveAgent($name, $moduleName);
        return true;
    }

    /** @deprecated */
    public function replaceAgent($moduleName, $name, $interval, $nextExec) {
        return $this->saveAgent(array(
            'MODULE_ID' => $moduleName,
            'NAME' => $name,
            'AGENT_INTERVAL' => $interval,
            'NEXT_EXEC' => $nextExec,
        ));
    }

    /** @deprecated */
    public function addAgentIfNotExists($moduleName, $name, $interval, $nextExec) {
        return $this->saveAgent(array(
            'MODULE_ID' => $moduleName,
            'NAME' => $name,
            'AGENT_INTERVAL' => $interval,
            'NEXT_EXEC' => $nextExec,
        ));
    }

    //version 2

    public function saveAgent($fields = array()) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('MODULE_ID', 'NAME'));

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */

        $aAgent = \CAgent::GetList(array("ID" => "DESC"), array(
            'MODULE_ID' => $fields['MODULE_ID'],
            'NAME' => $fields['NAME']
        ))->Fetch();

        if ($aAgent) {
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