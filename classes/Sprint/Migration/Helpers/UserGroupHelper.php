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
            $res[] = $this->getGroup($aItem['ID']);
        }

        return $res;
    }


    public function getGroupId($code) {
        return \CGroup::GetIDByCode($code);
    }

    public function getGroup($code) {
        $groupId = is_numeric($code) ? $code : $this->getGroupId($code);

        if (empty($groupId)) {
            return false;
        }

        /* extract SECURITY_POLICY */
        $item = \CGroup::GetByID($groupId)->Fetch();
        if (empty($item)) {
            return false;
        }

        if (!empty($item['SECURITY_POLICY'])) {
            $item['SECURITY_POLICY'] = unserialize($item['SECURITY_POLICY']);
        }

        return $item;

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
        $groupId = $group->Add($this->prepareFields($fields));

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
        if ($group->Update($groupId, $this->prepareFields($fields))) {
            return intval($groupId);
        }

        $this->throwException(__METHOD__, $group->LAST_ERROR);
    }

    protected function prepareFields($fields) {
        if (!empty($fields['SECURITY_POLICY']) && is_array($fields['SECURITY_POLICY'])) {
            $fields['SECURITY_POLICY'] = serialize($fields['SECURITY_POLICY']);
        }

        return $fields;
    }
}