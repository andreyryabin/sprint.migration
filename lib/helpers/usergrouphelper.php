<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class UserGroupHelper extends Helper
{


    public function getGroups($filter = array()) {
        $by = 'c_sort';
        $order = 'asc';

        $res = array();

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbres = \CGroup::GetList($by, $order, $filter);
        while ($item = $dbres->Fetch()) {
            $res[] = $this->getGroup($item['ID']);
        }

        return $res;

    }

    public function exportGroups($filter = array()) {
        $items = $this->getGroups($filter);
        $exportItems = array();
        foreach ($items as $item) {
            $exportItems[] = $this->prepareExportGroup($item);
        }
        return $exportItems;
    }

    protected function prepareExportGroup($item) {
        if (empty($item)) {
            return $item;
        }

        unset($item['ID']);
        unset($item['TIMESTAMP_X']);

        return $item;
    }

    public function getGroupCode($id) {
        $group = $this->getGroup($id);
        return ($group) ? $group['STRING_ID'] : false;
    }

    public function getGroupId($code) {
        $group = $this->getGroup($code);
        return ($group) ? $group['ID'] : false;
    }

    public function getGroup($code) {
        $groupId = is_numeric($code) ? $code : \CGroup::GetIDByCode($code);

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

        if ($item['ID'] == 1) {
            $item['STRING_ID'] = 'administrators';
        } elseif ($item['ID'] == 2) {
            $item['STRING_ID'] = 'everyone';
        }

        return $item;

    }

    public function exportGroup($code) {
        return $this->prepareExportGroup(
            $this->getGroup($code)
        );
    }

    public function saveGroup($code, $fields = array()) {
        $fields['STRING_ID'] = $code;

        $this->checkRequiredKeys(__METHOD__, $fields, array('STRING_ID', 'NAME'));

        $exists = $this->getGroup($fields['STRING_ID']);
        $exportExists = $this->prepareExportGroup($exists);
        $fields = $this->prepareExportGroup($fields);

        if (empty($exists)) {
            $ok = ($this->testMode) ? true : $this->addGroup($fields['STRING_ID'], $fields);
            $this->outNoticeIf($ok, 'Группа %s: добавлена', $fields['NAME']);
            return $ok;
        }

        if ($exportExists != $fields) {
            $ok = ($this->testMode) ? true : $this->updateGroup($exists['ID'], $fields);
            $this->outNoticeIf($ok, 'Группа %s: обновлена', $fields['NAME']);
            return $ok;
        }


        $ok = ($this->testMode) ? true : $exists['ID'];
        //$this->outIf($ok, 'Группа %s: совпадает', $fields['NAME']);
        return $ok;
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

    public function addGroup($code, $fields = array()) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('NAME'));

        $fields['STRING_ID'] = $code;

        $group = new \CGroup;
        $groupId = $group->Add($this->prepareFields($fields));

        if ($groupId) {
            return intval($groupId);
        }

        $this->throwException(__METHOD__, $group->LAST_ERROR);
    }

    public function updateGroup($groupId, $fields = array()) {
        if (empty($fields)) {
            $this->throwException(__METHOD__, 'Set fields for group');
        }

        $group = new \CGroup;
        if ($group->Update($groupId, $this->prepareFields($fields))) {
            return intval($groupId);
        }

        $this->throwException(__METHOD__, $group->LAST_ERROR);
    }

    public function deleteGroup($code) {
        $groupId = $this->getGroupId($code);
        if (empty($groupId)) {
            return false;
        }

        $group = new \CGroup;
        $group->Delete($groupId);
        return true;
    }

    protected function prepareFields($fields) {
        if (!empty($fields['SECURITY_POLICY']) && is_array($fields['SECURITY_POLICY'])) {
            $fields['SECURITY_POLICY'] = serialize($fields['SECURITY_POLICY']);
        }

        return $fields;
    }

    /** @deprecated */
    public function getGroupsByFilter($filter = array()) {
        return $this->getGroups($filter);
    }
}