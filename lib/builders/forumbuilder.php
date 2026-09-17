<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class ForumBuilder extends VersionBuilder
{
    protected function isBuilderEnabled(): bool
    {
        return $this->getHelperManager()->Forum()->isEnabled();
    }

    protected function initialize()
    {
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Forum'));

        $this->setTitle(implode(' ', [
            Locale::getMessage('BUILDER_ForumExport1'),
        ]));

        $this->setDescription(implode(PHP_EOL, [
            Locale::getMessage('DEVELOPER_NAME', ['#VALUE#' => '@temi4']),
            Locale::getMessage('DEVELOPER_URI', ['#VALUE#' => 'https://github.com/andreyryabin/sprint.migration/pull/183']),
            Locale::getMessage('BUILDER_ForumExport_Info'),
        ]));

        $this->addVersionFields();
    }

    /**
     * @throws HelperException
     * @throws MigrationException
     * @throws RebuildException
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();

        $forumIds = $this->addFieldAndReturn('forum_ids', [
            'title'    => Locale::getMessage('BUILDER_ForumExport_forum_ids'),
            'width'    => 350,
            'multiple' => 1,
            'value'    => [],
            'items'    => $this->getForumsSelect(),
        ]);

        $forums = $helper->Forum()->exportForums($forumIds);

        if (empty($forums)) {
            $this->rebuildField('forum_ids');
        }

        $this->createVersionFile(
            Module::getModuleTemplateFile('ForumExport'),
            [
                'groups' => [],
                'forums' => $forums,
            ]
        );
    }

    /**
     * @throws HelperException
     */
    protected function getForumsSelect(): array
    {
        $helper = $this->getHelperManager()->Forum();
        $groups = [];

        foreach ($helper->ensureGroupXmlIds($helper->getGroups()) as $group) {
            $groups[$group['ID']] = '[' . $group['XML_ID'] . '] ' . $group['NAME'];
        }

        $items = $helper->ensureForumXmlIds($helper->getForums());

        $items = array_map(function ($item) use ($groups) {
            $item['GROUP_TITLE'] = $groups[$item['FORUM_GROUP_ID']] ?? Locale::getMessage('BUILDER_ForumExport_WithoutGroup');
            $item['TITLE'] = '[' . $item['XML_ID'] . '] ' . $item['NAME'];
            return $item;
        }, $items);

        return $this->createSelectWithGroups($items, 'ID', 'TITLE', 'GROUP_TITLE');
    }
}
