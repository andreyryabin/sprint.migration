<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Fields\CheckboxListField;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class OptionBuilder extends VersionBuilder
{
    protected function isBuilderEnabled(): bool
    {
        return $this->getHelperManager()->Option()->isEnabled();
    }

    protected function initialize(): void
    {
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Main'));

        $this->setTitle(implode(' ', [
            Locale::getMessage('BUILDER_OptionExport1'),
        ]));

        $this->setDescription(implode(PHP_EOL, [
            Locale::getMessage('DEVELOPER_NAME', ['#VALUE#' => '@KotkinRoman']),
            Locale::getMessage('BUILDER_OptionExport_Info'),
        ]));

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

        $this->showField(
            'site_id',
            (new CheckboxListField())
                ->setTitle(Locale::getMessage('BUILDER_OptionExport_site_id'))
                ->setOptions($helper->Site()->getSites(), 'ID', 'NAME'),
        );

        $moduleIds = $this->askField('module_id',
            (new CheckboxListField())
                ->setTitle(Locale::getMessage('BUILDER_OptionExport_module_id'))
                ->setOptions($helper->Option()->getModules(), 'ID', 'ID'),
        );


        $items = [];
        foreach ($moduleIds as $moduleId) {
            $options = $helper->Option()->getOptions(
                [
                    'MODULE_ID' => $moduleId,
                    'SITE_ID'   => $this->getFieldValue('site_id', [false]),
                ],
            );

            foreach ($options as $option) {
                $items[] = $option;
            }
        }

        if (empty($items)) {
            $this->resetField('module_id');
        }

        $this->confirmVersionPlan();

        $this->createVersionFile(
            Module::getModuleTemplateFile('OptionExport'),
            [
                'items' => $items,
            ],
        );
    }
}
