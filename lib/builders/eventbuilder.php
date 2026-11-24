<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Fields\CheckboxListField;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class EventBuilder extends VersionBuilder
{
    protected function isBuilderEnabled(): bool
    {
        return true;
    }

    protected function initialize(): void
    {
        $this->setTitle(Locale::getMessage('BUILDER_EventExport1'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Main'));

        $this->addVersionFields();
    }

    /**
     * @throws RebuildException
     * @throws MigrationException
     * @throws HelperException
     */
    protected function execute(): void
    {
        $helper = $this->getHelperManager();

        $eventTypes = $this->askField('event_types',
            (new CheckboxListField())
                ->setTitle(Locale::getMessage('BUILDER_EventExport_event_types'))
                ->setOptions($this->getEventTypesSelect(), 'EVENT_NAME', 'NAME'),
        );

        $result = [];
        foreach ($eventTypes as $eventName) {
            $types = $helper->Event()->exportEventTypes($eventName);

            $messages = $helper->Event()->exportEventMessages($eventName);
            $smstemplates = $helper->Event()->exportEventSmsTemplates($eventName);

            $result[$eventName] = [
                'types' => $types,
                'email' => $messages,
                'sms'   => $smstemplates,
            ];
        }

        $this->confirmCreateVersion();

        $this->createVersionFile(
            Module::getModuleTemplateFile('EventExport'),
            [
                'result' => $result,
            ],
        );
    }

    protected function getEventTypesSelect(): array
    {
        $items = $this->getHelperManager()->Event()->getEventTypes([
            'LID' => LANGUAGE_ID,
        ]);

        return array_map(function ($item) {
            $item['NAME'] = '[' . $item['EVENT_NAME'] . '] ' . $item['NAME'];
            return $item;
        }, $items);
    }
}
