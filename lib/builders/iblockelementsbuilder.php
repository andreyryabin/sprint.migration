<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Builders\Traits\IblocksStructureTrait;
use Sprint\Migration\Exceptions\ExchangeException;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class IblockElementsBuilder extends VersionBuilder
{
    use IblocksStructureTrait;

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

        $this->addVersionFields();
    }

    /**
     * @throws RebuildException
     * @throws ExchangeException
     * @throws RestartException
     * @throws HelperException
     * @throws MigrationException
     */
    protected function execute()
    {
        $iblockId = $this->getFieldValueIblockId();
        $exportFilter = $this->getFieldValueExportFilter($iblockId);

        $updateMode = $this->getFieldValueUpdateMode();
        $exportFields = $this->getFieldValueExportFields($iblockId, $updateMode);
        $exportProps = $this->getFieldValueExportProps($iblockId);

        if (!isset($this->params['~version_name'])) {
            $this->params['~version_name'] = $this->getVersionName();
        }

        $versionName = $this->params['~version_name'];

        $this->getExchangeManager()
             ->IblockElementsExport()
             ->setExportFilter($exportFilter)
             ->setExportFields($exportFields)
             ->setExportProperties($exportProps)
             ->setIblockId($iblockId)
             ->setLimit(20)
             ->setExchangeFile(
                 $this->getVersionResourceFile($versionName, 'iblock_elements.xml')
             )->execute();

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/IblockElementsExport.php',
            [
                'version'    => $versionName,
                'updateMode' => $updateMode,
            ]
        );

        unset($this->params['~version_name']);
    }

    /**
     * @param $iblockId
     *
     * @throws RebuildException
     * @return array
     */
    protected function getFieldValueExportProps($iblockId)
    {
        $this->addField(
            'props_mode',
            [
                'title'  => Locale::getMessage('BUILDER_IblockElementsExport_Properties'),
                'width'  => 250,
                'select' => [
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_SelectAll'),
                        'value' => 'all',
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_SelectNone'),
                        'value' => 'none',
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_SelectSome'),
                        'value' => 'some',
                    ],
                ],
            ]
        );
        $propsMode = $this->getFieldValue('props_mode');
        if (empty($propsMode)) {
            $this->rebuildField('props_mode');
        }
        if ($propsMode == 'some') {
            $this->addField(
                'export_props', [
                    'title'    => Locale::getMessage('BUILDER_IblockElementsExport_Properties'),
                    'width'    => 250,
                    'multiple' => 1,
                    'value'    => [],
                    'select'   => $this->getIblockPropertiesStructure($iblockId),
                ]
            );

            $exportProps = $this->getFieldValue('export_props');
            if (!empty($exportProps)) {
                $exportProps = is_array($exportProps) ? $exportProps : [$exportProps];
            } else {
                $this->rebuildField('export_props');
            }
        } elseif ($propsMode == 'all') {
            $exportProps = $this->getIblockPropertiesStructure($iblockId);
            $exportProps = array_column($exportProps, 'value');
        } else {
            $exportProps = [];
        }

        return $exportProps;
    }

    /**
     * @param $iblockId
     *
     * @throws RebuildException
     * @return array
     */
    protected function getFieldValueExportFilter($iblockId)
    {
        $this->addField(
            'filter_mode',
            [
                'title'  => Locale::getMessage('BUILDER_IblockElementsExport_Filter'),
                'width'  => 250,
                'select' => [
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_SelectAll'),
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

        $elementsMode = $this->getFieldValue('filter_mode');
        if (empty($elementsMode)) {
            $this->rebuildField('filter_mode');
        }

        if ($elementsMode == 'list_id') {
            $this->addField(
                'export_filter_list_id', [
                    'title'  => Locale::getMessage('BUILDER_IblockElementsExport_FilterListId'),
                    'width'  => 350,
                    'height' => 40,
                ]
            );

            $filterIds = $this->getFieldValue('export_filter_list_id');
            if (empty($filterIds)) {
                $this->rebuildField('export_filter_list_id');
            }

            $exportFilter = [
                'ID' => $this->explodeString($filterIds, ','),
            ];
        } elseif ($elementsMode == 'list_xml_id') {
            $this->addField(
                'export_filter_list_xml_id', [
                    'title'  => Locale::getMessage('BUILDER_IblockElementsExport_FilterListXmlId'),
                    'width'  => 350,
                    'height' => 40,
                ]
            );

            $filterXmlIds = $this->getFieldValue('export_filter_list_xml_id');
            if (empty($filterXmlIds)) {
                $this->rebuildField('export_filter_list_xml_id');
            }

            $exportFilter = [
                'XML_ID' => $this->explodeString($filterXmlIds, ','),
            ];
        } else {
            $exportFilter = [];
        }

        return $exportFilter;
    }

    /**
     * @param      $iblockId
     *
     * @param bool $updateMode
     *
     * @throws RebuildException
     * @return array
     */
    protected function getFieldValueExportFields($iblockId, $updateMode = false)
    {
        $this->addField(
            'fields_mode',
            [
                'title'  => Locale::getMessage('BUILDER_IblockElementsExport_Fields'),
                'width'  => 250,
                'select' => [
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_SelectAll'),
                        'value' => 'all',
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_SelectNone'),
                        'value' => 'none',
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_SelectSome'),
                        'value' => 'some',
                    ],
                ],
            ]
        );

        $fieldsMode = $this->getFieldValue('fields_mode');
        if (empty($fieldsMode)) {
            $this->rebuildField('fields_mode');
        }

        if ($fieldsMode == 'some') {
            $this->addField(
                'export_filter', [
                    'title'    => Locale::getMessage('BUILDER_IblockElementsExport_Fields'),
                    'width'    => 250,
                    'multiple' => 1,
                    'value'    => [],
                    'select'   => $this->getIblockElementFieldsStructure($iblockId),
                ]
            );

            $exportFields = $this->getFieldValue('export_filter');
            if (!empty($exportFields)) {
                $exportFields = is_array($exportFields) ? $exportFields : [$exportFields];
            } else {
                $this->rebuildField('export_filter');
            }
        } elseif ($fieldsMode == 'all') {
            $exportFields = $this->getIblockElementFieldsStructure($iblockId);
            $exportFields = array_column($exportFields, 'value');
        } else {
            $exportFields = [];
        }

        if ($updateMode == 'code') {
            if (!in_array('CODE', $exportFields)) {
                $exportFields[] = 'CODE';
            }
        } elseif ($updateMode == 'xml_id') {
            if (!in_array('XML_ID', $exportFields)) {
                $exportFields[] = 'XML_ID';
            }
        }

        return $exportFields;
    }

    /**
     * @throws HelperException
     * @throws RebuildException
     * @return integer
     */
    protected function getFieldValueIblockId()
    {
        $this->addField(
            'iblock_id', [
                'title'       => Locale::getMessage('BUILDER_IblockElementsExport_IblockId'),
                'placeholder' => '',
                'width'       => 250,
                'items'       => $this->getIblocksStructure(),
            ]
        );

        $helper = $this->getHelperManager();
        $iblockId = $this->getFieldValue('iblock_id');
        if (empty($iblockId)) {
            $this->rebuildField('iblock_id');
        }

        $iblock = $helper->Iblock()->exportIblock($iblockId);
        if (empty($iblock)) {
            $this->rebuildField('iblock_id');
        }

        return (int)$iblockId;
    }

    /**
     * @throws RebuildException
     * @return string
     */
    protected function getFieldValueUpdateMode()
    {
        $this->addField(
            'update_mode', [
                'title'       => Locale::getMessage('BUILDER_IblockElementsExport_UpdateMode'),
                'placeholder' => '',
                'width'       => 250,
                'select'      => [
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_NotUpdate'),
                        'value' => 'not',
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_UpdateByCode'),
                        'value' => 'code',
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_UpdateByXmlId'),
                        'value' => 'xml_id',
                    ],
                ],
            ]
        );

        $updateMode = $this->getFieldValue('update_mode');
        if (empty($updateMode)) {
            $this->rebuildField('update_mode');
        }

        return $updateMode;
    }

    protected function explodeString($string, $delimiter = ',')
    {
        $values = explode($delimiter, trim($string));

        $cleaned = [];
        foreach ($values as $value) {
            $value = trim(strval($value));
            if (!empty($value)) {
                $cleaned[] = $value;
            }
        }
        return $cleaned;
    }
}
