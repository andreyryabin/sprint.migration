<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Fields\CheckboxListField;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class SubscribeBuilder extends VersionBuilder
{
    protected function isBuilderEnabled(): bool
    {
        return $this->getHelperManager()->Subscribe()->isEnabled();
    }

    protected function initialize(): void
    {
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Subscribe'));

        $this->setTitle(implode(' ', [
            Locale::getMessage('BUILDER_SubscribeExport1'),
        ]));

        $this->setDescription(implode(PHP_EOL, [
            Locale::getMessage('DEVELOPER_NAME', ['#VALUE#' => '@temi4']),
            Locale::getMessage(
                'DEVELOPER_URI',
                ['#VALUE#' => 'https://github.com/andreyryabin/sprint.migration/pull/180'],
            ),
            Locale::getMessage('BUILDER_SubscribeExport_Info'),
        ]));

        $this->addVersionFields();
    }

    /**
     * @throws MigrationException
     * @throws RebuildException
     * @throws HelperException
     */
    protected function execute(): void
    {
        $helper = $this->getHelperManager();

        $rubricIds = $this->askField('rubric_ids',
            (new CheckboxListField(Locale::getMessage('BUILDER_SubscribeExport_rubric_ids')))
                ->setOptions($this->getRubricsSelect(), 'ID', 'TITLE', 'LID'),
        );

        $items = $helper->Subscribe()->exportRubrics($rubricIds);

        if (empty($items)) {
            $this->resetField('rubric_ids');
        }

        $this->confirmCreateVersion();

        $this->createVersionFile(
            Module::getModuleTemplateFile('SubscribeExport'),
            [
                'items' => $items,
            ],
        );
    }

    protected function getRubricsSelect(): array
    {
        $items = $this->getHelperManager()->Subscribe()->getRubrics();

        $items = array_filter($items, fn($item) => !empty($item['CODE']));

        return array_map(function ($item) {
            $item['TITLE'] = '[' . $item['CODE'] . '] ' . $item['NAME'];
            return $item;
        }, $items);
    }
}
