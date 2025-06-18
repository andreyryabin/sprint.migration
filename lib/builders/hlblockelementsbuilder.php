<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\RestartableWriter;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class HlblockElementsBuilder extends VersionBuilder
{
    const UPDATE_MODE_NOT    = 'not';
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
                'title'       => Locale::getMessage('BUILDER_HlblockElementsExport_HlblockId'),
                'placeholder' => '',
                'width'       => 250,
                'select'      => $exhelper->getHlblocksStructure(),
            ]
        );

        $exportFields = $exhelper->getHlblockFieldsCodes($hlblockId);

        $exportFilter = $this->getFieldValueExportFilter($exportFields);
        $updateMode = $this->getFieldValueUpdateMode($exportFields);

        (new RestartableWriter($this, $this->getVersionExchangeDir()))
            ->setExchangeResource('hlblock_elements.xml')
            ->execute(
                attributesFn: fn() => $exhelper->getWriterAttributes($hlblockId),
                totalCountFn: fn() => $exhelper->getWriterRecordsCount(
                    $hlblockId,
                    $exportFilter
                ),
                recordsFn: fn($offset, $limit) => $exhelper->getWriterRecordsTag(
                    $offset,
                    $limit,
                    $hlblockId,
                    $exportFilter,
                    $exportFields
                )
            );

        $this->createVersionFile(
            Module::getModuleTemplateFile('HlblockElementsExport'),
            [
                'updateMode' => $updateMode,
            ]
        );
    }

    /**
     * @throws RebuildException
     */
    protected function getFieldValueUpdateMode(array $exportFields)
    {
        $updateModeSelect = [
            [
                'title' => Locale::getMessage('BUILDER_IblockElementsExport_NotUpdate'),
                'value' => self::UPDATE_MODE_NOT,
            ],
        ];

        if (in_array('UF_XML_ID', $exportFields)) {
            $updateModeSelect[] = [
                'title' => Locale::getMessage('BUILDER_IblockElementsExport_UpdateByXmlId'),
                'value' => self::UPDATE_MODE_XML_ID,
            ];
        }

        return $this->addFieldAndReturn(
            'update_mode',
            [
                'title'       => Locale::getMessage('BUILDER_IblockElementsExport_UpdateMode'),
                'placeholder' => '',
                'width'       => 250,
                'select'      => $updateModeSelect,
            ]
        );
    }

    protected function getFieldValueExportFilter(array $exportFields): array
    {
        $filterModeSelect = [
            [
                'title' => Locale::getMessage('BUILDER_SelectAll'),
                'value' => 'all',
            ],
            [
                'title' => Locale::getMessage('BUILDER_IblockElementsExport_SelectSomeId'),
                'value' => 'list_id',
            ],
        ];

        if (in_array('UF_XML_ID', $exportFields)) {
            $filterModeSelect[] = [
                'title' => Locale::getMessage('BUILDER_IblockElementsExport_SelectSomeXmlId'),
                'value' => 'list_xml_id',
            ];
        }

        $elementsMode = $this->addFieldAndReturn(
            'filter_mode',
            [
                'title'  => Locale::getMessage('BUILDER_IblockElementsExport_Filter'),
                'width'  => 250,
                'select' => $filterModeSelect,
            ]
        );

        if ($elementsMode == 'list_id') {
            $filterIds = $this->addFieldAndReturn(
                'export_filter_list_id', [
                    'title'  => Locale::getMessage('BUILDER_IblockElementsExport_FilterListId'),
                    'width'  => 350,
                    'height' => 40,
                ]
            );

            $exportFilter = [
                'ID' => $this->explodeString($filterIds),
            ];
        } elseif ($elementsMode == 'list_xml_id') {
            $filterXmlIds = $this->addFieldAndReturn(
                'export_filter_list_xml_id',
                [
                    'title'  => Locale::getMessage('BUILDER_IblockElementsExport_FilterListXmlId'),
                    'width'  => 350,
                    'height' => 40,
                ]
            );

            $exportFilter = [
                'UF_XML_ID' => $this->explodeString($filterXmlIds),
            ];
        } else {
            $exportFilter = [];
        }

        return $exportFilter;
    }
}
