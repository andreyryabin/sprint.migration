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
    const UPDATE_MODE_NOT = 'not';
    const UPDATE_MODE_CODE = 'code';
    const UPDATE_MODE_XML_ID = 'xml_id';

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

        $updateMode = $this->getFieldValueUpdateMode();
        $exportFields = $this->getFieldValueExportFields($iblockId, $updateMode);
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
            );

        $this->createVersionFile(
            Module::getModuleTemplateFile('IblockElementsExport'),
            [
                'updateMode' => $updateMode,
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
                'title' => Locale::getMessage('BUILDER_IblockElementsExport_Properties'),
                'width' => 250,
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
                    'title' => Locale::getMessage('BUILDER_IblockElementsExport_Properties'),
                    'width' => 250,
                    'multiple' => 1,
                    'value' => [],
                    'select' => $iblockExchangeHelper->getIblockPropertiesStructure($iblockId),
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
        $elementsMode = $this->addFieldAndReturn(
            'filter_mode',
            [
                'title' => Locale::getMessage('BUILDER_IblockElementsExport_Filter'),
                'width' => 250,
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
                ],
            ]
        );

        if ($elementsMode == 'list_id') {
            $filterIds = $this->addFieldAndReturn(
                'export_filter_list_id', [
                    'title' => Locale::getMessage('BUILDER_IblockElementsExport_FilterListId'),
                    'width' => 350,
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
                    'title' => Locale::getMessage('BUILDER_IblockElementsExport_FilterListXmlId'),
                    'width' => 350,
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
    protected function getFieldValueExportFields($iblockId, $updateMode = false)
    {
        $iblockExchangeHelper = new IblockExchangeHelper;

        $fieldsMode = $this->addFieldAndReturn(
            'fields_mode',
            [
                'title' => Locale::getMessage('BUILDER_IblockElementsExport_Fields'),
                'width' => 250,
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
                    'title' => Locale::getMessage('BUILDER_IblockElementsExport_Fields'),
                    'width' => 250,
                    'multiple' => 1,
                    'value' => [],
                    'select' => $iblockExchangeHelper->getIblockElementFieldsStructure($iblockId),
                ]
            );
        } elseif ($fieldsMode == 'all') {
            $exportFields = $iblockExchangeHelper->getIblockElementFieldsStructure($iblockId);
            $exportFields = array_column($exportFields, 'value');
        } else {
            $exportFields = [];
        }

        if ($updateMode == self::UPDATE_MODE_CODE) {
            if (!in_array('CODE', $exportFields)) {
                $exportFields[] = 'CODE';
            }
        } elseif ($updateMode == self::UPDATE_MODE_XML_ID) {
            if (!in_array('XML_ID', $exportFields)) {
                $exportFields[] = 'XML_ID';
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
                'title' => Locale::getMessage('BUILDER_IblockElementsExport_IblockId'),
                'placeholder' => '',
                'width' => 250,
                'items' => $iblockExchangeHelper->getIblocksStructure(),
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
    protected function getFieldValueUpdateMode()
    {
        return $this->addFieldAndReturn(
            'update_mode', [
                'title' => Locale::getMessage('BUILDER_IblockElementsExport_UpdateMode'),
                'placeholder' => '',
                'width' => 250,
                'select' => [
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_NotUpdate'),
                        'value' => self::UPDATE_MODE_NOT,
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_UpdateByCode'),
                        'value' => self::UPDATE_MODE_CODE,
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_UpdateByXmlId'),
                        'value' => self::UPDATE_MODE_XML_ID,
                    ],
                ],
            ]
        );
    }

    protected function explodeString(string $string, string $delimiter = ' '): array
    {
        $values = explode($delimiter, trim($string));
        return array_filter($values);
    }
}
