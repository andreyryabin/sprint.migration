<?php

namespace Sprint\Migration\Schema;

use Sprint\Migration\AbstractSchema;
use Sprint\Migration\Exceptions\HelperException;

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
        $this->setTitle('Схема инфоблоков');
    }

    public function getMap()
    {
        return ['iblock_types', 'iblocks/'];
    }

    public function outDescription()
    {
        $schemaTypes = $this->loadSchema('iblock_types', [
            'items' => [],
        ]);

        $this->out('Типы инфоблоков: %d', count($schemaTypes['items']));

        $schemaIblocks = $this->loadSchemas('iblocks/', [
            'iblock' => [],
            'fields' => [],
            'props' => [],
            'element_form' => [],
        ]);

        $this->out('Инфоблоков: %d', count($schemaIblocks));

        $cntProps = 0;
        $cntForms = 0;
        foreach ($schemaIblocks as $schemaIblock) {
            $cntProps += count($schemaIblock['props']);

            if (!empty($schemaIblock['element_form'])) {
                $cntForms++;
            }
        }

        $this->out('Свойств инфоблоков: %d', $cntProps);
        $this->out('Форм редактирования: %d', $cntForms);
    }

    /**
     * @throws HelperException
     */
    public function export()
    {
        $helper = $this->getHelperManager();

        $types = $helper->Iblock()->getIblockTypes();
        $exportTypes = [];
        foreach ($types as $type) {
            $exportTypes[] = $helper->Iblock()->exportIblockType($type['ID']);
        }

        $this->saveSchema('iblock_types', [
            'items' => $exportTypes,
        ]);

        $iblocks = $helper->Iblock()->getIblocks();
        foreach ($iblocks as $iblock) {
            if (!empty($iblock['CODE'])) {
                $this->saveSchema('iblocks/' . strtolower($iblock['IBLOCK_TYPE_ID'] . '-' . $iblock['CODE']), [
                    'iblock' => $helper->Iblock()->exportIblock($iblock['ID']),
                    'fields' => $helper->Iblock()->exportIblockFields($iblock['ID']),
                    'props' => $helper->Iblock()->exportProperties($iblock['ID']),
                    'element_form' => $helper->UserOptions()->exportElementForm($iblock['ID']),
                ]);
            }
        }

    }

    public function import()
    {
        $schemaTypes = $this->loadSchema('iblock_types', [
            'items' => [],
        ]);

        $schemaIblocks = $this->loadSchemas('iblocks/', [
            'iblock' => [],
            'fields' => [],
            'props' => [],
            'element_form' => [],
        ]);

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
            $this->addToQueue('saveElementForm', $iblockUid, $schemaIblock['element_form']);
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
     * @param array $skip
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
                        $this->outWarningIf($ok,
                            'Инфоблок %s: свойство %s удалено',
                            $iblockId,
                            $this->getTitleProp($old)
                        );
                    }
                }
            }
        }
    }

    /**
     * @param array $skip
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
                $this->outWarningIf($ok, 'Тип инфоблока %s: удален', $old['ID']);
            }
        }
    }

    /**
     * @param array $skip
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
                    $this->outWarningIf($ok, 'Инфоблок %s: удален', $old['ID']);
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
        return $this->getHelperManager()->Iblock()->getIblockUid($iblock);
    }


    protected function getIblockId($iblockUid)
    {
        $helper = $this->getHelperManager();

        if (isset($this->iblockIds[$iblockUid])) {
            return $this->iblockIds[$iblockUid];
        }

        $this->iblockIds[$iblockUid] = $helper->Iblock()
            ->getIblockIdByUid($iblockUid);

        return $this->iblockIds[$iblockUid];

    }

}