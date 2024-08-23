<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class HlblockBuilder extends VersionBuilder
{
    protected function isBuilderEnabled()
    {
        return $this->getHelperManager()->Hlblock()->isEnabled();
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_HlblockExport1'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Hlblock'));

        $this->addVersionFields();
    }

    /**
     * @throws HelperException
     * @throws RebuildException
     * @throws MigrationException
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();

        $hlblockId = $this->addFieldAndReturn(
            'hlblock_id',
            [
                'title'       => Locale::getMessage('BUILDER_HlblockExport_HlblockId'),
                'placeholder' => '',
                'width'       => 250,
                'select'      => $this->getHelperManager()->HlblockExchange()->getHlblocksStructure(),
            ]
        );

        $hlblock = $helper->Hlblock()->exportHlblock($hlblockId);
        if (empty($hlblockId)) {
            $this->rebuildField('hlblock_id');
        }

        $what = $this->addFieldAndReturn(
            'what', [
                'title'    => Locale::getMessage('BUILDER_HlblockExport_What'),
                'width'    => 250,
                'multiple' => 1,
                'value'    => [],
                'select'   => [
                    [
                        'title' => Locale::getMessage('BUILDER_HlblockExport_WhatHlblock'),
                        'value' => 'hlblock',
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_HlblockExport_WhatHlblockFields'),
                        'value' => 'hlblockFields',
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_HlblockExport_WhatHlblockUserOptions'),
                        'value' => 'hlblockUserOptions',
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_HlblockExport_WhatHlblockPermissions'),
                        'value' => 'hlblockPermissions',
                    ],
                ],
            ]
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
            $hlblockPermissions = $helper->Hlblock()->exportGroupPermissions($hlblockId);
        }

        if (in_array('hlblockUserOptions', $what)) {
            $exportElementForm = $helper->UserOptions()->exportHlblockForm($hlblockId);
            $exportElementList = $helper->UserOptions()->exportHlblockList($hlblockId);
        }

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/HlblockExport.php',
            [
                'hlblockExport'      => $hlblockExport,
                'hlblock'            => $hlblock,
                'hlblockFields'      => $hlblockFields,
                'hlblockPermissions' => $hlblockPermissions,
                'exportElementForm'  => $exportElementForm,
                'exportElementList'  => $exportElementList,
            ]
        );
    }
}
