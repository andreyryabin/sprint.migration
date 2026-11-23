<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Fields\CheckboxListField;
use Sprint\Migration\Fields\RadioListField;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class BlogBuilder extends VersionBuilder
{
    protected function isBuilderEnabled(): bool
    {
        return $this->getHelperManager()->Blog()->isEnabled();
    }

    protected function initialize(): void
    {
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Blog'));

        $this->setTitle(implode(' ', [
            Locale::getMessage('BUILDER_BlogExport1'),
        ]));

        $this->setDescription(implode(PHP_EOL, [
            Locale::getMessage('DEVELOPER_NAME', ['#VALUE#' => '@temi4']),
            Locale::getMessage('BUILDER_BlogExport_Info'),
        ]));

        $this->addVersionFields();
    }

    /**
     * @throws HelperException
     * @throws MigrationException
     * @throws RebuildException
     */
    protected function execute(): void
    {
        $helper = $this->getHelperManager();

        $groupId = $this->askField('group_id',
            (new RadioListField())
                ->setTitle(Locale::getMessage('BUILDER_BlogExport_group_id'))
                ->setOptions($this->getGroupsSelect(), 'ID', 'TITLE', 'SITE_ID'),
        );

        $blogIds = $this->askField('blog_ids',
            (new CheckboxListField())
                ->setTitle(Locale::getMessage('BUILDER_BlogExport_blog_ids'))
                ->setOptions($this->getBlogsSelect($groupId), 'ID', 'TITLE', 'GROUP_TITLE'),
        );

        $group = $helper->Blog()->exportGroupById($groupId);

        $blogs = $helper->Blog()->exportBlogs($blogIds);

        $this->confirmVersionPlan();

        $this->createVersionFile(
            Module::getModuleTemplateFile('BlogExport'),
            [
                'group' => $group,
                'blogs' => $blogs,
            ],
        );
    }

    protected function getGroupsSelect(): array
    {
        return array_map(function ($item) {
            $item['TITLE'] = '[' . $item['SITE_ID'] . '] ' . $item['NAME'];
            return $item;
        }, $this->getHelperManager()->Blog()->getGroups());
    }

    protected function getBlogsSelect(int $groupIds): array
    {
        return array_map(function ($item) {
            $item['GROUP_TITLE'] = '[' . $item['GROUP_SITE_ID'] . '] ' . $item['GROUP_NAME'];
            $item['TITLE'] = '[' . $item['URL'] . '] ' . $item['NAME'];
            return $item;
        }, $this->getHelperManager()->Blog()->getBlogs(['GROUP_ID' => $groupIds]));
    }
}
