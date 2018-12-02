<?php

namespace Sprint\Migration\Schema;

use \Sprint\Migration\AbstractSchema;
use Sprint\Migration\HelperManager;
use Sprint\Migration\Out;

class IblockSchema extends AbstractSchema
{

    public function export() {
        $helper = new HelperManager();

        $types = $helper->Iblock()->getIblockTypes();
        $exportTypes = array();
        foreach ($types as $type) {
            $exportTypes[] = $helper->Iblock()->exportIblockType($type['ID']);
        }

        $this->saveSchema('iblock_types', array(
            'items' => $exportTypes
        ));


        $iblocks = $helper->Iblock()->getIblocks();
        foreach ($iblocks as $iblock) {
            if (!empty($iblock['CODE'])) {
                $this->saveSchema('iblocks/' . $iblock['IBLOCK_TYPE_ID'] . '-' . $iblock['CODE'], array(
                    'iblock' => $helper->Iblock()->exportIblock($iblock['ID']),
                    'fields' => $helper->Iblock()->exportIblockFields($iblock['ID']),
                    'props' => $helper->Iblock()->exportProperties($iblock['ID']),
                    'element_form' => $helper->AdminIblock()->extractElementForm($iblock['ID'])
                ));
            }
        }
    }


    public function import() {
        $helper = new HelperManager();


        $schemaTypes = $this->loadSchema('iblock_types');
        $this->exitIfEmpty($schemaTypes, 'iblock types not found');

        foreach ($schemaTypes['items'] as $type) {
            $exists = $helper->Iblock()->exportIblockType($type['ID']);

            if ($exists != $type) {
                $helper->Iblock()->saveIblockType($type);
                Out::out('iblock type %s updated', $type['ID']);
            } else {
                Out::out('iblock type %s is equal', $type['ID']);
            }

        }


        $schemaIblocks = $this->loadSchemas('iblocks/');

        foreach ($schemaIblocks as $name => $schemaIblock) {

            $iblockId = $helper->Iblock()->getIblockId(
                $schemaIblock['iblock']['CODE'],
                $schemaIblock['iblock']['IBLOCK_TYPE_ID']
            );

            $exists = $helper->Iblock()->exportIblock($iblockId);
            if ($exists != $schemaIblock['iblock']) {
                $helper->Iblock()->saveIblock($schemaIblock['iblock']);
                Out::out('iblock %s:%s updated',
                    $schemaIblock['iblock']['IBLOCK_TYPE_ID'],
                    $schemaIblock['iblock']['CODE']
                );
            } else {
                Out::out('iblock %s:%s is equal',
                    $schemaIblock['iblock']['IBLOCK_TYPE_ID'],
                    $schemaIblock['iblock']['CODE']
                );
            }

            $exists = $helper->Iblock()->exportIblockFields($iblockId);
            if ($exists != $schemaIblock['fields']) {
                $helper->Iblock()->saveIblockFields($schemaIblock['fields']);
                Out::out('iblock fields %s:%s updated',
                    $schemaIblock['iblock']['IBLOCK_TYPE_ID'],
                    $schemaIblock['iblock']['CODE']
                );
            } else {
                Out::out('iblock fields %s:%s is equal',
                    $schemaIblock['iblock']['IBLOCK_TYPE_ID'],
                    $schemaIblock['iblock']['CODE']
                );
            }
        }

        foreach ($schemaIblocks as $name => $schemaIblock) {
            $iblockId = $helper->Iblock()->getIblockId(
                $schemaIblock['iblock']['CODE'],
                $schemaIblock['iblock']['IBLOCK_TYPE_ID']
            );


            $existsProps = $helper->Iblock()->exportProperties($iblockId);

            foreach ($schemaIblock['props'] as $prop) {
                $exists = $this->findProperty($prop['CODE'], $existsProps);

                if ($exists != $prop) {
                    $helper->Iblock()->saveProperty($iblockId, $prop);
                    Out::out('iblock property %s updated',
                        $prop['CODE']
                    );
                } else {
                    Out::out('iblock property %s is equal',
                        $prop['CODE']
                    );
                }

            }


            if (!empty($schemaIblock['element_form'])) {
                $exists = $helper->AdminIblock()->extractElementForm($iblockId);
                if ($exists != $schemaIblock['element_form']) {
                    $helper->AdminIblock()->saveElementForm($iblockId, $schemaIblock['element_form']);
                    Out::out('iblock admin form %s:%s updated',
                        $schemaIblock['iblock']['IBLOCK_TYPE_ID'],
                        $schemaIblock['iblock']['CODE']
                    );
                } else {
                    Out::out('iblock admin form %s:%s is equal',
                        $schemaIblock['iblock']['IBLOCK_TYPE_ID'],
                        $schemaIblock['iblock']['CODE']
                    );
                }
            }

        }


    }

    protected function findProperty($code, $props) {
        foreach ($props as $prop) {
            if ($prop['CODE'] == $code) {
                return $prop;
            }
        }

        return array();
    }


}