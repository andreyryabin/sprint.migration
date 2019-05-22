<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;
use Sprint\Migration\HelperManager;
use Sprint\Migration\Exceptions\HelperException;

class EventExport extends VersionBuilder
{

    protected function isBuilderEnabled()
    {
        return true;
    }

    protected function initialize()
    {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_EventExport1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_EventExport2'));

        $this->addField('prefix', [
            'title' => GetMessage('SPRINT_MIGRATION_FORM_PREFIX'),
            'value' => $this->getVersionConfig()->getVal('version_prefix'),
            'width' => 250,
        ]);

        $this->addField('description', [
            'title' => GetMessage('SPRINT_MIGRATION_FORM_DESCR'),
            'width' => 350,
            'height' => 40,
        ]);
    }


    protected function execute()
    {
        $helper = HelperManager::getInstance();

        $this->addField('event_types', [
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_EventExport_event_types'),
            'width' => 350,
            'select' => $this->getEventTypesStructure(),
            'multiple' => 1,
        ]);

        $eventTypes = $this->getFieldValue('event_types');
        if (empty($eventTypes)) {
            $this->rebuildField('event_types');
        }

        $eventTypes = is_array($eventTypes) ? $eventTypes : [$eventTypes];

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
            Module::getModuleDir() . '/templates/EventExport.php', [
            'result' => $result,
        ]);
    }


    protected function getEventTypesStructure()
    {
        $helper = HelperManager::getInstance();
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