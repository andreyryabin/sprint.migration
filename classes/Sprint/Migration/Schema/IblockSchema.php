<?php

namespace Sprint\Migration\Schema;

use \Sprint\Migration\AbstractSchema;
use Sprint\Migration\HelperManager;

class IblockSchema extends AbstractSchema
{

    private $cache = array();

    /**@var HelperManager */
    private $helper;

    protected function initialize() {
        $this->helper = new HelperManager();
    }

    public function outDescription() {

        $this->out('[b]%s[/]', $this->getName());

        $schemas = $this->getSchemas(array('iblock_types', 'iblocks/'));
        foreach ($schemas as $name) {
            $this->out($this->getSchemaFile($name, true));
        }
    }

    public function export() {
        $this->deleteSchemas(array('iblock_types', 'iblocks/'));

        $types = $this->helper->Iblock()->getIblockTypes();
        $exportTypes = array();
        foreach ($types as $type) {
            $exportTypes[] = $this->helper->Iblock()->exportIblockType($type['ID']);
        }

        $this->saveSchema('iblock_types', array(
            'items' => $exportTypes
        ));

        $iblocks = $this->helper->Iblock()->getIblocks();
        foreach ($iblocks as $iblock) {
            if (!empty($iblock['CODE'])) {
                $this->saveSchema('iblocks/' . $iblock['IBLOCK_TYPE_ID'] . '-' . $iblock['CODE'], array(
                    'iblock' => $this->helper->Iblock()->exportIblock($iblock['ID']),
                    'fields' => $this->helper->Iblock()->exportIblockFields($iblock['ID']),
                    'props' => $this->helper->Iblock()->exportProperties($iblock['ID']),
                    'element_form' => $this->helper->AdminIblock()->exportElementForm($iblock['ID'])
                ));
            }
        }

        $this->out('schema saved to %s', $this->getSchemaDir(true));
    }

    public function import() {
        $schemaTypes = $this->loadSchema('iblock_types', array(
            'items' => array()
        ));

        $schemaIblocks = $this->loadSchemas('iblocks/', array(
            'iblock' => array(),
            'fields' => array(),
            'props' => array(),
            'element_form' => array()
        ));

        foreach ($schemaTypes['items'] as $type) {
            $this->addToQueue('saveIblockType', $type);
        }

        foreach ($schemaIblocks as $schemaIblock) {
            $iblockId = $this->getIblockId($schemaIblock['iblock']);

            $this->addToQueue('saveIblock', $iblockId, $schemaIblock['iblock']);
            $this->addToQueue('saveIblockFields', $iblockId, $schemaIblock['fields']);
        }

        foreach ($schemaIblocks as $schemaIblock) {
            $iblockId = $this->getIblockId($schemaIblock['iblock']);

            foreach ($schemaIblock['props'] as $prop) {
                $this->addToQueue('saveProperty', $iblockId, $prop);
            }

            $this->addToQueue('saveElementForm', $iblockId, $schemaIblock['element_form']);
        }

        foreach ($schemaIblocks as $schemaIblock) {
            $iblockId = $this->getIblockId($schemaIblock['iblock']);

            $skip = array();
            foreach ($schemaIblock['props'] as $prop) {
                $skip[] = $this->getUniqProp($prop);
            }

            $this->addToQueue('cleanProperties', $iblockId, $skip);
        }

        $skip = array();
        foreach ($schemaIblocks as $schemaIblock) {
            $skip[] = $this->getUniqIblock($schemaIblock['iblock']);
        }

        $this->addToQueue('cleanIblocks', $skip);


        $skip = array();
        foreach ($schemaTypes['items'] as $type) {
            $skip[] = $this->getUniqIblockType($type);
        }

        $this->addToQueue('cleanIblockTypes', $skip);
    }


    protected function saveIblockType($type) {
        $exists = $this->helper->Iblock()->exportIblockType($type['ID']);
        if ($exists != $type) {

            if (!$this->testMode) {
                $this->helper->Iblock()->saveIblockType($type);
            }

            $this->out('iblock type %s saved', $type['ID']);
        } else {
            $this->out('iblock type %s equal', $type['ID']);
        }
    }

