<?php

namespace Sprint\Migration\Schema;

use Sprint\Migration\AbstractSchema;
use Sprint\Migration\Exceptions\HelperException;

class GroupSchema extends AbstractSchema
{

    protected function isBuilderEnabled()
    {
        return true;
    }

    protected function initialize()
    {
        $this->setTitle('Схема групп пользователей');
    }

    public function getMap()
    {
        return ['user_groups'];
    }

    public function outDescription()
    {
        $schemaItems = $this->loadSchema('user_groups', [
            'items' => [],
        ]);

        $this->out('Группы пользователей: %d', count($schemaItems['items']));
    }


    public function export()
    {
        $helper = $this->getHelperManager();

        $exportItems = $helper->UserGroup()->exportGroups();

        $this->saveSchema('user_groups', [
            'items' => $exportItems,
        ]);
    }

    public function import()
    {
        $schemaItems = $this->loadSchema('user_groups', [
            'items' => [],
        ]);

        foreach ($schemaItems['items'] as $item) {
            $this->addToQueue('saveGroup', $item);
        }

        $skip = [];
        foreach ($schemaItems['items'] as $item) {
            $skip[] = $this->getUniqGroup($item);
        }

        $this->addToQueue('cleanGroups', $skip);
    }


    /**
     * @param $fields
     * @throws HelperException
     */
    protected function saveGroup($fields)
    {
        $helper = $this->getHelperManager();
        $helper->UserGroup()->setTestMode($this->testMode);
        $helper->UserGroup()->saveGroup($fields['STRING_ID'], $fields);
    }

    /**
     * @param array $skip
     */
    protected function cleanGroups($skip = [])
    {
        $helper = $this->getHelperManager();

        $olds = $helper->UserGroup()->getGroups();
        foreach ($olds as $old) {
            if (!empty($old['STRING_ID'])) {
                $uniq = $this->getUniqGroup($old);
                if (!in_array($uniq, $skip)) {
                    $ok = ($this->testMode) ? true : $helper->UserGroup()->deleteGroup($old['STRING_ID']);
                    $this->outWarningIf($ok, 'Группа %s: удалена', $old['NAME']);
                }
            }
        }
    }

    protected function getUniqGroup($item)
    {
        return $item['STRING_ID'];
    }

}