<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\RestartableWriter;
use Sprint\Migration\Fields\RadioListField;
use Sprint\Migration\Fields\TextareaField;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class BlogPostBuilder extends VersionBuilder
{
    protected function isBuilderEnabled(): bool
    {
        return $this->getHelperManager()->Blog()->isEnabled();
    }

    protected function initialize(): void
    {
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Blog'));
        $this->setTitle(implode(' ', [
            Locale::getMessage('BUILDER_BlogPostExport1'),
        ]));

        $this->setDescription(implode(PHP_EOL, [
            Locale::getMessage('DEVELOPER_NAME', ['#VALUE#' => '@temi4']),
            Locale::getMessage(
                'DEVELOPER_URI',
                ['#VALUE#' => 'https://github.com/andreyryabin/sprint.migration/pull/187'],
            ),
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
    protected function execute(): void
    {
        $helper = $this->getHelperManager();
        $exhelper = $this->getExchangeHelpers();

        $blogId = (int) $this->askField('blog_id',
            (new RadioListField())
                ->setTitle(Locale::getMessage('BUILDER_BlogPostExport_blog_id'))
                ->setOptions($this->getBlogsSelect(), 'ID', 'TITLE', 'GROUP_TITLE'),
        );

        $helper->Blog()->exportBlogById($blogId);

        $filter = $this->getPostFilter();
        if (!$exhelper->BlogExchange()->getWriterRecordsCount($blogId, $filter)) {
            $this->resetField('post_filter_mode');
        }

        $this->confirmVersionPlan();

        (new RestartableWriter($this, $this->getVersionExchangeDir()))
            ->setExchangeResource('blog_posts.xml')
            ->execute(
                attributesFn: fn() => $exhelper->BlogExchange()->getWriterAttributes($blogId),
                totalCountFn: fn() => $exhelper->BlogExchange()->getWriterRecordsCount($blogId, $filter),
                recordsFn: fn($offset, $limit) => $exhelper->BlogExchange()->getWriterRecordsTag(
                    $offset,
                    $limit,
                    $blogId,
                    $filter,
                ),
                progressFn: fn($value, $totalCount) => $this->outProgress(
                    'Progress',
                    $value,
                    $totalCount,
                ),
            );

        $this->createVersionFile(Module::getModuleTemplateFile('BlogPostExport'));
    }

    protected function getBlogsSelect(): array
    {
        return array_map(function ($item) {
            $item['GROUP_TITLE'] = '[' . $item['GROUP_SITE_ID'] . '] ' . $item['GROUP_NAME'];
            $item['TITLE'] = '[' . $item['URL'] . '] ' . $item['NAME'];
            return $item;
        }, $this->getHelperManager()->Blog()->getBlogs());
    }

    /**
     * @throws RebuildException
     */
    protected function getPostFilter(): array
    {
        $mode = $this->askField('post_filter_mode',
            (new RadioListField())
                ->setTitle(Locale::getMessage('BUILDER_BlogPostExport_filter'))
                ->addOption('all', Locale::getMessage('BUILDER_SelectAll'))
                ->addOption('list_id', Locale::getMessage('BUILDER_BlogPostExport_SelectSomeId'))
                ->addOption('list_code', Locale::getMessage('BUILDER_BlogPostExport_SelectSomeCode')),
        );

        if ($mode === 'list_id') {
            $ids = $this->askField('post_filter_list_id',
                (new TextareaField())
                    ->setTitle(Locale::getMessage('BUILDER_BlogPostExport_FilterListId'))
                    ->setCols(35)
                    ->setRows(4),
            );

            $ids = array_values(array_filter(array_map('intval', $this->explodeString($ids))));
            return empty($ids) ? ['ID' => 0] : ['@ID' => $ids];
        }

        $filter = [];
        if ($mode === 'list_code') {
            $codes = $this->askField('post_filter_list_code',
                (new TextareaField())
                    ->setTitle(Locale::getMessage('BUILDER_BlogPostExport_FilterListCode'))
                    ->setCols(35)
                    ->setRows(4),
            );

            $codes = $this->explodeString($codes);
            if (empty($codes)) {
                return ['ID' => 0];
            }

            $filter['@CODE'] = $codes;
        }

        return $filter;
    }
}