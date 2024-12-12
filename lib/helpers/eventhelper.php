<?php

namespace Sprint\Migration\Helpers;

use CEventMessage;
use CEventType;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;
use Sprint\Migration\Support\ExportRules;

class EventHelper extends Helper
{
    /**
     * Получает тип почтового события по фильтру или типу почтового события
     *
     * @param $eventName
     *
     * @return array
     */
    public function getEventType($eventName)
    {
        $filter = is_array($eventName)
            ? $eventName
            : [
                'EVENT_NAME' => $eventName,
            ];

        $dbres = CEventType::GetList($filter);
        return $dbres->Fetch();
    }

    /**
     * Получает список типов почтовых событий по фильтру или типу почтового события
     *
     * @param $eventName
     *
     * @return array
     */
    public function getEventTypes($eventName)
    {
        $filter = is_array($eventName)
            ? $eventName
            : [
                'EVENT_NAME' => $eventName,
            ];

        $dbres = CEventType::GetList($filter);
        return $this->fetchAll($dbres);
    }

    /**
     * Получает почтовый шаблон по фильтру или типу почтового события
     *
     * @param $eventName
     *
     * @return mixed
     */
    public function getEventMessage($eventName)
    {
        $filter = is_array($eventName)
            ? $eventName
            : [
                'EVENT_NAME' => $eventName,
            ];

        $by = 'id';
        $order = 'asc';

        if (isset($filter['EVENT_NAME'])) {
            $filter['EVENT_NAME_EXACT_MATCH'] = 'Y';
        }
        if (isset($filter['SUBJECT'])) {
            $filter['SUBJECT_EXACT_MATCH'] = 'Y';
        }

        $item = CEventMessage::GetList($by, $order, $filter)->Fetch();
        return $this->prepareEventMessage($item);
    }

    /**
     * Получает почтовые шаблоны по фильтру или типу почтового события
     *
     * @param $eventName
     *
     * @return array
     */
    public function getEventMessages($eventName)
    {
        $filter = is_array($eventName)
            ? $eventName
            : [
                'EVENT_NAME' => $eventName,
            ];

        $by = 'id';
        $order = 'asc';

        if (isset($filter['EVENT_NAME'])) {
            $filter['EVENT_NAME_EXACT_MATCH'] = 'Y';
        }
        if (isset($filter['SUBJECT'])) {
            $filter['SUBJECT_EXACT_MATCH'] = 'Y';
        }

        $result = [];
        $dbres = CEventMessage::GetList($by, $order, $filter);
        while ($item = $dbres->Fetch()) {
            $result[] = $this->prepareEventMessage($item);
        }
        return $result;
    }

    public function getEventMessageById($messageId)
    {
        $item = CEventMessage::GetByID($messageId)->Fetch();
        if ($item) {
            return $this->prepareEventMessage($item);
        }
        return false;
    }

    public function getEventMessageUidFilterById($messageId)
    {
        $item = CEventMessage::GetByID($messageId)->Fetch();
        if ($item) {
            return [
                'EVENT_NAME' => $item['EVENT_NAME'],
                'SUBJECT'    => $item['SUBJECT'],
            ];
        }
        return $messageId;
    }

    public function getEventMessageIdByUidFilter($templateId)
    {
        if (empty($templateId)) {
            return false;
        }

        if (is_numeric($templateId)) {
            return $templateId;
        }

        if (is_array($templateId) && isset($templateId['EVENT_NAME'])) {
            $item = $this->getEventMessage($templateId);
            if ($item) {
                return $item['ID'];
            }
        }

        return false;
    }

    /**
     * Получает список сайтов для почтового шаблона
     *
     * @param $messageId
     *
     * @return array
     */
    public function getEventMessageSites($messageId)
    {
        $dbres = CEventMessage::GetLang($messageId);
        return $this->fetchAll($dbres, false, 'LID');
    }

    /**
     * Получает почтовые шаблоны по фильтру или типу почтового события
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @param $eventName
     *
     * @return array
     */
    public function exportEventMessages($eventName)
    {
        $exports = [];
        $items = $this->getEventMessages($eventName);
        foreach ($items as $item) {
            $exports[] = $this->prepareExportEventMessage($item);
        }
        return $exports;
    }

    /**
     * Получает список типов почтовых событий по фильтру или типу почтового события
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @param $eventName
     *
     * @return array
     */
    public function exportEventTypes($eventName)
    {
        $exports = [];
        $items = $this->getEventTypes($eventName);
        foreach ($items as $item) {
            $exports[] = $this->prepareExportEventType($item);
        }
        return $exports;
    }

