<?php

namespace Sprint\Migration\Schema;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Exception;
use Sprint\Migration\AbstractSchema;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;

class OptionSchema extends AbstractSchema
{
    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('SCHEMA_OPTION'));
    }

    public function getMap()
    {
        return ['options/'];
    }

    /**
     * @return bool
     */
    protected function isBuilderEnabled()
    {
        return $this->getHelperManager()->Option()->isEnabled();
    }

    protected function getModules(): array
    {
        $helper = $this->getHelperManager();

        return $helper->Option()->getModules([
            '!ID' => [Module::ID],
        ]);
    }

    public function outDescription()
    {
        $schemas = $this->loadSchemas(
            'options/', [
                'items' => [],
            ]
        );

        $cnt = 0;

        foreach ($schemas as $schema) {
            $cnt += count($schema['items']);
        }

        $this->out(
            Locale::getMessage(
                'SCHEMA_OPTION_DESC',
                [
                    '#COUNT#' => $cnt,
                ]
            )
        );
    }

    /**
     * @throws ArgumentException
     * @throws SystemException
     * @throws Exception
     */
    public function export()
    {
        $helper = $this->getHelperManager();

        foreach ($this->getModules() as $module) {
            $exportItems = $helper->Option()->getOptions(
                [
                    'MODULE_ID' => $module['ID'],
                ]
            );

            $this->saveSchema(
                'options/' . $module['ID'], [
                    'items' => $exportItems,
                ]
            );
        }
    }

    public function import()
    {
        foreach ($this->getModules() as $module) {
            $schema = $this->loadSchema(
                'options/' . $module['ID'], [
                    'items' => [],
                ]
            );

            $this->addToQueue('saveOptions', $schema['items']);
        }
    }

    /**
     * @param $items
     *
     * @throws ArgumentException
     * @throws SystemException
     * @throws ArgumentOutOfRangeException
     * @throws ObjectPropertyException
     * @throws HelperException
     */
    protected function saveOptions($items)
    {
        $helper = $this->getHelperManager();
        $helper->Option()->setTestMode($this->testMode);

        foreach ($items as $item) {
            $helper->Option()->saveOption($item);
        }
    }
}
