<?php

namespace Sprint\Migration\Helpers;

class EventHelper
{


    public function addEventType($eventName, $fields) {
        $default = array(
            "LID" => 'ru',
            "EVENT_NAME" => 'EVENT_NAME',
            "NAME" => 'NAME',
            "DESCRIPTION" => 'DESCRIPTION',
            'SORT' => '',
        );

        $fields = array_merge($default, $fields);
        $fields['EVENT_NAME'] = $eventName;

        $event = new \CEventType;
        $id = $event->Add($fields);
        return $id;
    }


    public function addEventMessage($eventName, $fields) {
        $default = array(
            'ACTIVE' => 'Y',
            'LID' => 's1',
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => '#EMAIL_TO#',
            'BCC' => '',
            'SUBJECT' => 'SUBJECT',
            'BODY_TYPE' => 'text',
            'MESSAGE' => 'MESSAGE',
        );

        $fields = array_merge($default, $fields);
        $fields['EVENT_NAME'] = $eventName;

        $event = new \CEventMessage;
        $id = $event->Add($fields);

        echo $event->LAST_ERROR;
        return $id;
    }

    public function updateEventMessageByFilter($filter, $fields) {

        $event = new \CEventMessage;

        $eventList = $event->GetList($by = "site_id", $order = "desc", $filter);

        while ($mess = $eventList->getNext()) {
            if (!$event->Update($mess["ID"], $fields)) {
                echo $event->LAST_ERROR . "\n";
            }
        }

    }

    public function updateEventMessage($eventName, $fields) {
        $filter = array();

        if (!is_array($eventName)) {
            $filter['TYPE_ID'] = $eventName;
        }

        $this->updateEventMessageByFilter($filter, $fields);
    }

}