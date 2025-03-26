<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\ExchangeWriter;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class HlblockElementsBuilder extends VersionBuilder
{
    const UPDATE_MODE_NOT = 'not';
    const UPDATE_MODE_XML_ID = 'xml_id';

    /**
     * @return bool
     */
    protected function isBuilderEnabled(): bool
    {
        return $this->getHelperManager()->Hlblock()->isEnabled();
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_HlblockElementsExport1'));
        $this->setDescription(Locale::getMessage('BUILDER_HlblockElementsExport2'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Hlblock'));

        $this->addVersionFields();
    }

    /**
     * @throws MigrationException
     * @throws HelperException
     * @throws RebuildException
     * @throws RestartException
     */
    protected function execute(): void
    {
        $exhelper = $this->getHelperManager()->HlblockExchange();

        $hlblockId = $this->addFieldAndReturn(
            'hlblock_id',
            [
                'title' => Locale::getMessage('BUILDER_HlblockElementsExport_HlblockId'),
                'placeholder' => '',
                'width' => 250,
                'select' => $exhelper->getHlblocksStructure(),
            ]
        );

        $fields = $exhelper->getHlblockFieldsCodes($hlblockId);

        $updateMode = $this->getFieldValueUpdateMode();

        if ($updateMode == self::UPDATE_MODE_XML_ID) {
            if (!in_array('UF_XML_ID', $fields)) {
                throw new HelperException('Field UF_XML_ID not found');
            }
        }

        (new ExchangeWriter)
            ->setCopyFiles(true)
            ->setExchangeFile($this->getExchangeFile('hlblock_elements.xml'))
            ->execute(
                restartable: $this,
                recordsCntFn: fn() => $exhelper->getElementsCount($hlblockId),
                recordsFn: fn($offset, $limit) => $exhelper->createRecordsTags($hlblockId, $offset, $limit, $fields),
                fileAttrsFn: fn() => ['hlblockUid' => $exhelper->getHlblockUid($hlblockId)],
                progressFn: fn($msg, $offset, $total) => $this->outProgress($msg, $offset, $total)
            );;

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/HlblockElementsExport.php',
            [
                'updateMode' => $updateMode,
                'hlblock' => $exhelper->exportHlblock($hlblockId)
            ]
        );
    }

    /**
     * @throws RebuildException
     */
    protected function getFieldValueUpdateMode()
    {
        return $this->addFieldAndReturn(
            'update_mode',
            [
                'title' => Locale::getMessage('BUILDER_IblockElementsExport_UpdateMode'),
                'placeholder' => '',
                'width' => 250,
                'select' => [
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_NotUpdate'),
                        'value' => self::UPDATE_MODE_NOT,
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_UpdateByXmlId'),
                        'value' => self::UPDATE_MODE_XML_ID,
                    ],
                ],
            ]
        );
    }
}
