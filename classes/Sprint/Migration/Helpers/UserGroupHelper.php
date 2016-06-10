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


    public function getGroupId($code){
        return \CGroup::GetIDByCode($code);
    }

    public function getGroup($code){
        $groupId = $this->getGroupId($code);
        return ($groupId) ? \CGroup::GetByID($groupId)->Fetch() : false;
    }

    
        
    public function addGroupIfNotExists($code, $fields = array()){
        $this->checkRequiredKeys(__METHOD__, $fields, array('NAME'));

        $groupId = $this->getGroupId($code);
        if ($groupId){
            return intval($groupId);
        }

        $fields['STRING_ID'] = $code;

        $group = new \CGroup;
        $groupId = $group->Add($fields);

        if ($groupId){
            return intval($groupId);
        }

        $this->throwException(__METHOD__, $group->LAST_ERROR);

    }

    public function updateGroupIfExists($code, $fields = array()){
        $groupId = $this->getGroupId($code);
        if (!$groupId){
            return false;
        }

        if (empty($fields)){
            $this->throwException(__METHOD__, 'Set fields for group %s', $code);
        }

        $group = new \CGroup;

        if ($group->Update($groupId, $fields)){
            return intval($groupId);
        }

        $this->throwException(__METHOD__, $group->LAST_ERROR);

    }

}