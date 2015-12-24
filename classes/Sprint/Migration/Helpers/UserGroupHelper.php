<?php

namespace Sprint\Migration\Helpers;
use Sprint\Migration\Helper;

class UserGroupHelper extends Helper
{


    public function getGroupsByFilter($filter = array()){
        $by = 'c_sort';
        $order = 'asc';

        $res = array();

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbRes = \CGroup::GetList($by, $order, $filter);
        while ($aItem = $dbRes->Fetch()){
            $res[] = $aItem;
        }

        return $res;
    }

    public function addGroupIfNotExists($code, $fields = array()){
        $by = 'c_sort';
        $order = 'asc';
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $aItem = \CGroup::GetList($by, $order, array('STRING_ID' => $code))->Fetch();
        if ($aItem){
            return $aItem['ID'];
        }

        $fields['STRING_ID'] = $code;

        if (empty($fields['NAME'])){
            $this->throwException(__METHOD__, 'Set name for group %s', $code);
        }

        $group = new \CGroup;
        $id = $group->Add($fields);

        if ($id){
            return $id;
        }

        $this->throwException(__METHOD__, $group->LAST_ERROR);

    }

    public function updateGroupIfExists($code, $fields = array()){
        $by = 'c_sort';
        $order = 'asc';
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $aItem = \CGroup::GetList($by, $order, array('STRING_ID' => $code))->Fetch();
        if (!$aItem){
            return false;
        }

        $default = array(
            'STRING_ID' => $code,
            'NAME' => $aItem['NAME']
        );

        $fields = array_merge($default, $fields);

        if (empty($fields['NAME'])){
            $this->throwException(__METHOD__, 'Set name for group %s', $code);
        }

        $group = new \CGroup;

        if ($group->Update($aItem['ID'], $fields)){
            return $aItem['ID'];
        }

        $this->throwException(__METHOD__, $group->LAST_ERROR);

    }

}