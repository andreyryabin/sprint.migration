<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class UserGroupBuilder extends VersionBuilder
{
    protected function isBuilderEnabled()
    {
        return true;
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_UserGroupExport1'));
        $this->setDescription(Locale::getMessage('BUILDER_UserGroupExport2'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Main'));

        $this->addVersionFields();
    }

    /**
     * @throws RebuildException
     * @throws HelperException
     * @throws MigrationException
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();

        $userGroups = $this->addFieldAndReturn(
            'user_group',
            [
                'title'       => Locale::getMessage('BUILDER_UserGroupExport_user_group'),
                'placeholder' => '',
                'multiple'    => 1,
                'value'       => [],
                'width'       => 250,
                'select'      => $this->getUserGroupsSelect(),
            ]
        );

        $items = [];
        foreach ($userGroups as $groupId) {
            if ($item = $helper->UserGroup()->exportGroup($groupId)) {
                $fields = $item;
                unset($fields['STRING_ID']);
                $items[] = [
                    'STRING_ID' => $item['STRING_ID'],
                    'FIELDS'    => $fields,
                ];
            }
        }

        if (empty($items)) {
            $this->rebuildField('user_group');
        }

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/UserGroupExport.php',
            [
                'items' => $items,
            ]
        );
    }

    protected function getUserGroupsSelect(): array
    {
        $helper = $this->getHelperManager();

        $items = array_filter($helper->UserGroup()->getGroups(), function ($item) {
            return !empty($item['STRING_ID']);
        });

        $items = array_map(function ($item) {
            $item['NAME'] = '[' . $item['STRING_ID'] . '] ' . $item['NAME'];
            return $item;
        }, $items);

        return $this->createSelect(
            $items,
            'ID',
            'NAME'
        );
    }
}