    /**
     * Добавляет тип почтового события если его не существует
     *
     * @param       $eventName
     * @param array $fields
     *
     * @throws HelperException
     * @return bool|int|mixed
     */
    public function addEventTypeIfNotExists($eventName, $fields)
    {
        $this->checkRequiredKeys($fields, ['LID']);

        $item = $this->getEventType([
            'EVENT_NAME' => $eventName,
            'LID'        => $fields['LID'],
        ]);

        if ($item) {
            return $item['ID'];
        }

        return $this->addEventType($eventName, $fields);
    }

    /**
     * Добавляет почтовый шаблон если его не существует
     *
     * @param       $eventName
     * @param array $fields
     *
     * @throws HelperException
     * @return bool|int
     */
    public function addEventMessageIfNotExists($eventName, $fields)
    {
        $this->checkRequiredKeys($fields, ['SUBJECT', 'LID']);

        $item = $this->getEventMessage(
            [
                'EVENT_NAME' => $eventName,
                'SUBJECT'    => $fields['SUBJECT'],
            ]
        );

        if ($item) {
            return $item['ID'];
        }

        return $this->addEventMessage($eventName, $fields);
    }

    /**
     * Обновляет почтовые шаблоны по типу почтового события или фильтру
     *
     * @param $eventName
     * @param $fields
     *
     * @throws HelperException
     * @return bool
     */
    public function updateEventMessage($eventName, $fields)
    {
        $items = $this->getEventMessages($eventName);

        foreach ($items as $item) {
            $this->updateEventMessageById($item["ID"], $fields);
        }

        return true;
    }

    /**
     * Обновляет почтовый шаблон по id
     *
     * @param $id
     * @param $fields
     *
     * @throws HelperException
     * @return mixed
     */
    public function updateEventMessageById($id, $fields)
    {
        $event = new CEventMessage;

        //Удаление "лишних" значений из массива, наличие которых вызовет ошибку при \CAllEventMessage::Update() (bitrix\modules\main\classes\general\event.php#355)
        //Код удаления взят из соседнего метода \CAllEventMessage::Add() (bitrix\modules\main\classes\general\event.php#310), который сам удаляет эти значения,
        //а в \CAllEventMessage::Update() Битрикс видимо забыл это перенести

        unset($fields['EVENT_MESSAGE_TYPE_ID']);
        unset($fields['EVENT_MESSAGE_TYPE_NAME']);
        unset($fields['EVENT_MESSAGE_TYPE_EVENT_NAME']);
        unset($fields['SITE_ID']);
        unset($fields['EVENT_TYPE']);

        if ($event->Update($id, $fields)) {
            return $id;
        }

        throw new HelperException($event->LAST_ERROR);
    }

