<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;
use Sprint\Migration\HelperManager;
use Sprint\Migration\Exceptions\HelperException;

class UserGroupExport extends VersionBuilder
{

    protected function isBuilderEnabled() {
        return true;
    }

    protected function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_UserGroupExport1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_UserGroupExport2'));

        $this->addField('prefix', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_PREFIX'),
            'value' => $this->getVersionConfig()->getVal('version_prefix'),
            'width' => 250,
        ));

        $this->addField('description', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_DESCR'),
            'width' => 350,
            'height' => 40,
        ));
    }


    protected function execute() {
        $helper = HelperManager::getInstance();

        $this->addField('user_group', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_UserGroupExport_user_group'),
            'placeholder' => '',
            'multiple' => 1,
            'value' => array(),
            'width' => 250,
            'select' => $this->getUserGroups()
        ));

        $userGroups = $this->getFieldValue('user_group');
        if (empty($userGroups)) {
            $this->rebuildField('user_group');
        }

        $userGroups = is_array($userGroups) ? $userGroups : array($userGroups);

        $items = array();
        foreach ($userGroups as $groupId) {
            if ($item = $helper->UserGroup()->exportGroup($groupId)) {
                $fields = $item;
                unset($fields['STRING_ID']);
                $items[] = array(
                    'STRING_ID' => $item['STRING_ID'],
                    'FIELDS' => $fields
                );
            }
        }

        if (empty($items)) {
            $this->rebuildField('user_group');
        }

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/UserGroupExport.php', array(
            'items' => $items,
        ));

    }

    protected function getUserGroups() {
        $helper = HelperManager::getInstance();

        $groups = $helper->UserGroup()->getGroups();

        $result = [];
        foreach ($groups as $group) {
            if (!empty($group['STRING_ID'])) {
                $result[] = [
                    'title' => '[' . $group['STRING_ID'] . '] ' . $group['NAME'],
                    'value' => $group['ID']
                ];
            }
        }

        return $result;

    }
}