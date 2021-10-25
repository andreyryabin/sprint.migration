<?php

namespace Sprint\Migration\Builders;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;
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
        $this->setTitle(Locale::getMessage('BUILDER_OptionExport1'));
        $this->setGroup('Main');

        $this->addVersionFields();
    }

    /**
     * @throws ArgumentException
     * @throws SystemException
     * @throws RebuildException
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();

        $moduleIds = $this->addFieldAndReturn(
            'module_id',
            [
                'title'       => Locale::getMessage('BUILDER_OptionExport_module_id'),
                'placeholder' => '',
                'multiple'    => 1,
                'value'       => [],
                'width'       => 250,
                'select'      => $this->getModules(),
            ]
        );

        $items = [];
        foreach ($moduleIds as $moduleId) {
            $options = $helper->Option()->getOptions(
                [
                    'MODULE_ID' => $moduleId,
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
            Module::getModuleDir() . '/templates/OptionExport.php',
            [
                'items' => $items,
            ]
        );
    }

    protected function getModules()
    {
        $helper = $this->getHelperManager();

        $items = $helper->Option()->getModules();

        $result = [];
        foreach ($items as $item) {
            $result[] = [
                'title' => $item['ID'],
                'value' => $item['ID'],
            ];
        }

        return $result;
    }
}
