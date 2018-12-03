<?php

namespace Sprint\Migration\Schema;

use \Sprint\Migration\AbstractSchema;
use Sprint\Migration\HelperManager;

class IblockSchema extends AbstractSchema
{

    public function export() {
        $helper = new HelperManager();

        $this->deleteSchema('iblock_types');
        $this->deleteSchemas('iblocks');

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
                    'element_form' => $helper->AdminIblock()->exportElementForm($iblock['ID'])
                ));
            }
        }
    }


    public function import() {
        $helper = new HelperManager();

        $schemaTypes = $this->loadSchema('iblock_types', array(
            'items' => array()
        ));

        foreach ($schemaTypes['items'] as $type) {
            $exists = $helper->Iblock()->exportIblockType($type['ID']);

            if ($exists != $type) {
                $helper->Iblock()->saveIblockType($type);
                $this->outSuccess('iblock type %s saved', $type['ID']);
            } else {
                $this->out('iblock type %s is equal', $type['ID']);
            }

        }

        $deletedTypes = array();
        $existsTypes = $helper->Iblock()->getIblockTypes();
        foreach ($existsTypes as $existsType) {
            if (!$this->findByKey('ID', $existsType, $schemaTypes['items'])) {
                $deletedTypes[] = $existsType['ID'];
                $helper->Iblock()->deleteIblockType($existsType['ID']);
                $this->outError('iblock type %s is delete',
                    $existsType['ID']
                );
            }
        }


        $schemaIblocks = $this->loadSchemas('iblocks/');

        foreach ($schemaIblocks as $name => $schemaIblock) {

            if (in_array($schemaIblock['IBLOCK_TYPE_ID'], $deletedTypes)) {
                continue;
            }

            $iblockId = $helper->Iblock()->getIblockId(
                $schemaIblock['iblock']['CODE'],
                $schemaIblock['iblock']['IBLOCK_TYPE_ID']
            );

            $exists = $helper->Iblock()->exportIblock($iblockId);
            if ($exists != $schemaIblock['iblock']) {
                $helper->Iblock()->saveIblock($schemaIblock['iblock']);
                $this->outSuccess('iblock %s:%s saved',
                    $schemaIblock['iblock']['IBLOCK_TYPE_ID'],
                    $schemaIblock['iblock']['CODE']
                );
            } else {
                $this->out('iblock %s:%s is equal',
                    $schemaIblock['iblock']['IBLOCK_TYPE_ID'],
                    $schemaIblock['iblock']['CODE']
                );
            }

            $exists = $helper->Iblock()->exportIblockFields($iblockId);
            if ($exists != $schemaIblock['fields']) {
                $helper->Iblock()->saveIblockFields($schemaIblock['fields']);
                $this->outSuccess('iblock fields %s:%s saved',
                    $schemaIblock['iblock']['IBLOCK_TYPE_ID'],
                    $schemaIblock['iblock']['CODE']
                );
            } else {
                $this->out('iblock fields %s:%s is equal',
                    $schemaIblock['iblock']['IBLOCK_TYPE_ID'],
                    $schemaIblock['iblock']['CODE']
                );
            }
        }

        foreach ($schemaIblocks as $name => $schemaIblock) {

            if (in_array($schemaIblock['IBLOCK_TYPE_ID'], $deletedTypes)) {
                continue;
            }

            $iblockId = $helper->Iblock()->getIblockId(
                $schemaIblock['iblock']['CODE'],
                $schemaIblock['iblock']['IBLOCK_TYPE_ID']
            );


            $existsProps = $helper->Iblock()->exportProperties($iblockId);

            foreach ($schemaIblock['props'] as $prop) {
                $exists = $this->findByKey('CODE', $prop, $existsProps);

                if ($exists != $prop) {
                    $helper->Iblock()->saveProperty($iblockId, $prop);
                    $this->outSuccess('iblock property %s saved',
                        $prop['CODE']
                    );
                } else {
                    $this->out('iblock property %s is equal',
                        $prop['CODE']
                    );
                }
            }

            foreach ($existsProps as $existsProp) {
                if (!$this->findByKey('CODE', $existsProp, $schemaIblock['props'])) {
                    $helper->Iblock()->deletePropertyIfExists($iblockId, $existsProp['CODE']);
                    $this->outError('iblock property %s is delete',
                        $existsProp['CODE']
                    );
                }
            }


            $exists = $helper->AdminIblock()->exportElementForm($iblockId);

            if ($exists != $schemaIblock['element_form']) {
                $helper->AdminIblock()->saveElementForm($iblockId, $schemaIblock['element_form']);
                $this->outSuccess('iblock admin form %s:%s saved',
                    $schemaIblock['iblock']['IBLOCK_TYPE_ID'],
                    $schemaIblock['iblock']['CODE']
                );
            } else {
                $this->out('iblock admin form %s:%s is equal',
                    $schemaIblock['iblock']['IBLOCK_TYPE_ID'],
                    $schemaIblock['iblock']['CODE']
                );
            }


        }


    }

    protected function findByKey($key, $needle, $haystack) {
        foreach ($haystack as $item) {
            if ($item[$key] == $needle[$key]) {
                return $item;
            }
        }

        return false;
    }


}