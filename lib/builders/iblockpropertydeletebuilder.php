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

class IblockPropertyDeleteBuilder extends VersionBuilder
{
    protected function initialize(): void
    {
        $this->setEnabled($this->getHelperManager()->Iblock()->isEnabled());
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Iblock'));

        $this->setTitle(implode(' ', [
            Locale::getMessage('BUILDER_IblockPropertyDelete'),
        ]));

        $this->setDescription(implode(PHP_EOL, [
            Locale::getMessage('DEVELOPER_NAME', ['#VALUE#' => '@temi4']),
            Locale::getMessage(
                'DEVELOPER_URI',
                ['#VALUE#' => 'https://github.com/andreyryabin/sprint.migration/pull/184'],
            ),
            Locale::getMessage('BUILDER_IblockPropertyDelete_Info'),
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
        $exhelper = $this->getExchangeHelpers();

        $iblockId = (int) $this->askField('iblock_id',
            (new RadioListField(Locale::getMessage('BUILDER_IblockExport_IblockId')))
                ->setOptions($exhelper->IblockExchange()->getIblocksSelect(), 'ID', 'TITLE', 'GROUP'),
        );

        $propertyCodes = $this->askField(
            'property_codes',
            (new CheckboxListField(Locale::getMessage('BUILDER_IblockPropertyDelete_Properties')))
                ->setOptions($exhelper->IblockExchange()->getIblockPropertiesSelect($iblockId), 'CODE', 'TITLE'),
        );

        $this->confirmCreateVersion();

        $this->createVersionFile(
            Module::getModuleTemplateFile('IblockPropertyDelete'),
            [
                'iblock'        => $helper->Iblock()->exportIblock($iblockId),
                'propertyCodes' => $propertyCodes,
            ],
            false,
        );
    }

}
