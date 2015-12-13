<?php

namespace Sprint\Migration\Helpers;
use Sprint\Migration\Helper;

class AgentHelper
{

    public function replaceAgent($moduleName, $name, $interval, $nextExec){
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

        return ($agentId) ? $agentId : false;
    }
}