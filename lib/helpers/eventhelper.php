<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class EventHelper extends Helper
{

    /**
     * Получает тип почтового события по фильтру или типу почтового события
     * @param $eventName
     * @return array
     */
    public function getEventType($eventName) {
        $filter = is_array($eventName) ? $eventName : array(
            'EVENT_NAME' => $eventName,
        );

        $dbres = \CEventType::GetList($filter);
        return $dbres->Fetch();
    }

    /**
     * Получает список типов почтовых событий по фильтру или типу почтового события
     * @param $eventName
     * @return array
     */
    public function getEventTypes($eventName) {
        $filter = is_array($eventName) ? $eventName : array(
            'EVENT_NAME' => $eventName,
        );

        $dbres = \CEventType::GetList($filter);
        return $this->fetchAll($dbres);
    }

    /**
     * Получает почтовый шаблон по фильтру или типу почтового события
     * @param $eventName
     * @return mixed
     */
    public function getEventMessage($eventName) {
        $filter = is_array($eventName) ? $eventName : array(
            'EVENT_NAME' => $eventName,
        );

        $by = 'id';
        $order = 'asc';

        $item = \CEventMessage::GetList($by, $order, $filter)->Fetch();
        return $this->prepareEventMessage($item);
    }

    /**
     * Получает почтовые шаблоны по фильтру или типу почтового события
     * @param $eventName
     * @return array
     */
    public function getEventMessages($eventName) {
        $filter = is_array($eventName) ? $eventName : array(
            'EVENT_NAME' => $eventName,
        );

        $by = 'id';
        $order = 'asc';

        $result = array();
        $dbres = \CEventMessage::GetList($by, $order, $filter);
        while ($item = $dbres->Fetch()) {
            $result[] = $this->prepareEventMessage($item);
        }
        return $result;
    }

    /**
     * Получает список сайтов для почтового шаблона
     * @param $messageId
     * @return array
     */
    public function getEventMessageSites($messageId) {
        $dbres = \CEventMessage::GetLang($messageId);
        return $this->fetchAll($dbres, false, 'LID');
    }

    /**
     * Получает почтовые шаблоны по фильтру или типу почтового события
     * Данные подготовлены для экспорта в миграцию или схему
     * @param $eventName
     * @return array
     */
    public function exportEventMessages($eventName) {
        $exports = array();
        $items = $this->getEventMessages($eventName);
        foreach ($items as $item) {
            $exports[] = $this->prepareExportEventMessage($item);
        }
        return $exports;
    }

    /**
     * Получает список типов почтовых событий по фильтру или типу почтового события
     * Данные подготовлены для экспорта в миграцию или схему
     * @param $eventName
     * @return array
     */
    public function exportEventTypes($eventName) {
        $exports = array();
        $items = $this->getEventTypes($eventName);
        foreach ($items as $item) {
            $exports[] = $this->prepareExportEventType($item);
        }
        return $exports;
    }

    /**
     * Добавляет тип почтового события если его не существует
     * @param $eventName
     * @param $fields , обязательные параметры - id сайта
     * @return bool|int|mixed
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function addEventTypeIfNotExists($eventName, $fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('LID'));

        $item = $this->getEventType(array(
            'EVENT_NAME' => $eventName,
            'LID' => $fields['LID']
        ));

        if ($item) {
            return $item['ID'];
        }

        return $this->addEventType($eventName, $fields);
    }

    /**
     * Добавляет почтовый шаблон если его не существует
     * @param $eventName
     * @param $fields , обязательные параметры - тема сообщения, id сайта
     * @return bool|int
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function addEventMessageIfNotExists($eventName, $fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('SUBJECT', 'LID'));

        $item = $this->getEventMessage(array(
            'EVENT_NAME' => $eventName,
            'SUBJECT' => $fields['SUBJECT'],
        ));

        if ($item) {
            return $item['ID'];
        }

        return $this->addEventMessage($eventName, $fields);
    }

    /**
     * Обновляет почтовые шаблоны по типу почтового события или фильтру
     * @param $eventName
     * @param $fields
     * @return bool
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function updateEventMessage($eventName, $fields) {
        $items = $this->getEventMessages($eventName);

        foreach ($items as $item) {
            $this->updateEventMessageById($item["ID"], $fields);
        }

        return true;
    }

    /**
     * Обновляет почтовый шаблон по id
     * @param $id
     * @param $fields
     * @return mixed
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function updateEventMessageById($id, $fields) {
        $event = new \CEventMessage;
        if ($event->Update($id, $fields)) {
            return $id;
        }

        $this->throwException(__METHOD__, $event->LAST_ERROR);
    }

    /**
     * Обновляет тип почтового события по id
     * @param $id
     * @param $fields
     * @return mixed
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function updateEventTypeById($id, $fields) {
        $event = new \CEventType();
        if ($event->Update(array('ID' => $id), $fields)) {
            return $id;
        }

        $this->throwException(__METHOD__, 'event type not updated');
    }

    /**
     * Сохраняет почтовый шаблон
     * Создаст если не было, обновит если существует и отличается
     * @param $eventName
     * @param $fields , обязательные параметры - тема сообщения, id сайта
     * @return bool|int|mixed
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function saveEventMessage($eventName, $fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('SUBJECT', 'LID'));

        $exists = $this->getEventMessage(array(
            'EVENT_NAME' => $eventName,
            'SUBJECT' => $fields['SUBJECT'],
        ));

        $exportExists = $this->prepareExportEventMessage($exists);
        $fields = $this->prepareExportEventMessage($fields);

        if (empty($exists)) {
            $ok = $this->getMode('test') ? true : $this->addEventMessage($eventName, $fields);
            $this->outNoticeIf($ok, 'Почтовый шаблон %s:%s: добавлен', $eventName, $fields['SUBJECT']);
            return $ok;
        }

        if ($this->hasDiff($exportExists, $fields)) {
            $ok = $this->getMode('test') ? true : $this->updateEventMessageById($exists['ID'], $fields);
            $this->outNoticeIf($ok, 'Почтовый шаблон %s:%s: обновлен', $eventName, $fields['SUBJECT']);
            $this->outDiffIf($ok, $exportExists, $fields);
            return $ok;
        }

        $ok = $this->getMode('test') ? true : $eventName;
        if ($this->getMode('out_equal')) {
            $this->outIf($ok, 'Почтовый шаблон %s:%s: совпадает', $eventName, $fields['SUBJECT']);
        }
        return $ok;
    }

    /**
     * Сохраняет тип почтового события
     * Создаст если не было, обновит если существует и отличается
     * @param $eventName
     * @param $fields , обязательные параметры - id языка
     * @return bool|int|mixed
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function saveEventType($eventName, $fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('LID'));

        $exists = $this->getEventType(array(
            'EVENT_NAME' => $eventName,
            'LID' => $fields['LID']
        ));

        $exportExists = $this->prepareExportEventType($exists);
        $fields = $this->prepareExportEventType($fields);

        if (empty($exists)) {
            $ok = $this->getMode('test') ? true : $this->addEventType($eventName, $fields);
            $this->outNoticeIf($ok, 'Тип почтового события %s:%s: добавлен', $eventName, $fields['LID']);
            return $ok;
        }

        if ($this->hasDiff($exportExists, $fields)) {
            $ok = $this->getMode('test') ? true : $this->updateEventTypeById($exists['ID'], $fields);
            $this->outNoticeIf($ok, 'Тип почтового события %s:%s: обновлен', $eventName, $fields['LID']);
            $this->outDiffIf($ok, $exportExists, $fields);
            return $ok;
        }

        $ok = $this->getMode('test') ? true : $eventName;
        if ($this->getMode('out_equal')) {
            $this->outIf($ok, 'Тип почтового события %s:%s: совпадает', $eventName, $fields['LID']);
        }
        return $ok;
    }

    /**
     * Удаляет тип почтового события
     * @param $fields , обязательные параметры - id языка, тип события
     * @return bool
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function deleteEventType($fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('LID', 'EVENT_NAME'));

        $exists = $this->getEventType(array(
            'EVENT_NAME' => $fields['EVENT_NAME'],
            'LID' => $fields['LID']
        ));

        if (empty($exists)) {
            return false;
        }

        if (\CEventType::Delete(array("ID" => $exists['ID']))) {
            return true;
        }

        $this->throwException(__METHOD__, 'Could not delete event type %s:%s', $fields['EVENT_NAME'], $fields['LID']);
    }

    /**
     * Удаляет почтовый шаблон
     * @param $fields , обязательные параметры - тема сообщения языка, тип события
     * @return bool
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function deleteEventMessage($fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('SUBJECT', 'EVENT_NAME'));

        $exists = $this->getEventMessage(array(
            'EVENT_NAME' => $fields['EVENT_NAME'],
            'SUBJECT' => $fields['SUBJECT']
        ));

        if (empty($exists)) {
            return false;
        }

        if (\CEventMessage::Delete($exists['ID'])) {
            return true;
        };

        $this->throwException(__METHOD__, 'Could not delete event message %s:%s', $fields['EVENT_NAME'], $fields['SUBJECT']);
    }

    /**
     * Добавляет тип почтового события
     * @param $eventName
     * @param $fields , обязательные параметры - id языка, название события
     * @return bool|int
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function addEventType($eventName, $fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('LID', 'NAME'));
        $fields['EVENT_NAME'] = $eventName;

        $event = new \CEventType;
        $id = $event->Add($fields);

        if ($id) {
            return $id;
        }

        $this->throwException(__METHOD__, 'Event type %s not added', $eventName);
    }

    /**
     * Добавляет почтовый шаблон
     * @param $eventName
     * @param $fields , обязательные параметры - id сайта, тема сообщения
     * @return bool|int
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function addEventMessage($eventName, $fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('LID', 'SUBJECT'));

        $default = array(
            'ACTIVE' => 'Y',
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => '#EMAIL_TO#',
            'BCC' => '',
            'BODY_TYPE' => 'text',
            'MESSAGE' => '',
        );

        $fields = array_merge($default, $fields);
        $fields['EVENT_NAME'] = $eventName;

        $event = new \CEventMessage;
        $id = $event->Add($fields);

        if ($id) {
            return $id;
        }

        $this->throwException(__METHOD__, 'Event message %s not added, error: %s', $eventName, $event->LAST_ERROR);
    }

    /**
     * @deprecated use updateEventMessage
     * @param $filter
     * @param $fields
     * @return bool
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function updateEventMessageByFilter($filter, $fields) {
        return $this->updateEventMessage($filter, $fields);
    }

    protected function prepareEventMessage($item) {
        if (empty($item['ID'])) {
            return $item;
        }
        $item['LID'] = $this->getEventMessageSites($item['ID']);
        return $item;
    }

    protected function prepareExportEventType($item) {
        if (empty($item)) {
            return $item;
        }

        unset($item['ID']);
        unset($item['EVENT_NAME']);

        return $item;
    }

    protected function prepareExportEventMessage($item) {
        if (empty($item)) {
            return $item;
        }

        unset($item['ID']);
        unset($item['SITE_ID']);
        unset($item['TIMESTAMP_X']);
        unset($item['MESSAGE_PHP']);
        unset($item['EVENT_NAME']);
        unset($item['EVENT_MESSAGE_TYPE_ID']);
        unset($item['EVENT_MESSAGE_TYPE_NAME']);
        unset($item['EVENT_MESSAGE_TYPE_EVENT_NAME']);

        return $item;
    }
}