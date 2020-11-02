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
        $exportFields = $this->getFieldValueExportFields($iblockId);
        $exportProps = $this->getFieldValueExportProps($iblockId);
        $updateMode = $this->getFieldValueUpdateMode();

        if ($updateMode == 'code') {
            if (!in_array('CODE', $exportFields)) {
                $exportFields[] = 'CODE';
            }
        } elseif ($updateMode == 'xml_id') {
            if (!in_array('XML_ID', $exportFields)) {
                $exportFields[] = 'XML_ID';
            }
        } else {
            $updateMode = false;
        }

        if (!isset($this->params['~version_name'])) {
            $this->params['~version_name'] = $this->getVersionName();
        }

        $versionName = $this->params['~version_name'];

        $this->getExchangeManager()
             ->IblockElementsExport()
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
            'props_mode', [
                'title'  => Locale::getMessage('BUILDER_IblockElementsExport_Properties'),
                'width'  => 250,
                'select' => $this->getTernarySelector(),
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
    protected function getFieldValueExportFields($iblockId)
    {
        $this->addField(
            'fields_mode', [
                'title'  => Locale::getMessage('BUILDER_IblockElementsExport_Fields'),
                'width'  => 250,
                'select' => $this->getTernarySelector(),
            ]
        );
        $fieldsMode = $this->getFieldValue('fields_mode');
        if (empty($fieldsMode)) {
            $this->rebuildField('fields_mode');
        }

        if ($fieldsMode == 'some') {
            $this->addField(
                'export_fields', [
                    'title'    => Locale::getMessage('BUILDER_IblockElementsExport_Fields'),
                    'width'    => 250,
                    'multiple' => 1,
                    'value'    => [],
                    'select'   => $this->getIblockElementFieldsStructure($iblockId),
                ]
            );

            $exportFields = $this->getFieldValue('export_fields');
            if (!empty($exportFields)) {
                $exportFields = is_array($exportFields) ? $exportFields : [$exportFields];
            } else {
                $this->rebuildField('export_fields');
            }
        } elseif ($fieldsMode == 'all') {
            $exportFields = $this->getIblockElementFieldsStructure($iblockId);
            $exportFields = array_column($exportFields, 'value');
        } else {
            $exportFields = [];
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

    protected function getTernarySelector()
    {
        return [
            [
                'title' => Locale::getMessage('BUILDER_IblockElementsExport_All'),
                'value' => 'all',
            ],
            [
                'title' => Locale::getMessage('BUILDER_IblockElementsExport_None'),
                'value' => 'none',
            ],
            [
                'title' => Locale::getMessage('BUILDER_IblockElementsExport_Some'),
                'value' => 'some',
            ],
        ];
    }
}
