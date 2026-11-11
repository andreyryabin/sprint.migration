<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class OptionBuilder extends VersionBuilder
{
    protected function isBuilderEnabled()
    {
        return $this->getHelperManager()->Option()->isEnabled();
    }

    protected function initialize()
    {
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Main'));

        $this->setTitle(implode(' ', [
            Locale::getMessage('BUILDER_OptionExport1'),
        ]));

        $this->setDescription(implode(PHP_EOL, [
            Locale::getMessage('DEVELOPER_NAME', ['#VALUE#' => '@KotkinRoman']),
            Locale::getMessage('BUILDER_OptionExport_Info')
        ]));

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

        $this->addField(
            'site_id',
            [
                'title'    => Locale::getMessage('BUILDER_OptionExport_site_id'),
                'select'   => $this->createSelect($helper->Site()->getSites(), 'ID', 'NAME', true),
                'multiple' => 1,
            ]
        );

        $moduleIds = $this->addFieldAndReturn('module_id', [
            'title'    => Locale::getMessage('BUILDER_OptionExport_module_id'),
            'select'   => $this->createSelect($helper->Option()->getModules(), 'ID', 'ID'),
            'multiple' => 1,
        ]);


        $items = [];
        foreach ($moduleIds as $moduleId) {
            $options = $helper->Option()->getOptions(
                [
                    'MODULE_ID' => $moduleId,
                    'SITE_ID'   => $this->getFieldValue('site_id', [false])
                ]
            );

            foreach ($options as $option) {
                $items[] = $option;
            }
        }

        if (empty($items)) {
            $this->rebuildField('module_id');
        }

        $this->createVersionFile(
            Module::getModuleTemplateFile('OptionExport'),
            [
                'items' => $items,
            ]
        );
    }
}
