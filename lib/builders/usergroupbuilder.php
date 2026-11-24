<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Fields\CheckboxListField;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class UserGroupBuilder extends VersionBuilder
{
    protected function isBuilderEnabled(): bool
    {
        return true;
    }

    protected function initialize(): void
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
    protected function execute(): void
    {
        $helper = $this->getHelperManager();

        $userGroups = $this->askField('user_group',
            (new CheckboxListField(Locale::getMessage('BUILDER_UserGroupExport_user_group')))
                ->setOptions($this->getUserGroupsSelect(), 'ID', 'NAME'),
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
            $this->resetField('user_group');
        }

        $this->confirmCreateVersion();

        $this->createVersionFile(
            Module::getModuleTemplateFile('UserGroupExport'),
            [
                'items' => $items,
            ],
        );
    }

    protected function getUserGroupsSelect(): array
    {
        $helper = $this->getHelperManager();

        $items = array_filter($helper->UserGroup()->getGroups(), function ($item) {
            return !empty($item['STRING_ID']);
        });

        return array_map(function ($item) {
            $item['NAME'] = '[' . $item['STRING_ID'] . '] ' . $item['NAME'];
            return $item;
        }, $items);
    }
}
