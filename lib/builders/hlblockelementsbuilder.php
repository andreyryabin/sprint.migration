<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\HlblockElementsExport;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class HlblockElementsBuilder extends VersionBuilder
{
    /**
     * @return bool
     */
    protected function isBuilderEnabled()
    {
        return (!Locale::isWin1251() && $this->getHelperManager()->Hlblock()->isEnabled());
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_HlblockElementsExport1'));
        $this->setDescription(Locale::getMessage('BUILDER_HlblockElementsExport2'));
        $this->setGroup('Hlblock');

        $this->addVersionFields();
    }

    /**
     * @throws MigrationException
     * @throws HelperException
     * @throws RebuildException
     * @throws RestartException
     */
    protected function execute()
    {
        $hlblockId = $this->addFieldAndReturn(
            'hlblock_id',
            [
                'title'       => Locale::getMessage('BUILDER_HlblockElementsExport_HlblockId'),
                'placeholder' => '',
                'width'       => 250,
                'select'      => $this->getHelperManager()->HlblockExchange()->getHlblocksStructure(),
            ]
        );

        $fields = $this->getHelperManager()->HlblockExchange()->getHlblockFieldsCodes($hlblockId);
        $updateMode = $this->getFieldValueUpdateMode();

        if ($updateMode == HlblockElementsExport::UPDATE_MODE_XML_ID) {
            if (!in_array('UF_XML_ID', $fields)) {
                throw new HelperException('Field UF_XML_ID not found');
            }
        }

        $this->getExchangeManager()
             ->HlblockElementsExport()
             ->setLimit(20)
             ->setUpdateMode($updateMode)
             ->setExportFields($fields)
             ->setHlblockId($hlblockId)
             ->setExchangeFile(
                 $this->getVersionResourceFile(
                     $this->getVersionName(),
                     'hlblock_elements.xml'
                 )
             )->execute();

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/HlblockElementsExport.php',
            [
                'updateMode' => $updateMode,
            ]
        );
    }

    /**
     * @throws RebuildException
     * @return string
     */
    protected function getFieldValueUpdateMode()
    {
        return $this->addFieldAndReturn(
            'update_mode', [
                'title'       => Locale::getMessage('BUILDER_IblockElementsExport_UpdateMode'),
                'placeholder' => '',
                'width'       => 250,
                'select'      => [
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_NotUpdate'),
                        'value' => HlblockElementsExport::UPDATE_MODE_NOT,
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_UpdateByXmlId'),
                        'value' => HlblockElementsExport::UPDATE_MODE_XML_ID,
                    ],
                ],
            ]
        );
    }
}
