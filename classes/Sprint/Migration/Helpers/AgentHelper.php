<?php

namespace Sprint\Migration\Helpers;
use Sprint\Migration\Helper;

class AgentHelper extends Helper
{

    public function replaceAgent($moduleName, $name, $interval, $nextExec){

        /* @global $APPLICATION \CMain */
        global $APPLICATION;

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */

        $aAgent = \CAgent::GetList(array("ID" => "DESC"), array(
            'MODULE_ID' => $moduleName,
            'NAME' => $name
        ))->Fetch();

        if ($aAgent){
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            \CAgent::RemoveAgent($name, $moduleName);
        }

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $agentId = \CAgent::AddAgent($name, $moduleName, 'N', $interval, '', 'Y', $nextExec);

        if ($agentId){
            return $agentId;
        }

        if ($APPLICATION->GetException()) {
            $this->throwException(__METHOD__, $APPLICATION->GetException()->GetString());
        } else {
            $this->throwException(__METHOD__, 'Agent %s not added', $name);
        }

    }

    public function addAgentIfNotExists($moduleName, $name, $interval, $nextExec){
        /* @global $APPLICATION \CMain */
        global $APPLICATION;

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $aAgent = \CAgent::GetList(array("ID" => "DESC"), array(
            'MODULE_ID' => $moduleName,
            'NAME' => $name
        ))->Fetch();

        if ($aAgent){
            return $aAgent['ID'];
        }

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $agentId = \CAgent::AddAgent($name, $moduleName, 'N', $interval, '', 'Y', $nextExec);

        if ($agentId){
            return $agentId;
        }

        if ($APPLICATION->GetException()) {
            $this->throwException(__METHOD__, $APPLICATION->GetException()->GetString());
        } else {
            $this->throwException(__METHOD__, 'Agent %s not added', $name);
        }
    }

    public function deleteAgentIfExists($moduleName, $name){
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $aAgent = \CAgent::GetList(array("ID" => "DESC"), array(
            'MODULE_ID' => $moduleName,
            'NAME' => $name
        ))->Fetch();

        if (!$aAgent){
            return false;
        }

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        \CAgent::RemoveAgent($name, $moduleName);
        return true;
    }
}