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

        $this->setTitle('Схема инфоблоков');
    }

    public function outDescription() {
        $this->out('[b]%s[/]', $this->getTitle());

        $schemaTypes = $this->loadSchema('iblock_types', array(
            'items' => array()
        ));

        $this->out('[b]Типы инфоблоков:[/] %d', count($schemaTypes['items']));
        foreach ($schemaTypes['items'] as $item) {
            $this->out('[%s] %s', $item['ID'], $item['LANG']['ru']['NAME']);
        }

        $schemaIblocks = $this->loadSchemas('iblocks/', array(
            'iblock' => array(),
            'fields' => array(),
            'props' => array(),
            'element_form' => array()
        ));

        $this->out('[b]Инфоблоков:[/] %d', count($schemaIblocks));
        foreach ($schemaIblocks as $schemaIblock) {
            $iblock = $schemaIblock['iblock'];
            $this->out('[%s] %s', $iblock['CODE'], $iblock['NAME']);

            if (!empty($schemaIblock['fields'])) {
                $this->out('[t]Поля инфоблока');
            }

            if (!empty($schemaIblock['element_form'])) {
                $this->out('[t]Форма редактирования');
            }

            $props = $schemaIblock['props'];
            $this->out('[t]Свойств: %d', count($props));
            foreach ($props as $prop) {
                $this->out('[t][t]%s', $this->getTitleProp($prop));
            }
        }

        $this->out(PHP_EOL);
    }

    public function export() {
        $this->deleteSchema('iblock_types');
        $this->deleteSchemas('iblocks/');

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

        $this->outSuccess('%s сохранена в:', $this->getTitle());
        $names = $this->getSchemas(array('iblock_types', 'iblocks/'));
        foreach ($names as $name) {
            $this->out($this->getSchemaFile($name, true));
        }
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

            $this->outSuccess('Тип инфоблока %s: сохранен', $type['ID']);
        } else {
            $this->out('Тип инфоблока %s: совпадает', $type['ID']);
        }
    }

    protected function saveIblock($iblockId, $iblock) {
        $exists = $this->helper->Iblock()->exportIblock($iblockId);
        if ($exists != $iblock) {
            if (!$this->testMode) {
                $this->helper->Iblock()->saveIblock($iblock);
            }

            $this->outSuccess('Инфоблок %s: сохранен', $iblockId);
        } else {
            $this->out('Инфоблок %s: совпадает', $iblockId);
        }
    }

    protected function saveIblockFields($iblockId, $fields) {
        $exists = $this->helper->Iblock()->exportIblockFields($iblockId);
        if ($exists != $fields) {

            if (!$this->testMode) {
                $this->helper->Iblock()->saveIblockFields($iblockId, $fields);
            }

            $this->outSuccess('Инфоблок %s: поля %сохранены', $iblockId);
        } else {
            $this->out('Инфоблок %s: поля совпадают', $iblockId);
        }
    }

    protected function saveElementForm($iblockId, $elementForm) {
        $exists = $this->helper->AdminIblock()->exportElementForm($iblockId);
        if ($exists != $elementForm) {
            if (!$this->testMode) {
                $this->helper->AdminIblock()->saveElementForm($iblockId, $elementForm);
            }
            $this->outSuccess('Инфоблок %s: форма редактирования сохранена', $iblockId);
        } else {
            $this->out('Инфоблок %s: форма редактирования cовпадает', $iblockId);
        }
    }

    protected function saveProperty($iblockId, $property) {
        $exists = $this->helper->Iblock()->exportProperty($iblockId, $property['CODE']);
        if ($exists != $property) {
            if (!$this->testMode) {
                $this->helper->Iblock()->saveProperty($iblockId, $property);
            }
            $this->outSuccess('Инфоблок %s: свойство %s сохранено', $iblockId, $this->getTitleProp($property));
        } else {
            $this->out('Инфоблок %s: свойство %s совпадает', $iblockId, $this->getTitleProp($property));
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
                $this->outError('Инфоблок %s: свойство %s удалено', $iblockId, $this->getTitleProp($old));
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
                $this->outError('Тип инфоблока %s: удален', $old['ID']);
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
                $this->outError('Инфоблок %s: удален', $old['ID']);
            }
        }
    }


    protected function getTitleProp($prop) {
        return empty($prop['CODE']) ? $prop['ID'] : $prop['CODE'];
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