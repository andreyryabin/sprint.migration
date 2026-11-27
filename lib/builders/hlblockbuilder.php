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

class HlblockBuilder extends VersionBuilder
{
    protected function initialize(): void
    {
        $this->setEnabled($this->getHelperManager()->Hlblock()->isEnabled());
        $this->setTitle(Locale::getMessage('BUILDER_HlblockExport1'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Hlblock'));

        $this->addVersionFields();
    }

    /**
     * @throws HelperException
     * @throws RebuildException
     * @throws MigrationException
     */
    protected function execute(): void
    {
        $helper = $this->getHelperManager();
        $exhelper = $this->getExchangeHelpers();

        $hlblockId = $this->askField('hlblock_id',
            (new RadioListField(Locale::getMessage('BUILDER_HlblockExport_HlblockId')))
                ->setOptions($exhelper->HlblockExchange()->getHlblocksSelect(), 'ID', 'TITLE'),
        );

        $hlblock = $helper->Hlblock()->exportHlblock($hlblockId);
        if (empty($hlblockId)) {
            $this->resetField('hlblock_id');
        }

        $what = $this->askField('what',
            (new CheckboxListField(Locale::getMessage('BUILDER_HlblockExport_What')))
                ->addOption('hlblock', Locale::getMessage('BUILDER_HlblockExport_WhatHlblock'))
                ->addOption('hlblockFields', Locale::getMessage('BUILDER_HlblockExport_WhatHlblockFields'))
                ->addOption('hlblockUserOptions', Locale::getMessage('BUILDER_HlblockExport_WhatHlblockUserOptions'))
                ->addOption('hlblockPermissions', Locale::getMessage('BUILDER_HlblockExport_WhatHlblockPermissions')),
        );

        $hlblockExport = false;
        $hlblockFields = [];
        $hlblockPermissions = [];
        $exportElementForm = [];
        $exportElementList = [];
        if (in_array('hlblock', $what)) {
            $hlblockExport = true;
        }

        if (in_array('hlblockFields', $what)) {
            $hlblockFields = $helper->Hlblock()->exportFields($hlblockId);
        }

        if (in_array('hlblockPermissions', $what)) {
            $hlblockPermissions = $helper->Hlblock()->exportExtendedPermissions($hlblockId);
        }

        if (in_array('hlblockUserOptions', $what)) {
            $exportElementForm = $helper->UserOptions()->exportHlblockForm($hlblockId);
            $exportElementList = $helper->UserOptions()->exportHlblockList($hlblockId);
        }

        $this->confirmCreateVersion();

        $this->createVersionFile(
            Module::getModuleTemplateFile('HlblockExport'),
            [
                'hlblockExport'      => $hlblockExport,
                'hlblock'            => $hlblock,
                'hlblockFields'      => $hlblockFields,
                'hlblockPermissions' => $hlblockPermissions,
                'exportElementForm'  => $exportElementForm,
                'exportElementList'  => $exportElementList,
            ],
        );
    }
}
