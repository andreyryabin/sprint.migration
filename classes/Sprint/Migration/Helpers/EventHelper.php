<?php

namespace Sprint\Migration\Helpers;
use Sprint\Migration\Helper;

class EventHelper extends Helper
{

    /**
     * @param $eventName
     * @param $fields array(), key LID = language id
     * @return bool|int
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function addEventTypeIfNotExists($eventName, $fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('LID'));

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

        $fields = array_replace_recursive($default, $fields);
        $fields['EVENT_NAME'] = $eventName;

        $event = new \CEventType;
        $id = $event->Add($fields);

        if ($id){
            return $id;
        }

        $this->throwException(__METHOD__, 'Event type %s not added', $eventName);
    }


    /**
     * @param $eventName
     * @param $fields array(), key LID = site id
     * @return int
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function addEventMessageIfNotExists($eventName, $fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('SUBJECT', 'LID'));

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

        $fields = array_replace_recursive($default, $fields);
        $fields['EVENT_NAME'] = $eventName;

        $event = new \CEventMessage;
        $id = $event->Add($fields);

        if ($id) {
            return $id;
        }

        $this->throwException(__METHOD__, 'Event message %s not added, error: %s',$eventName, $event->LAST_ERROR);
    }

    /**
     * @param $eventName
     * @param $fields
     * @return bool|int
     * @deprecated use addEventTypeIfNotExists
     */
    public function addEventType($eventName, $fields) {
        return $this->addEventTypeIfNotExists($eventName, $fields);
    }


    /**
     * @param $eventName
     * @param $fields
     * @return bool|int
     * @deprecated use addEventMessageIfNotExists
     */
    public function addEventMessage($eventName, $fields) {
        return $this->addEventMessageIfNotExists($eventName, $fields);
    }

    public function updateEventMessageByFilter($filter, $fields) {

        $by = "site_id";
        $order = "desc";

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbRes = \CEventMessage::GetList($by, $order, $filter);

        while ($aItem = $dbRes->getNext()) {
            $event = new \CEventMessage;
            if (!$event->Update($aItem["ID"], $fields)) {
                $this->throwException(__METHOD__, $event->LAST_ERROR);
            }
        }

        return true;
    }

    public function updateEventMessage($eventName, $fields) {
        return $this->updateEventMessageByFilter(array('TYPE_ID' => $eventName), $fields);
    }

}