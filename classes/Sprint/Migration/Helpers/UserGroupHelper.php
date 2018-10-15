<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class UserGroupHelper extends Helper
{


    public function getGroupsByFilter($filter = array()) {
        $by = 'c_sort';
        $order = 'asc';

        $res = array();

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbRes = \CGroup::GetList($by, $order, $filter);
        while ($aItem = $dbRes->Fetch()) {
            $res[] = $aItem;
        }

        return $res;
    }


    public function getGroupId($code) {
        return \CGroup::GetIDByCode($code);
    }

    public function getGroup($code) {
        $groupId = is_numeric($code) ? $code : $this->getGroupId($code);

        return ($groupId) ? \CGroup::GetByID($groupId)->Fetch() : false;
    }

    public function saveGroup($code, $fields = array()) {
        $groupId = $this->getGroupId($code);
        if ($groupId) {
            return $this->updateGroup($groupId, $fields);
        } else {
            return $this->addGroup($code, $fields);
        }
    }

    public function addGroupIfNotExists($code, $fields = array()) {
        $groupId = $this->getGroupId($code);
        if ($groupId) {
            return intval($groupId);
        }

        return $this->addGroup($code, $fields);
    }

    public function updateGroupIfExists($code, $fields = array()) {
        $groupId = $this->getGroupId($code);
        if (!$groupId) {
            return false;
        }

        return $this->updateGroup($groupId, $fields);
    }

    protected function addGroup($code, $fields = array()) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('NAME'));

        $fields['STRING_ID'] = $code;

        $group = new \CGroup;
        $groupId = $group->Add($fields);

        if ($groupId) {
            return intval($groupId);
        }

        $this->throwException(__METHOD__, $group->LAST_ERROR);
    }

    protected function updateGroup($groupId, $fields = array()) {
        if (empty($fields)) {
            $this->throwException(__METHOD__, 'Set fields for group');
        }

        $group = new \CGroup;
        if ($group->Update($groupId, $fields)) {
            return intval($groupId);
        }

        $this->throwException(__METHOD__, $group->LAST_ERROR);
    }
}