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

        $this->outSuccess('schema saved to %s', $this->getSchemaDir(true));
    }

    public function import() {
        $this->importTypes(1);
        $this->importIblocks(1);
    }

    public function testImport() {
        $this->importTypes(0);
        $this->importIblocks(0);
    }

    protected function importTypes($execute) {
        $helper = new HelperManager();

        $schemaTypes = $this->loadSchema('iblock_types');

        if (empty($schemaTypes['items'])) {
            $this->outError('iblock schema not found');
            return false;
        }


        foreach ($schemaTypes['items'] as $type) {
            $exists = $helper->Iblock()->exportIblockType($type['ID']);

            if ($exists != $type) {

                if ($execute) {
                    $helper->Iblock()->saveIblockType($type);
                }

                $this->outSuccess('iblock type %s saved', $type['ID']);
            } else {
                $this->out('iblock type %s is equal', $type['ID']);
            }

        }

        $existsTypes = $helper->Iblock()->getIblockTypes();
        foreach ($existsTypes as $existsType) {
            if (!$this->findValue($existsType['ID'], $schemaTypes['items'], 'ID')) {
                if ($execute) {
                    $helper->Iblock()->deleteIblockType($existsType['ID']);
                }
                $this->outError('iblock type %s is delete',
                    $existsType['ID']
                );
            }
        }

        return true;
    }

    protected function importIblocks($execute) {
        $helper = new HelperManager();

        $schemaIblocks = $this->loadSchemas('iblocks/');

        $existsTypes = $helper->Iblock()->getIblockTypes();

        foreach ($schemaIblocks as $name => $schemaIblock) {

            if (!$this->findValue($schemaIblock['iblock']['IBLOCK_TYPE_ID'], $existsTypes, 'ID')) {
                continue;
            }

            $iblockId = $helper->Iblock()->getIblockId(
                $schemaIblock['iblock']['CODE'],
                $schemaIblock['iblock']['IBLOCK_TYPE_ID']
            );

            $exists = $helper->Iblock()->exportIblock($iblockId);
            if ($exists != $schemaIblock['iblock']) {

                if ($execute) {
                    $helper->Iblock()->saveIblock($schemaIblock['iblock']);
                }

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

                if ($execute) {
                    $helper->Iblock()->saveIblockFields($schemaIblock['fields']);
                }

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

            if (!$this->findValue($schemaIblock['iblock']['IBLOCK_TYPE_ID'], $existsTypes, 'ID')) {
                continue;
            }


            $iblockId = $helper->Iblock()->getIblockId(
                $schemaIblock['iblock']['CODE'],
                $schemaIblock['iblock']['IBLOCK_TYPE_ID']
            );


            $existsProps = $helper->Iblock()->exportProperties($iblockId);

            foreach ($schemaIblock['props'] as $prop) {
                $exists = $this->findValue($prop['CODE'], $existsProps, 'CODE');

                if ($exists != $prop) {

                    if ($execute) {
                        $helper->Iblock()->saveProperty($iblockId, $prop);
                    }

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
                if (!$this->findValue($existsProp['CODE'], $schemaIblock['props'], 'CODE')) {

                    if ($execute) {
                        $helper->Iblock()->deletePropertyIfExists($iblockId, $existsProp['CODE']);
                    }

                    $this->outError('iblock property %s is delete',
                        $existsProp['CODE']
                    );
                }
            }


            $exists = $helper->AdminIblock()->exportElementForm($iblockId);

            if ($exists != $schemaIblock['element_form']) {

                if ($execute) {
                    $helper->AdminIblock()->saveElementForm($iblockId, $schemaIblock['element_form']);
                }

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

    protected function findValue($value, $haystack, $haystackKey) {
        foreach ($haystack as $item) {
            if ($item[$haystackKey] == $value) {
                return $item;
            }
        }

        return false;
    }


}