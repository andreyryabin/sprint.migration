<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\HelperManager;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class UserOptionsExport extends VersionBuilder
{

    protected function isBuilderEnabled()
    {
        return true;
    }

    protected function initialize()
    {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_UserOptionsExport_Title'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_UserOptionsExport_Desc'));

        $this->addVersionFields();
    }


    protected function execute()
    {
        $helper = HelperManager::getInstance();

        $this->addField('what', [
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_UserOptionsExport_What'),
            'width' => 250,
            'multiple' => 1,
            'value' => [],
            'select' => [
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_UserOptionsExport_WhatUserForm'),
                    'value' => 'userForm',
                ],
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_UserOptionsExport_WhatUserList'),
                    'value' => 'userList',
                ],
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_UserOptionsExport_WhatGroupList'),
                    'value' => 'groupList',
                ],
            ],
        ]);


        $what = $this->getFieldValue('what');
        if (!empty($what)) {
            $what = is_array($what) ? $what : [$what];
        } else {
            $this->rebuildField('what');
        }


        $exportUserForm = [];
        $exportUserList = [];
        $exportUserGroupList = [];

        if (in_array('userForm', $what)) {
            $exportUserForm = $helper->UserOptions()->exportUserForm();
        }
        if (in_array('userList', $what)) {
            $exportUserList = $helper->UserOptions()->exportUserList();
        }
        if (in_array('groupList', $what)) {
            $exportUserGroupList = $helper->UserOptions()->exportUserGroupList();
        }

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/UserOptionsExport.php', [
            'exportUserForm' => $exportUserForm,
            'exportUserList' => $exportUserList,
            'exportUserGroupList' => $exportUserGroupList,
        ]);

    }
}