<?php

namespace Sprint\Migration\Schema;

use Exception;
use Sprint\Migration\AbstractSchema;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Locale;

class EventSchema extends AbstractSchema
{
    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('SCHEMA_EVENT'));
    }

    public function getMap()
    {
        return ['events/'];
    }

    protected function isBuilderEnabled()
    {
        return $this->getHelperManager()->Event()->isEnabled();
    }

    public function outDescription()
    {
        $schemas = $this->loadSchemas(
            'events/', [
                'event'    => '',
                'type'     => [],
                'messages' => [],
            ]
        );

        $ctnTypes = 0;
        $cntMessages = 0;

        foreach ($schemas as $schema) {
            $cntMessages += count($schema['messages']);
            $ctnTypes++;
        }

        $this->out(
            Locale::getMessage(
                'SCHEMA_EVENT_DESC',
                [
                    '#COUNT#' => $ctnTypes,
                ]
            )
        );
        $this->out(
            Locale::getMessage(
                'SCHEMA_EVENT_MESSAGES_DESC',
                [
                    '#COUNT#' => $cntMessages,
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
        $eventTypes = $helper->Event()->getEventTypes([]);
        foreach ($eventTypes as $eventType) {
            $eventName = $eventType['EVENT_NAME'];
            $eventUid = strtolower($eventType['EVENT_NAME'] . '_' . $eventType['LID']);

            unset($eventType['ID']);
            unset($eventType['EVENT_NAME']);
            $eventType['DESCRIPTION'] = $this->explodeText($eventType['DESCRIPTION']);

            $messages = $helper->Event()->exportEventMessages($eventName);
            foreach ($messages as $index => $message) {
                $message['MESSAGE'] = $this->explodeText($message['MESSAGE']);
                $messages[$index] = $message;
            }

            $this->saveSchema(
                'events/' . $eventUid, [
                    'event'    => $eventName,
                    'type'     => $eventType,
                    'messages' => $messages,
                ]
            );
        }
    }

    public function import()
    {
        $schemas = $this->loadSchemas(
            'events/', [
                'event'    => '',
                'type'     => [],
                'messages' => [],
            ]
        );

        foreach ($schemas as $schema) {
            $this->addToQueue('saveEventType', $schema['event'], $schema['type']);
            foreach ($schema['messages'] as $message) {
                $this->addToQueue('saveEventMessage', $schema['event'], $message);
            }
        }

        foreach ($schemas as $schema) {
            $skip = [];
            foreach ($schema['messages'] as $message) {
                $skip[] = $this->getUniqMessage($schema['event'], $message);
            }

            $this->addToQueue('cleanEventMessages', $schema['event'], $skip);
        }

        $skip = [];
        foreach ($schemas as $schema) {
            $skip[] = $this->getUniqType($schema['event'], $schema['type']);
        }

        $this->addToQueue('cleanEventTypes', $skip);
    }

    /**
     * @param $eventName
     * @param $fields
     *
     * @throws HelperException
     */
    protected function saveEventType($eventName, $fields)
    {
        $helper = $this->getHelperManager();
        $helper->Event()->setTestMode($this->testMode);

        if (isset($fields['DESCRIPTION']) && is_array($fields['DESCRIPTION'])) {
            $fields['DESCRIPTION'] = $this->implodeText($fields['DESCRIPTION']);
        }

        $helper->Event()->saveEventType($eventName, $fields);
    }

    /**
     * @param $eventName
     * @param $fields
     *
     * @throws HelperException
     */
    protected function saveEventMessage($eventName, $fields)
    {
        $helper = $this->getHelperManager();
        $helper->Event()->setTestMode($this->testMode);

        if (isset($fields['MESSAGE']) && is_array($fields['MESSAGE'])) {
            $fields['MESSAGE'] = $this->implodeText($fields['MESSAGE']);
        }

        $helper->Event()->saveEventMessage($eventName, $fields);
    }

    /**
     * @param array $skip
     *
     * @throws HelperException
     */
    protected function cleanEventTypes($skip = [])
    {
        $helper = $this->getHelperManager();

        $olds = $helper->Event()->getEventTypes([]);
        foreach ($olds as $old) {
            $uniq = $this->getUniqType($old['EVENT_NAME'], $old);
            if (!in_array($uniq, $skip)) {
                $ok = ($this->testMode) ? true : $helper->Event()->deleteEventType($old);
                $this->outWarningIf(
                    $ok,
                    Locale::getMessage(
                        'EVENT_TYPE_DELETED',
                        [
                            '#NAME#' => $old['EVENT_NAME'] . ':' . $old['LID'],
                        ]
                    )
                );
            }
        }
    }

    /**
     * @param       $eventName
     * @param array $skip
     *
     * @throws HelperException
     */
    protected function cleanEventMessages($eventName, $skip = [])
    {
        $helper = $this->getHelperManager();

        $olds = $helper->Event()->getEventMessages($eventName);
        foreach ($olds as $old) {
            $uniq = $this->getUniqMessage($old['EVENT_NAME'], $old);
            if (!in_array($uniq, $skip)) {
                $ok = ($this->testMode) ? true : $helper->Event()->deleteEventMessage($old);
                $this->outWarningIf(
                    $ok,
                    Locale::getMessage(
                        'EVENT_MESSAGE_DELETED',
                        [
                            '#NAME#' => $old['EVENT_NAME'] . ':' . $old['SUBJECT'],
                        ]
                    )
                );
            }
        }
    }

    protected function getUniqType($eventName, $item)
    {
        return $eventName . $item['LID'];
    }

    protected function getUniqMessage($eventName, $item)
    {
        return $eventName . $item['SUBJECT'];
    }

    protected function explodeText($string)
    {
        return explode(PHP_EOL, $string);
    }

    protected function implodeText($strings)
    {
        return implode(PHP_EOL, $strings);
    }
}
