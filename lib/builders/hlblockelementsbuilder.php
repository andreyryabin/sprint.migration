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
    const UPDATE_METHOD_NOT      = 'not';
    const UPDATE_METHOD_XML_ID     = 'xml_id';
    const UPDATE_METHOD_EQUAL_KEYS = 'equal_keys';

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

        $fieldsSelect = $exhelper->getHlblockFieldsStructure($hlblockId);
        $exportFields = array_column($fieldsSelect, 'value');

        $exportFilter = $this->getFieldValueExportFilter($exportFields);
        $updateMethod = $this->getFieldValueUpdateMethod($exportFields);

        $equalKeys = $this->getFieldValueEqualKeys($updateMethod, $fieldsSelect);

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
                ),
                progressFn: fn($value, $totalCount) => $this->outProgress(
                    'Progress: ',
                    $value,
                    $totalCount
                )
            );

        $this->createVersionFile(
            Module::getModuleTemplateFile('HlblockElementsExport'),
            [
                'updateMethod' => $updateMethod,
                'equalKeys'    => $equalKeys,
            ]
        );
    }

    /**
     * @throws RebuildException
     */
    protected function getFieldValueUpdateMethod(array $exportFields)
    {
        $updateMethodSelect = [
            [
                'title' => Locale::getMessage('BUILDER_HlblockElementsExport_NotUpdate'),
                'value' => self::UPDATE_METHOD_NOT,
            ],
            [
                'title' => Locale::getMessage('BUILDER_HlblockElementsExport_SaveElementWithEqualKeys'),
                'value' => self::UPDATE_METHOD_EQUAL_KEYS,
            ],
        ];

        if (in_array('UF_XML_ID', $exportFields)) {
            $updateMethodSelect[] = [
                'title' => Locale::getMessage('BUILDER_HlblockElementsExport_SaveElementByXmlId'),
                'value' => self::UPDATE_METHOD_XML_ID,
            ];
        }

        return $this->addFieldAndReturn(
            'update_method',
            [
                'title'       => Locale::getMessage('BUILDER_HlblockElementsExport_UpdateMethod'),
                'placeholder' => '',
                'width'       => 250,
                'select'      => $updateMethodSelect,
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
                'title' => Locale::getMessage('BUILDER_HlblockElementsExport_SelectSomeId'),
                'value' => 'list_id',
            ],
        ];

        if (in_array('UF_XML_ID', $exportFields)) {
            $filterModeSelect[] = [
                'title' => Locale::getMessage('BUILDER_HlblockElementsExport_SelectSomeXmlId'),
                'value' => 'list_xml_id',
            ];
        }

        $elementsMode = $this->addFieldAndReturn(
            'filter_mode',
            [
                'title'  => Locale::getMessage('BUILDER_HlblockElementsExport_Filter'),
                'width'  => 250,
                'select' => $filterModeSelect,
            ]
        );

        if ($elementsMode == 'list_id') {
            $filterIds = $this->addFieldAndReturn(
                'export_filter_list_id', [
                    'title'  => Locale::getMessage('BUILDER_HlblockElementsExport_FilterListId'),
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
                    'title'  => Locale::getMessage('BUILDER_HlblockElementsExport_FilterListXmlId'),
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

    private function getFieldValueEqualKeys($updateMethod, $fieldsSelect)
    {
        if ($updateMethod == self::UPDATE_METHOD_EQUAL_KEYS) {
            return $this->addFieldAndReturn(
                'equal_keys', [
                    'title'    => Locale::getMessage('BUILDER_HlblockElementsExport_EqualKeys'),
                    'width'    => 250,
                    'multiple' => 1,
                    'value'    => [],
                    'select'   => $fieldsSelect,
                ]
            );
        }
        return [];
    }
}