    protected function saveIblock($iblockId, $iblock) {
        $exists = $this->helper->Iblock()->exportIblock($iblockId);
        if ($exists != $iblock) {
            if (!$this->testMode) {
                $this->helper->Iblock()->saveIblock($iblock);
            }

            $this->out('iblock %s saved', $iblockId);
        } else {
            $this->out('iblock %s is equal', $iblockId);
        }
    }

    protected function saveIblockFields($iblockId, $fields) {
        $exists = $this->helper->Iblock()->exportIblockFields($iblockId);
        if ($exists != $fields) {

            if (!$this->testMode) {
                $this->helper->Iblock()->saveIblockFields($iblockId, $fields);
            }

            $this->out('iblock %s fields saved', $iblockId);
        } else {
            $this->out('iblock %s fields equal', $iblockId);
        }
    }

    protected function saveElementForm($iblockId, $elementForm) {
        $exists = $this->helper->AdminIblock()->exportElementForm($iblockId);
        if ($exists != $elementForm) {
            if (!$this->testMode) {
                $this->helper->AdminIblock()->saveElementForm($iblockId, $elementForm);
            }
            $this->out('iblock %s admin form saved', $iblockId);
        } else {
            $this->out('iblock %s admin form equal', $iblockId);
        }
    }

    protected function saveProperty($iblockId, $property) {
        $exists = $this->helper->Iblock()->exportProperty($iblockId, $property['CODE']);
        if ($exists != $property) {
            if (!$this->testMode) {
                $this->helper->Iblock()->saveProperty($iblockId, $property);
            }
            $this->out('iblock %s property %s saved', $iblockId, $property['CODE']);
        } else {
            $this->out('iblock %s property %s equal', $iblockId, $property['CODE']);
        }
    }

    protected function cleanProperties($iblockId, $skip = array()) {
        $olds = $this->helper->Iblock()->getProperties($iblockId);
        foreach ($olds as $old) {
            $uniq = $this->getUniqProp($old);
            if (!in_array($uniq, $skip)) {
                if (!$this->testMode) {
                    $this->helper->Iblock()->deletePropertyById($old['ID']);
                }
                $this->out('iblock %s property %s deleted', $iblockId, $old['ID']);
            }
        }
    }

    protected function cleanIblockTypes($skip = array()) {
        $olds = $this->helper->Iblock()->getIblockTypes();
        foreach ($olds as $old) {
            $uniq = $this->getUniqIblockType($old);
            if (!in_array($uniq, $skip)) {
                if (!$this->testMode) {
                    $this->helper->Iblock()->deleteIblockType($old['ID']);
                }
                $this->out('iblock type %s deleted', $old['ID']);
            }
        }
    }

    protected function cleanIblocks($skip = array()) {
        $olds = $this->helper->Iblock()->getIblocks();
        foreach ($olds as $old) {
            $uniq = $this->getUniqIblock($old);
            if (!in_array($uniq, $skip)) {
                if (!$this->testMode) {
                    $this->helper->Iblock()->deleteIblock($old['ID']);
                }
                $this->out('iblock %s deleted', $old['ID']);
            }
        }
    }


    protected function getUniqProp($prop) {
        return $prop['CODE'];
    }

    protected function getUniqIblockType($type) {
        return $type['ID'];
    }

    protected function getUniqIblock($iblock) {
        return $iblock['IBLOCK_TYPE_ID'] . $iblock['CODE'];
    }

    protected function getIblockId($iblock) {
        $uniq = $this->getUniqIblock($iblock);

        if (!isset($this->cache[$uniq])) {
            $this->cache[$uniq] = $this->helper->Iblock()->getIblockId(
                $iblock['CODE'],
                $iblock['IBLOCK_TYPE_ID']
            );
        }

        return $this->cache[$uniq];

    }

}