<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class EventBuilder extends VersionBuilder
{
    protected function isBuilderEnabled()
    {
        return true;
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_EventExport1'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Main'));

        $this->addVersionFields();
    }

    /**
     * @throws RebuildException
     * @throws MigrationException
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();

        $eventTypes = $this->addFieldAndReturn('event_types', [
            'title'    => Locale::getMessage('BUILDER_EventExport_event_types'),
            'width'    => 350,
            'select'   => $this->getEventTypesSelect(),
            'multiple' => 1,
        ]);

        $result = [];
        foreach ($eventTypes as $eventName) {
            $types = $helper->Event()->exportEventTypes($eventName);
            $messages = $helper->Event()->exportEventMessages($eventName);

            $result[$eventName] = [
                'types'    => $types,
                'messages' => $messages,
            ];
        }

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/EventExport.php',
            [
                'result' => $result,
            ]
        );
    }

    protected function getEventTypesSelect(): array
    {
        $items = $this->getHelperManager()->Event()->getEventTypes([
            'LID' => LANGUAGE_ID,
        ]);

        $items = array_map(function ($item) {
            $item['NAME'] = '[' . $item['EVENT_NAME'] . '] ' . $item['NAME'];
            return $item;
        }, $items);

        return $this->createSelect($items, 'EVENT_NAME', 'NAME');
    }
}
