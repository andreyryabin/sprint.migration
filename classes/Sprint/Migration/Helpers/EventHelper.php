<?php

namespace Sprint\Migration\Helpers;
use Sprint\Migration\Helper;

class EventHelper extends Helper
{

    public function addEventTypeIfNotExists($eventName, $fields) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $aItem = \CEventType::GetList(array(
            'TYPE_ID' => $eventName,
            'LID' => $fields['LID']
        ))->Fetch();

        if ($aItem) {
            return $aItem['ID'];
        }

        $default = array(
            "LID" => $fields['LID'],
            "EVENT_NAME" => 'event_name',
            "NAME" => 'NAME',
            "DESCRIPTION" => 'description',
        );

        $fields = array_merge($default, $fields);
        $fields['EVENT_NAME'] = $eventName;

        $event = new \CEventType;
        $id = $event->Add($fields);

        return ($id) ? $id : false;
    }


    public function addEventMessageIfNotExists($eventName, $fields) {
        $by = 'id';
        $order = 'asc';
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $aItem = \CEventMessage::GetList($by, $order, array(
            'TYPE_ID' => $eventName,
            'SUBJECT' => $fields['SUBJECT']
        ))->Fetch();

        if ($aItem) {
            return $aItem['ID'];
        }

        $default = array(
            'ACTIVE' => 'Y',
            'LID' => 's1',
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => '#EMAIL_TO#',
            'BCC' => '',
            'SUBJECT' => 'subject',
            'BODY_TYPE' => 'text',
            'MESSAGE' => 'message',
        );

        $fields = array_merge($default, $fields);
        $fields['EVENT_NAME'] = $eventName;

        $event = new \CEventMessage;
        $id = $event->Add($fields);

        if ($event->LAST_ERROR) {
            $this->addError($event->LAST_ERROR);
        }

        return $id;
    }

    /* @deprecated use addEventTypeIfNotExists */
    public function addEventType($eventName, $fields) {
        $default = array(
            "LID" => 'ru',
            "EVENT_NAME" => 'event_name',
            "NAME" => 'NAME',
            "DESCRIPTION" => 'description',
            'SORT' => '',
        );

        $fields = array_merge($default, $fields);
        $fields['EVENT_NAME'] = $eventName;

        $event = new \CEventType;
        $id = $event->Add($fields);
        return $id;
    }


    /* @deprecated use addEventMessageIfNotExists */
    public function addEventMessage($eventName, $fields) {
        $default = array(
            'ACTIVE' => 'Y',
            'LID' => 's1',
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => '#EMAIL_TO#',
            'BCC' => '',
            'SUBJECT' => 'subject',
            'BODY_TYPE' => 'text',
            'MESSAGE' => 'message',
        );

        $fields = array_merge($default, $fields);
        $fields['EVENT_NAME'] = $eventName;

        $event = new \CEventMessage;
        $id = $event->Add($fields);

        echo $event->LAST_ERROR;
        return $id;
    }

    public function updateEventMessageByFilter($filter, $fields) {

        $by = "site_id";
        $order = "desc";

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbRes = \CEventMessage::GetList($by, $order, $filter);

        while ($aItem = $dbRes->getNext()) {

            $event = new \CEventMessage;
            if (!$event->Update($aItem["ID"], $fields)) {
                $this->addError($event->LAST_ERROR);
            }
        }

    }

    public function updateEventMessage($eventName, $fields) {
        $this->updateEventMessageByFilter(array('TYPE_ID' => $eventName), $fields);
    }

}