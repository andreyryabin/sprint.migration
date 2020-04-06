<?php

namespace Sprint\Migration\Schema;

use Sprint\Migration\AbstractSchema;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Locale;

class UserTypeEntitiesSchema extends AbstractSchema
{

    private $transforms = [];

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('SCHEMA_USER_TYPE_ENTITY'));
    }

    public function getMap()
    {
        return ['user_type_entities'];
    }

    protected function isBuilderEnabled()
    {
        return true;
    }

    public function outDescription()
    {
        $schemaItems = $this->loadSchema('user_type_entities', [
            'items' => [],
        ]);

        $this->out(
            Locale::getMessage(
                'SCHEMA_USER_TYPE_ENTITY_DESC',
                [
                    '#COUNT#' => count($schemaItems['items']),
                ]
            )
        );
    }

    /**
     * @throws HelperException
     * @throws \Exception
     */
    public function export()
    {
        $helper = $this->getHelperManager();

        $exportItems = $helper->UserTypeEntity()->exportUserTypeEntities();
        $exportItems = $this->filterEntities($exportItems);

        $this->saveSchema('user_type_entities', [
            'items' => $exportItems,
        ]);

    }

    /**
     * @throws HelperException
     */
    public function import()
    {
        $schemaItems = $this->loadSchema('user_type_entities', [
            'items' => [],
        ]);

        foreach ($schemaItems['items'] as $item) {
            $this->addToQueue('saveUserTypeEntity', $item);
        }


        $skip = [];
        foreach ($schemaItems['items'] as $item) {
            $skip[] = $this->getUniqEntity($item);
        }

        $this->addToQueue('clearUserTypeEntities', $skip);
    }


    /**
     * @param $fields
     * @throws HelperException
     */
    protected function saveUserTypeEntity($fields)
    {
        $helper = $this->getHelperManager();
        $helper->UserTypeEntity()->setTestMode($this->testMode);
        $helper->UserTypeEntity()->saveUserTypeEntity($fields);
    }

    /**
     * @param array $skip
     * @throws HelperException
     */
    protected function clearUserTypeEntities($skip = [])
    {
        $helper = $this->getHelperManager();
        $olds = $helper->UserTypeEntity()->exportUserTypeEntities();
        $olds = $this->filterEntities($olds);

        foreach ($olds as $old) {
            $uniq = $this->getUniqEntity($old);
            if (!in_array($uniq, $skip)) {
                $ok = ($this->testMode) ? true : $helper->UserTypeEntity()->deleteUserTypeEntity(
                    $old['ENTITY_ID'],
                    $old['FIELD_NAME']
                );

                $this->outWarningIf(
                    $ok,
                    Locale::getMessage(
                        'USER_TYPE_ENTITY_DELETED',
                        [
                            '#NAME#' => $old['FIELD_NAME'],
                        ]
                    )
                );
            }
        }
    }

    /**
     * @param $item
     * @throws HelperException
     * @return string
     */
    protected function getUniqEntity($item)
    {
        $entityId = $item['ENTITY_ID'];

        if (!isset($this->transforms[$entityId])) {
            $helper = $this->getHelperManager();
            $this->transforms[$entityId] = $helper->UserTypeEntity()->transformEntityId($entityId);
        }

        return $this->transforms[$entityId] . $item['FIELD_NAME'];
    }

    protected function filterEntities($items = [])
    {
        $filtered = [];
        foreach ($items as $item) {
            if (strpos($item['ENTITY_ID'], 'HLBLOCK_') === false) {
                $filtered[] = $item;
            }
        }
        return $filtered;
    }

}
