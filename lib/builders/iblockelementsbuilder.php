<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Builders\Traits\IblocksStructureTrait;
use Sprint\Migration\Exceptions\ExchangeException;
use Sprint\Migration\Exceptions\HelperException;
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
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();

        $this->addField(
            'iblock_id', [
                'title'       => Locale::getMessage('BUILDER_IblockElementsExport_IblockId'),
                'placeholder' => '',
                'width'       => 250,
                'items'       => $this->getIblocksStructure(),
            ]
        );

        $this->addField(
            'what', [
                'title'       => Locale::getMessage('BUILDER_IblockElementsExport_What'),
                'placeholder' => '',
                'multiple'    => 1,
                'width'       => 100,
                'select'      => [
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_WhatUpdateExists'),
                        'value' => 'replaceExists',
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_WhatIblockFields'),
                        'value' => 'iblockFields',
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_IblockElementsExport_WhatIblockProperties'),
                        'value' => 'iblockProperties',
                    ],
                ],
            ]
        );

        $iblockId = $this->getFieldValue('iblock_id');
        if (empty($iblockId)) {
            $this->rebuildField('iblock_id');
        }

        $iblock = $helper->Iblock()->exportIblock($iblockId);
        if (empty($iblock)) {
            $this->rebuildField('iblock_id');
        }

        $what = $this->getFieldValue('what', []);
        if (!empty($what)) {
            $what = is_array($what) ? $what : [$what];
        } else {
            $what = [];
        }

        if (in_array('iblockFields', $what)) {
            $this->addField(
                'export_fields', [
                    'title'    => Locale::getMessage('BUILDER_IblockElementsExport_Fields'),
                    'width'    => 250,
                    'multiple' => 1,
                    'value'    => [],
                    'select'   => $this->getIblockElementFieldsStructure($iblockId),
                ]
            );
        }

        if (in_array('iblockProperties', $what)) {
            $this->addField(
                'export_props', [
                    'title'    => Locale::getMessage('BUILDER_IblockElementsExport_Properties'),
                    'width'    => 250,
                    'multiple' => 1,
                    'value'    => [],
                    'select'   => $this->getIblockPropertiesStructure($iblockId),
                ]
            );
        }

        if (in_array('iblockFields', $what)) {
            $exportFields = $this->getFieldValue('export_fields');
            if (!empty($exportFields)) {
                $exportFields = is_array($exportFields) ? $exportFields : [$exportFields];
            } else {
                $this->rebuildField('export_fields');
            }
        } else {
            $exportFields = $this->getIblockElementFieldsStructure($iblockId);
            $exportFields = array_column($exportFields, 'value');
        }

        if (in_array('iblockProperties', $what)) {
            $exportProps = $this->getFieldValue('export_props');
            if (!empty($exportProps)) {
                $exportProps = is_array($exportProps) ? $exportProps : [$exportProps];
            } else {
                $this->rebuildField('export_props');
            }
        } else {
            $exportProps = $this->getIblockPropertiesStructure($iblockId);
            $exportProps = array_column($exportProps, 'value');
        }

        $replaceExists = false;
        if (in_array('replaceExists', $what)) {
            $replaceExists = true;
            if (!in_array('CODE', $exportFields)) {
                $exportFields[] = 'CODE';
            }
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
                'version'       => $versionName,
                'replaceExists' => $replaceExists,
            ]
        );

        unset($this->params['~version_name']);
    }
}
