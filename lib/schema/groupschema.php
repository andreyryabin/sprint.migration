<?php

namespace Sprint\Migration\Schema;

use Exception;
use Sprint\Migration\AbstractSchema;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Locale;

class GroupSchema extends AbstractSchema
{
    protected function isBuilderEnabled()
    {
        return true;
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('SCHEMA_USER_GROUP'));
    }

    public function getMap()
    {
        return ['user_groups'];
    }

    public function outDescription()
    {
        $schemaItems = $this->loadSchema(
            'user_groups', [
                'items' => [],
            ]
        );

        $this->out(
            Locale::getMessage(
                'SCHEMA_USER_GROUP_DESC',
                [
                    '#COUNT#' => count($schemaItems['items']),
                ]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function export()
    {
        $helper = $this->getHelperManager();

        $exportItems = $helper->UserGroup()->exportGroups();

        $this->saveSchema(
            'user_groups', [
                'items' => $exportItems,
            ]
        );
    }

    public function import()
    {
        $schemaItems = $this->loadSchema(
            'user_groups', [
                'items' => [],
            ]
        );

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
     *
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
                    $this->outWarningIf(
                        $ok,
                        Locale::getMessage(
                            'USER_GROUP_DELETED',
                            [
                                '#NAME#' => $old['NAME'],
                            ]
                        )
                    );
                }
            }
        }
    }

    protected function getUniqGroup($item)
    {
        return $item['STRING_ID'];
    }
}
