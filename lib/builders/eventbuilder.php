<?php

namespace Sprint\Migration\Builders;

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
        $this->setGroup('Main');

        $this->addVersionFields();
    }

    /**
     * @throws RebuildException
     * @throws \Sprint\Migration\Exceptions\MigrationException
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();

        $eventTypes = $this->addFieldAndReturn('event_types', [
            'title' => Locale::getMessage('BUILDER_EventExport_event_types'),
            'width' => 350,
            'select' => $this->getEventTypesStructure(),
            'multiple' => 1,
        ]);

        $result = [];
        foreach ($eventTypes as $eventName) {

            $types = $helper->Event()->exportEventTypes($eventName);
            $messages = $helper->Event()->exportEventMessages($eventName);

            $result[$eventName] = [
                'types' => $types,
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


    /**
     * @return array
     */
    protected function getEventTypesStructure()
    {
        $helper = $this->getHelperManager();
        $eventTypes = $helper->Event()->getEventTypes([
            'LID' => LANGUAGE_ID,
        ]);

        $structure = [];
        foreach ($eventTypes as $item) {
            $eventName = $item['EVENT_NAME'];
            $structure[$eventName] = [
                'title' => '[' . $eventName . '] ' . $item['NAME'],
                'value' => $eventName,
            ];
        }

        return $structure;
    }

}
