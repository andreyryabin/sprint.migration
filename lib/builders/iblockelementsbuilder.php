<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\RestartableWriter;
use Sprint\Migration\Helpers\IblockExchangeHelper;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class IblockElementsBuilder extends VersionBuilder
{
    const UPDATE_METHOD_NOT    = 'not';
    const UPDATE_METHOD_CODE   = 'code';
    const UPDATE_METHOD_XML_ID = 'xml_id';

    /**
     * @return bool
     */
    protected function isBuilderEnabled()
    {
        return $this->getHelperManager()->Iblock()->isEnabled();
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_IblockElementsExport1'));
        $this->setDescription(Locale::getMessage('BUILDER_IblockElementsExport2'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Iblock'));

        $this->addVersionFields();
    }

    /**
     * @throws RebuildException
     * @throws MigrationException
     * @throws RestartException
     * @throws HelperException
     */
    protected function execute()
    {
        $exhelper = $this->getHelperManager()->IblockExchange();

        $iblockId = $this->getFieldValueIblockId();

        $exportFilter = $this->getFieldValueExportFilter();
        $updateMethod = $this->getFieldValueUpdateMethod();

        $exportFields = $this->getFieldValueExportFields($iblockId, $updateMethod, $exportFilter);
        $exportProps = $this->getFieldValueExportProps($iblockId);

        (new RestartableWriter($this, $this->getVersionExchangeDir()))
            ->setExchangeResource('iblock_elements.xml')
            ->execute(
                attributesFn: fn() => $exhelper->getWriterAttributes(
                    $iblockId
                ),
                totalCountFn: fn() => $exhelper->getWriterRecordsCount(
                    $iblockId,
                    $exportFilter
                ),
                recordsFn: fn($offset, $limit) => $exhelper->getWriterRecordsTag(
                    $offset,
                    $limit,
                    $iblockId,
                    $exportFilter,
                    $exportFields,
                    $exportProps
                ),
                progressFn: fn($value, $totalCount) => $this->outProgress(
                    'Progress: ',
                    $value,
                    $totalCount
                )
            );

        $this->createVersionFile(
            Module::getModuleTemplateFile('IblockElementsExport'),
            [
                'updateMethod' => $updateMethod,
            ]
        );
    }

    /**
     * @throws RebuildException
     */
    protected function getFieldValueExportProps($iblockId)
    {
        $iblockExchangeHelper = new IblockExchangeHelper;

        $propsMode = $this->addFieldAndReturn(
            'props_mode',
            [
                'title'  => Locale::getMessage('BUILDER_IblockElementsExport_Properties'),
                'width'  => 250,
                'select' => [
                    [
                        'title' => Locale::getMessage('BUILDER_SelectAll'),
                        'value' => 'all',
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_SelectNone'),
                        'value' => 'none',
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_SelectSome'),
                        'value' => 'some',
                    ],
                ],
            ]
        );

        if ($propsMode == 'some') {
            $exportProps = $this->addFieldAndReturn(
                'export_props',
                [
                    'title'    => Locale::getMessage('BUILDER_IblockElementsExport_Properties'),
                    'width'    => 250,
                    'multiple' => 1,
                    'value'    => [],
                    'select'   => $iblockExchangeHelper->getIblockPropertiesStructure($iblockId),
                ]
            );
        } elseif ($propsMode == 'all') {
            $exportProps = $iblockExchangeHelper->getIblockPropertiesStructure($iblockId);
            $exportProps = array_column($exportProps, 'value');
        } else {
            $exportProps = [];
        }

        return $exportProps;
    }

    /**
     * @throws RebuildException
     */
    protected function getFieldValueExportFilter(): array
    {
        $filterMode = $this->addFieldAndReturn(
            'filter_mode',
            [
                'title'  => Locale::getMessage('BUILDER_IblockElementsExport_Filter'),
                'width'  => 250,
                'select' => [
                    [
                        'title' => Locale::getMessage('BUILDER_SelectAll'),
                        'value' => 'all',
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_SelectSomeId'),
                        'value' => 'list_id',
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_SelectSomeXmlId'),
                        'value' => 'list_xml_id',
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_SelectSomeSectionId'),
                        'value' => 'list_section_id',
                    ],
                ],
            ]
        );

        if ($filterMode == 'list_id') {
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
        } elseif ($filterMode == 'list_section_id') {
            $filterSectionId = $this->addFieldAndReturn(
                'export_filter_list_section_id', [
                    'title' => Locale::getMessage('BUILDER_IblockElementsExport_FilterListSectionId'),
                    'width' => 100,
                ]
            );

            $exportFilter = [
                'IBLOCK_SECTION_ID' => (int)$filterSectionId,
            ];
        } elseif ($filterMode == 'list_xml_id') {
            $filterXmlIds = $this->addFieldAndReturn(
                'export_filter_list_xml_id',
                [
                    'title'  => Locale::getMessage('BUILDER_IblockElementsExport_FilterListXmlId'),
                    'width'  => 350,
                    'height' => 40,
                ]
            );

            $exportFilter = [
                'XML_ID' => $this->explodeString($filterXmlIds),
            ];
        } else {
            $exportFilter = [];
        }

        return $exportFilter;
    }

    /**
     * @throws RebuildException
     */
    protected function getFieldValueExportFields($iblockId, $updateMethod = false, $exportFilter = [])
    {
        $iblockExchangeHelper = new IblockExchangeHelper;

        $fieldsMode = $this->addFieldAndReturn(
            'fields_mode',
            [
                'title'  => Locale::getMessage('BUILDER_IblockElementsExport_Fields'),
                'width'  => 250,
                'select' => [
                    [
                        'title' => Locale::getMessage('BUILDER_SelectAll'),
                        'value' => 'all',
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_SelectNone'),
                        'value' => 'none',
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_SelectSome'),
                        'value' => 'some',
                    ],
                ],
            ]
        );

        if ($fieldsMode == 'some') {
            $exportFields = $this->addFieldAndReturn(
                'export_filter', [
                    'title'    => Locale::getMessage('BUILDER_IblockElementsExport_Fields'),
                    'width'    => 250,
                    'multiple' => 1,
                    'value'    => [],
                    'select'   => $iblockExchangeHelper->getIblockElementFieldsStructure(
                        iblockId: $iblockId,
                        withShowCounter: true
                    ),
                ]
            );
        } elseif ($fieldsMode == 'all') {
            $exportFields = $iblockExchangeHelper->getIblockElementFieldsStructure(
                iblockId: $iblockId,
            );
            $exportFields = array_column($exportFields, 'value');
        } else {
            $exportFields = [];
        }

        if ($updateMethod == self::UPDATE_METHOD_CODE) {
            if (!in_array('CODE', $exportFields)) {
                $exportFields[] = 'CODE';
            }
        }

        if ($updateMethod == self::UPDATE_METHOD_XML_ID) {
            if (!in_array('XML_ID', $exportFields)) {
                $exportFields[] = 'XML_ID';
            }
        }
        if (isset($exportFilter['IBLOCK_SECTION_ID'])) {
            if (!in_array('IBLOCK_SECTION', $exportFields)) {
                $exportFields[] = 'IBLOCK_SECTION';
            }
        }

        return $exportFields;
    }

    /**
     * @throws HelperException
     * @throws RebuildException
     */
    protected function getFieldValueIblockId(): int
    {
        $iblockExchangeHelper = new IblockExchangeHelper;

        $iblockId = $this->addFieldAndReturn(
            'iblock_id', [
                'title'       => Locale::getMessage('BUILDER_IblockElementsExport_IblockId'),
                'placeholder' => '',
                'width'       => 250,
                'items'       => $iblockExchangeHelper->getIblocksStructure(),
            ]
        );

        $iblock = $iblockExchangeHelper->exportIblock($iblockId);
        if (empty($iblock)) {
            $this->rebuildField('iblock_id');
        }

        return (int)$iblockId;
    }

    /**
     * @throws RebuildException
     */
    protected function getFieldValueUpdateMethod()
    {
        return $this->addFieldAndReturn(
            'update_method', [
                'title'       => Locale::getMessage('BUILDER_IblockElementsExport_UpdateMethod'),
                'placeholder' => '',
                'width'       => 250,
                'select'      => [
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_NotUpdate'),
                        'value' => self::UPDATE_METHOD_NOT,
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_SaveElementByCode'),
                        'value' => self::UPDATE_METHOD_CODE,
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_SaveElementByXmlId'),
                        'value' => self::UPDATE_METHOD_XML_ID,
                    ],
                ],
            ]
        );
    }
}
