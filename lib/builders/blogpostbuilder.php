<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\RestartableWriter;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class BlogPostBuilder extends VersionBuilder
{
    protected function isBuilderEnabled()
    {
        return $this->getHelperManager()->Blog()->isEnabled();
    }

    protected function initialize()
    {
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Blog'));
        $this->setTitle(implode(' ', [
            Locale::getMessage('BUILDER_BlogPostExport1'),
            Locale::getMessage('DEVELOPER_LABEL'),
        ]));

        $this->setDescription(implode(PHP_EOL, [
            Locale::getMessage('DEVELOPER_NAME', ['#VALUE#' => '@temi4']),
            Locale::getMessage('BUILDER_BlogPostExport_Info'),
        ]));

        $this->addVersionFields();
    }

    /**
     * @throws HelperException
     * @throws MigrationException
     * @throws RebuildException
     * @throws RestartException
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();
        $exhelper = $this->getHelperManager()->BlogExchange();

        $blogId = (int)$this->addFieldAndReturn('blog_id', [
            'title' => Locale::getMessage('BUILDER_BlogPostExport_blog_id'),
            'width' => 350,
            'items' => $this->getBlogsSelect(),
        ]);

        $helper->Blog()->exportBlogById($blogId);

        $filter = $this->getPostFilter();
        if (!$exhelper->getWriterRecordsCount($blogId, $filter)) {
            $this->rebuildField('post_filter_mode');
        }

        (new RestartableWriter($this, $this->getVersionExchangeDir()))
            ->setExchangeResource('blog_posts.xml')
            ->execute(
                attributesFn: fn() => $exhelper->getWriterAttributes($blogId),
                totalCountFn: fn() => $exhelper->getWriterRecordsCount($blogId, $filter),
                recordsFn: fn($offset, $limit) => $exhelper->getWriterRecordsTag(
                    $offset,
                    $limit,
                    $blogId,
                    $filter
                ),
                progressFn: fn($value, $totalCount) => $this->outProgress(
                    'Progress: ',
                    $value,
                    $totalCount
                )
            );

        $this->createVersionFile(Module::getModuleTemplateFile('BlogPostExport'));
    }

    /**
     * @throws RebuildException
     */
    protected function getPostFilter(): array
    {
        $mode = $this->addFieldAndReturn('post_filter_mode', [
            'title' => Locale::getMessage('BUILDER_BlogPostExport_filter'),
            'width' => 250,
            'select' => [
                [
                    'title' => Locale::getMessage('BUILDER_SelectAll'),
                    'value' => 'all',
                ],
                [
                    'title' => Locale::getMessage('BUILDER_BlogPostExport_SelectSomeId'),
                    'value' => 'list_id',
                ],
                [
                    'title' => Locale::getMessage('BUILDER_BlogPostExport_SelectSomeCode'),
                    'value' => 'list_code',
                ],
            ],
        ]);

        if ($mode === 'list_id') {
            $ids = $this->addFieldAndReturn('post_filter_list_id', [
                'title' => Locale::getMessage('BUILDER_BlogPostExport_FilterListId'),
                'width' => 350,
                'height' => 40,
            ]);

            $ids = array_values(array_filter(array_map('intval', $this->explodeString($ids))));
            return empty($ids) ? ['ID' => 0] : ['@ID' => $ids];
        }

        $filter = [];
        if ($mode === 'list_code') {
            $codes = $this->addFieldAndReturn('post_filter_list_code', [
                'title' => Locale::getMessage('BUILDER_BlogPostExport_FilterListCode'),
                'width' => 350,
                'height' => 40,
            ]);

            $codes = $this->explodeString($codes);
            if (empty($codes)) {
                return ['ID' => 0];
            }

            $filter['@CODE'] = $codes;
        }

        return $filter;
    }

    protected function getBlogsSelect(): array
    {
        $items = array_map(function ($item) {
            $item['GROUP_TITLE'] = '[' . $item['GROUP_SITE_ID'] . '] ' . $item['GROUP_NAME'];
            $item['TITLE'] = '[' . $item['URL'] . '] ' . $item['NAME'];
            return $item;
        }, $this->getHelperManager()->Blog()->getBlogs());

        return $this->createSelectWithGroups($items, 'ID', 'TITLE', 'GROUP_TITLE');
    }
}
