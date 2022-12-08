<?php

namespace Sprint\Migration\Schema;

use Exception;
use Sprint\Migration\AbstractSchema;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Locale;

class IblockSchema extends AbstractSchema
{
    private $iblockIds = [];

    /**
     * @return bool
     */
    protected function isBuilderEnabled()
    {
        return $this->getHelperManager()->Iblock()->isEnabled();
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('SCHEMA_IBLOCK'));
    }

    public function getMap()
    {
        return ['iblock_types', 'iblocks/'];
    }

    public function outDescription()
    {
        $schemaTypes = $this->loadSchema(
            'iblock_types', [
                'items' => [],
            ]
        );

        $this->out(
            Locale::getMessage(
                'SCHEMA_IBLOCK_TYPE_DESC',
                [
                    '#COUNT#' => count($schemaTypes['items']),
                ]
            )
        );

        $schemaIblocks = $this->loadSchemas(
            'iblocks/', [
                'iblock'       => [],
                'fields'       => [],
                'props'        => [],
                'element_form' => [],
                'section_form' => [],
            ]
        );

        $this->out(
            Locale::getMessage(
                'SCHEMA_IBLOCK_DESC',
                [
                    '#COUNT#' => count($schemaIblocks),
                ]
            )
        );

        $cntProps = 0;
        $cntForms = 0;
        foreach ($schemaIblocks as $schemaIblock) {
            $cntProps += count($schemaIblock['props']);
            if (!empty($schemaIblock['element_form'])) {
                $cntForms++;
            }
            if (!empty($schemaIblock['section_form'])) {
                $cntForms++;
            }
        }

        $this->out(
            Locale::getMessage(
                'SCHEMA_IBLOCK_PROPS_DESC',
                [
                    '#COUNT#' => $cntProps,
                ]
            )
        );
        $this->out(
            Locale::getMessage(
                'SCHEMA_IBLOCK_FORMS_DESC',
                [
                    '#COUNT#' => $cntForms,
                ]
            )
        );
    }

    /**
     * @throws HelperException
     * @throws Exception
     */
    public function export()
    {
        $helper = $this->getHelperManager();

        $types = $helper->Iblock()->getIblockTypes();
        $exportTypes = [];
        foreach ($types as $type) {
            $exportTypes[] = $helper->Iblock()->exportIblockType($type['ID']);
        }

        $this->saveSchema(
            'iblock_types', [
                'items' => $exportTypes,
            ]
        );

        $iblocks = $helper->Iblock()->getIblocks();
        foreach ($iblocks as $iblock) {
            if (!empty($iblock['CODE'])) {
                $this->saveSchema(
                    'iblocks/' . strtolower($iblock['IBLOCK_TYPE_ID'] . '-' . $iblock['CODE']), [
                        'iblock'       => $helper->Iblock()->exportIblock($iblock['ID']),
                        'fields'       => $helper->Iblock()->exportIblockFields($iblock['ID']),
                        'props'        => $helper->Iblock()->exportProperties($iblock['ID']),
                        'element_form' => $helper->UserOptions()->exportElementForm($iblock['ID']),
                        'section_form' => $helper->UserOptions()->exportSectionForm($iblock['ID']),
                    ]
                );
            }
        }
    }

    public function import()
    {
        $schemaTypes = $this->loadSchema(
            'iblock_types', [
                'items' => [],
            ]
        );

        $schemaIblocks = $this->loadSchemas(
            'iblocks/', [
                'iblock' => [],
                'fields' => [],
                'props'  => [],
            ]
        );

        foreach ($schemaTypes['items'] as $type) {
            $this->addToQueue('saveIblockType', $type);
        }

        foreach ($schemaIblocks as $schemaIblock) {
            $iblockUid = $this->getUniqIblock($schemaIblock['iblock']);

            $this->addToQueue('saveIblock', $schemaIblock['iblock']);
            $this->addToQueue('saveIblockFields', $iblockUid, $schemaIblock['fields']);
        }

        foreach ($schemaIblocks as $schemaIblock) {
            $iblockUid = $this->getUniqIblock($schemaIblock['iblock']);
            $this->addToQueue('saveProperties', $iblockUid, $schemaIblock['props']);
            if (isset($schemaIblock['element_form'])) {
                $this->addToQueue('saveElementForm', $iblockUid, $schemaIblock['element_form']);
            }
            if (isset($schemaIblock['section_form'])) {
                $this->addToQueue('saveSectionForm', $iblockUid, $schemaIblock['section_form']);
            }
        }

        foreach ($schemaIblocks as $schemaIblock) {
            $iblockUid = $this->getUniqIblock($schemaIblock['iblock']);

            $skip = [];
            foreach ($schemaIblock['props'] as $prop) {
                $skip[] = $this->getUniqProp($prop);
            }

            $this->addToQueue('cleanProperties', $iblockUid, $skip);
        }

        $skip = [];
        foreach ($schemaIblocks as $schemaIblock) {
            $skip[] = $this->getUniqIblock($schemaIblock['iblock']);
        }

        $this->addToQueue('cleanIblocks', $skip);

        $skip = [];
        foreach ($schemaTypes['items'] as $type) {
            $skip[] = $this->getUniqIblockType($type);
        }

        $this->addToQueue('cleanIblockTypes', $skip);
    }

    /**
     * @param array $fields
     *
     * @throws HelperException
     */
    protected function saveIblockType($fields = [])
    {
        $helper = $this->getHelperManager();
        $helper->Iblock()->setTestMode($this->testMode);
        $helper->Iblock()->saveIblockType($fields);
    }

    /**
     * @param $fields
     *
     * @throws HelperException
     */
    protected function saveIblock($fields)
    {
        $helper = $this->getHelperManager();
        $helper->Iblock()->setTestMode($this->testMode);
        $helper->Iblock()->saveIblock($fields);
    }

    /**
     * @param $iblockUid
     * @param $fields
     */
    protected function saveIblockFields($iblockUid, $fields)
    {
        $iblockId = $this->getIblockId($iblockUid);
        if (!empty($iblockId)) {
            $helper = $this->getHelperManager();
            $helper->Iblock()->setTestMode($this->testMode);
            $helper->Iblock()->saveIblockFields($iblockId, $fields);
        }
    }

    /**
     * @param $iblockUid
     * @param $properties
     *
     * @throws HelperException
     */
    protected function saveProperties($iblockUid, $properties)
    {
        $iblockId = $this->getIblockId($iblockUid);
        if (!empty($iblockId)) {
            $helper = $this->getHelperManager();
            $helper->Iblock()->setTestMode($this->testMode);
            foreach ($properties as $property) {
                $helper->Iblock()->saveProperty($iblockId, $property);
            }
        }
    }

    /**
     * @param $iblockUid
     * @param $elementForm
     *
     * @throws HelperException
     */
    protected function saveElementForm($iblockUid, $elementForm)
    {
        $iblockId = $this->getIblockId($iblockUid);
        if (!empty($iblockId)) {
            $helper = $this->getHelperManager();
            $helper->UserOptions()->setTestMode($this->testMode);
            $helper->UserOptions()->saveElementForm($iblockId, $elementForm);
        }
    }

    /**
     * @param $iblockUid
     * @param $sectionForm
     *
     * @throws HelperException
     */
    protected function saveSectionForm($iblockUid, $sectionForm)
    {
        $iblockId = $this->getIblockId($iblockUid);
        if (!empty($iblockId)) {
            $helper = $this->getHelperManager();
            $helper->UserOptions()->setTestMode($this->testMode);
            $helper->UserOptions()->saveSectionForm($iblockId, $sectionForm);
        }
    }

    /**
     * @param       $iblockUid
     * @param array $skip
     *
     * @throws HelperException
     */
    protected function cleanProperties($iblockUid, $skip = [])
    {
        $iblockId = $this->getIblockId($iblockUid);
        if (!empty($iblockId)) {
            $helper = $this->getHelperManager();
            $olds = $helper->Iblock()->getProperties($iblockId);
            foreach ($olds as $old) {
                if (!empty($old['CODE'])) {
                    $uniq = $this->getUniqProp($old);
                    if (!in_array($uniq, $skip)) {
                        $ok = ($this->testMode) ? true : $helper->Iblock()->deletePropertyById($old['ID']);
                        $this->outWarningIf(
                            $ok,
                            Locale::getMessage(
                                'IB_PROPERTY_DELETED',
                                [
                                    '#IBLOCK_ID#' => $iblockId,
                                    '#NAME#'      => $this->getTitleProp($old),
                                ]
                            )
                        );
                    }
                }
            }
        }
    }

    /**
     * @param array $skip
     *
     * @throws HelperException
     */
    protected function cleanIblockTypes($skip = [])
    {
        $helper = $this->getHelperManager();

        $olds = $helper->Iblock()->getIblockTypes();
        foreach ($olds as $old) {
            $uniq = $this->getUniqIblockType($old);
            if (!in_array($uniq, $skip)) {
                $ok = ($this->testMode) ? true : $helper->Iblock()->deleteIblockType($old['ID']);
                $this->outWarningIf(
                    $ok,
                    Locale::getMessage(
                        'IB_TYPE_DELETED',
                        [
                            '#NAME#' => $old['ID'],
                        ]
                    )
                );
            }
        }
    }

    /**
     * @param array $skip
     *
     * @throws HelperException
     */
    protected function cleanIblocks($skip = [])
    {
        $helper = $this->getHelperManager();

        $olds = $helper->Iblock()->getIblocks();
        foreach ($olds as $old) {
            if (!empty($old['CODE'])) {
                $uniq = $this->getUniqIblock($old);
                if (!in_array($uniq, $skip)) {
                    $ok = ($this->testMode) ? true : $helper->Iblock()->deleteIblock($old['ID']);
                    $this->outWarningIf(
                        $ok,
                        Locale::getMessage(
                            'IB_DELETED',
                            [
                                '#NAME#' => $old['ID'],
                            ]
                        )
                    );
                }
            }
        }
    }

    protected function getTitleProp($prop)
    {
        return empty($prop['CODE']) ? $prop['ID'] : $prop['CODE'];
    }

    protected function getUniqProp($prop)
    {
        return $prop['CODE'];
    }

    protected function getUniqIblockType($type)
    {
        return $type['ID'];
    }

    protected function getUniqIblock($iblock)
    {
        return $this
            ->getHelperManager()
            ->Iblock()
            ->getIblockUid($iblock);
    }

    protected function getIblockId($iblockUid)
    {
        $helper = $this->getHelperManager();

        if (isset($this->iblockIds[$iblockUid])) {
            return $this->iblockIds[$iblockUid];
        }

        $this->iblockIds[$iblockUid] = $helper
            ->Iblock()
            ->getIblockIdByUid($iblockUid);

        return $this->iblockIds[$iblockUid];
    }
}