    /**
     * Обновляет тип почтового события по id
     *
     * @param $id
     * @param $fields
     *
     * @throws HelperException
     * @return mixed
     */
    public function updateEventTypeById($id, $fields)
    {
        $event = new CEventType();
        if ($event->Update(['ID' => $id], $fields)) {
            return $id;
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_EVENT_TYPE_NOT_UPDATED'
            )
        );
    }

    /**
     * Сохраняет почтовый шаблон
     * Создаст если не было, обновит если существует и отличается
     *
     * @param       $eventName
     * @param array $fields
     *
     * @throws HelperException
     * @return bool|int|mixed
     */
    public function saveEventMessage($eventName, $fields)
    {
        $this->checkRequiredKeys($fields, ['SUBJECT', 'LID']);

        $exists = $this->getEventMessage(
            [
                'EVENT_NAME' => $eventName,
                'SUBJECT'    => $fields['SUBJECT'],
            ]
        );

        $fields = $this->prepareExportEventMessage($fields);

        if (empty($exists)) {
            $ok = $this->getMode('test') ? true : $this->addEventMessage($eventName, $fields);
            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'EVENT_MESSAGE_CREATED',
                    [
                        '#NAME#' => $eventName . ':' . $fields['SUBJECT'],
                    ]
                )
            );
            return $ok;
        }

        $exportExists = $this->prepareExportEventMessage($exists);

        if ($this->hasDiff($exportExists, $fields)) {
            $ok = $this->getMode('test') ? true : $this->updateEventMessageById($exists['ID'], $fields);

            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'EVENT_MESSAGE_UPDATED',
                    [
                        '#NAME#' => $eventName . ':' . $fields['SUBJECT'],
                    ]
                )
            );

            $this->outDiffIf($ok, $exportExists, $fields);
            return $ok;
        }

        return $this->getMode('test') ? true : $eventName;
    }

    /**
     * Сохраняет тип почтового события
     * Создаст если не было, обновит если существует и отличается
     *
     * @param       $eventName
     * @param array $fields
     *
     * @throws HelperException
     * @return bool|int|mixed
     */
    public function saveEventType($eventName, $fields)
    {
        $this->checkRequiredKeys($fields, ['LID']);

        $exists = $this->getEventType([
            'EVENT_NAME' => $eventName,
            'LID'        => $fields['LID'],
        ]);

        $fields = $this->prepareExportEventType($fields);

        if (empty($exists)) {
            $ok = $this->getMode('test') ? true : $this->addEventType($eventName, $fields);

            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'EVENT_TYPE_CREATED',
                    [
                        '#NAME#' => $eventName . ':' . $fields['LID'],
                    ]
                )
            );

            return $ok;
        }

        $exportExists = $this->prepareExportEventType($exists);

        if ($this->hasDiff($exportExists, $fields)) {
            $ok = $this->getMode('test') ? true : $this->updateEventTypeById($exists['ID'], $fields);

            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'EVENT_TYPE_UPDATED',
                    [
                        '#NAME#' => $eventName . ':' . $fields['LID'],
                    ]
                )
            );

            $this->outDiffIf($ok, $exportExists, $fields);
            return $ok;
        }

        return $this->getMode('test') ? true : $eventName;
    }

    /**
     * Удаляет тип почтового события
     *
     * @param array $fields
     *
     * @throws HelperException
     * @return bool
     */
    public function deleteEventType($fields)
    {
        $this->checkRequiredKeys($fields, ['LID', 'EVENT_NAME']);

        $exists = $this->getEventType([
            'EVENT_NAME' => $fields['EVENT_NAME'],
            'LID'        => $fields['LID'],
        ]);

        if (empty($exists)) {
            return false;
        }

        if (CEventType::Delete(["ID" => $exists['ID']])) {
            return true;
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_CANT_DELETE_EVENT_TYPE',
                [
                    '#NAME#' => $fields['EVENT_NAME'] . ':' . $fields['LID'],
                ]
            )
        );
    }

    /**
     * Удаляет почтовый шаблон
     *
     * @param array $fields
     *
     * @throws HelperException
     * @return bool
     */
    public function deleteEventMessage($fields)
    {
        $this->checkRequiredKeys($fields, ['SUBJECT', 'EVENT_NAME']);

        $exists = $this->getEventMessage(
            [
                'EVENT_NAME' => $fields['EVENT_NAME'],
                'SUBJECT'    => $fields['SUBJECT'],
            ]
        );

        if (empty($exists)) {
            return false;
        }

        if (CEventMessage::Delete($exists['ID'])) {
            return true;
        };

        throw new HelperException(
            Locale::getMessage(
                'ERR_CANT_DELETE_EVENT_MESSAGE',
                [
                    '#NAME#' => $fields['EVENT_NAME'] . ':' . $fields['SUBJECT'],
                ]
            )
        );
    }

    /**
     * Добавляет тип почтового события
     *
     * @param       $eventName
     * @param array $fields
     *
     * @throws HelperException
     * @return bool|int
     */
    public function addEventType($eventName, $fields)
    {
        $this->checkRequiredKeys($fields, ['LID', 'NAME']);
        $fields['EVENT_NAME'] = $eventName;

        $event = new CEventType;
        $id = $event->Add($fields);

        if ($id) {
            return $id;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException(
            Locale::getMessage(
                'ERR_EVENT_TYPE_NOT_ADDED',
                [
                    '#NAME#' => $eventName,
                ]
            )
        );
    }

    /**
     * Добавляет почтовый шаблон
     *
     * @param       $eventName
     * @param array $fields
     *
     * @throws HelperException
     * @return bool|int
     */
    public function addEventMessage($eventName, $fields)
    {
        $this->checkRequiredKeys($fields, ['LID', 'SUBJECT']);

        $default = [
            'ACTIVE'     => 'Y',
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO'   => '#EMAIL_TO#',
            'BCC'        => '',
            'BODY_TYPE'  => 'text',
            'MESSAGE'    => '',
        ];

        $fields = array_merge($default, $fields);
        $fields['EVENT_NAME'] = $eventName;

        $event = new CEventMessage;
        $id = $event->Add($fields);

        if ($id) {
            return $id;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException(
            Locale::getMessage(
                'ERR_EVENT_MESSAGE_NOT_ADDED',
                [
                    '#NAME#' => $eventName,
                ]
            )
        );
    }

    /**
     * @param $filter
     * @param $fields
     *
     * @throws HelperException
     * @return bool
     * @deprecated use updateEventMessage
     */
    public function updateEventMessageByFilter($filter, $fields)
    {
        return $this->updateEventMessage($filter, $fields);
    }

    protected function prepareEventMessage($item)
    {
        if (empty($item['ID'])) {
            return $item;
        }
        $item['LID'] = $this->getEventMessageSites($item['ID']);
        return $item;
    }

    protected function prepareExportEventType($item)
    {
        if (empty($item)) {
            return $item;
        }

        unset($item['ID']);
        unset($item['EVENT_NAME']);

        return $item;
    }

    protected function prepareExportEventMessage($item)
    {
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
