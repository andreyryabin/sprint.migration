<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Fields\CheckboxListField;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class UserOptionsBuilder extends VersionBuilder
{
    protected function isBuilderEnabled(): bool
    {
        return true;
    }

    protected function initialize(): void
    {
        $this->setTitle(Locale::getMessage('BUILDER_UserOptionsExport_Title'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Main'));

        $this->addVersionFields();
    }

    /**
     * @throws RebuildException
     * @throws HelperException
     * @throws MigrationException
     */
    protected function execute(): void
    {
        $helper = $this->getHelperManager();

        $what = $this->askField('what',
            (new CheckboxListField())
                ->setTitle(Locale::getMessage('BUILDER_UserOptionsExport_What'))
                ->addOption('userForm', Locale::getMessage('BUILDER_UserOptionsExport_WhatUserForm'))
                ->addOption('userList', Locale::getMessage('BUILDER_UserOptionsExport_WhatUserList'))
                ->addOption('groupList', Locale::getMessage('BUILDER_UserOptionsExport_WhatGroupList')),
        );

        $exportUserForm = [];
        $exportUserList = [];
        $exportUserGroupList = [];
        $exportUserGrid = [];
        $exportUserGroupGrid = [];

        if (in_array('userForm', $what)) {
            $exportUserForm = $helper->UserOptions()->exportUserForm();
        }
        if (in_array('userList', $what)) {
            $exportUserList = $helper->UserOptions()->exportUserList();
            $exportUserGrid = $helper->UserOptions()->exportGrid(
                $helper->UserOptions()->getUserGridId(),
            );
        }
        if (in_array('groupList', $what)) {
            $exportUserGroupList = $helper->UserOptions()->exportUserGroupList();
            $exportUserGroupGrid = $helper->UserOptions()->exportGrid(
                $helper->UserOptions()->getUserGroupGridId(),
            );
        }

        $this->confirmCreateVersion();

        $this->createVersionFile(
            Module::getModuleTemplateFile('UserOptionsExport'),
            [
                'exportUserForm'      => $exportUserForm,
                'exportUserList'      => $exportUserList,
                'exportUserGroupList' => $exportUserGroupList,
                'exportUserGrid'      => $exportUserGrid,
                'exportUserGroupGrid' => $exportUserGroupGrid,
            ],
        );
    }
}
