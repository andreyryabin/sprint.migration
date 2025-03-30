<?php

namespace Sprint\Migration\Helpers;

use Bitrix\Main\Sms\TemplateTable as SmsTemplateTable;
use CEventMessage;
use CEventType;
use Exception;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;
use Sprint\Migration\Tables\SmsTemplateSiteTable;

class EventHelper extends Helper
{
    /**
     * Получает тип почтового события по фильтру или типу почтового события
     */
    public function getEventType(array|string $eventName): array|bool
    {
        $items = $this->getEventTypes($eventName);
        return $items[0] ?? false;
    }

    /**
     * Получает почтовый шаблон по фильтру или типу почтового события
     */
    public function getEventMessage(array|string $eventName): bool|array
    {
        $items = $this->getEventMessages($eventName);

        return $items[0] ?? false;
    }

    /**
     * @throws HelperException
     */
    public function getEventSmsTemplate(array|string $eventName): bool|array
    {
        $items = $this->getEventSmsTemplates($eventName);

        return $items[0] ?? false;
    }

    /**
     * Получает список типов почтовых событий по фильтру или типу почтового события
     */
    public function getEventTypes($eventName): array
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
     * Получает почтовые шаблоны по фильтру или типу почтового события
     */
    public function getEventMessages(array|string $eventName): array
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

        $dbres = CEventMessage::GetList($by, $order, $filter);

        return array_map(
            fn($item) => $this->prepareEventMessage($item),
            $this->fetchAll($dbres)
        );
    }

    /**
     * @throws HelperException
     */
    public function getEventSmsTemplates(array|string $eventName): array
    {
        $filter = is_array($eventName)
            ? $eventName
            : [
                '=EVENT_NAME' => $eventName,
            ];

        try {
            $result = SmsTemplateTable::getList([
                'filter' => $filter
            ])->fetchAll();

            return array_map(
                fn($item) => $this->prepareEventSmsTemplate($item),
                $result
            );

        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getEventMessageById(int $messageId): bool|array
    {
        $item = CEventMessage::GetByID($messageId)->Fetch();

        return ($item) ? $this->prepareEventMessage($item) : false;
    }

    /**
     * @throws HelperException
     */
    public function getEventMessageUidFilterById(int $messageId): array
    {
        $item = CEventMessage::GetByID($messageId)->Fetch();
        if ($item) {
            return [
                'EVENT_NAME' => $item['EVENT_NAME'],
                'SUBJECT' => $item['SUBJECT'],
            ];
        }
        throw new HelperException("Event message with ID=\"$messageId\" not found");
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
     */
    public function getEventMessageSites(int $messageId): array
    {
        $dbres = CEventMessage::GetSite($messageId);
        return $this->fetchAll($dbres, false, 'LID');
    }

    /**
     * Получает список сайтов для смс
     * @throws HelperException
     */
    public function getEventSmsTemplateSites(int $templateId): array
    {
        try {
            return SmsTemplateSiteTable::getSites($templateId);
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }

    }

    /**
     * Получает почтовые шаблоны по фильтру или типу почтового события
     * Данные подготовлены для экспорта в миграцию или схему
     */
    public function exportEventMessages(string $eventName): array
    {
        return array_map(
            fn($item) => $this->prepareExportEventMessage($item),
            $this->getEventMessages($eventName)
        );
    }

    /**
     * @throws HelperException
     */
    public function exportEventSmsTemplates(string $eventName): array
    {
        return array_map(
            fn($item) => $this->prepareExportEventSmsTemplate($item),
            $this->getEventSmsTemplates($eventName)
        );
    }

    /**
     * Получает список типов почтовых событий по фильтру или типу почтового события
     * Данные подготовлены для экспорта в миграцию или схему
     */
    public function exportEventTypes(string $eventName): array
    {
        return array_map(
            fn($item) => $this->prepareExportEventType($item),
            $this->getEventTypes($eventName)
        );
    }

    /**
     * Добавляет тип почтового события если его не существует
     * @throws HelperException
     */
    public function addEventTypeIfNotExists(string $eventName, array $fields)
    {
        $this->checkRequiredKeys($fields, ['LID']);

        $item = $this->getEventType([
            'EVENT_NAME' => $eventName,
            'LID' => $fields['LID'],
        ]);

        if ($item) {
            return $item['ID'];
        }

        return $this->addEventType($eventName, $fields);
    }

    /**
     * Добавляет почтовый шаблон если его не существует
     * @throws HelperException
     */
    public function addEventMessageIfNotExists(string $eventName, array $fields): int
    {
        $this->checkRequiredKeys($fields, ['SUBJECT', 'LID']);

        $item = $this->getEventMessage(
            [
                'EVENT_NAME' => $eventName,
                'SUBJECT' => $fields['SUBJECT'],
            ]
        );

        if ($item) {
            return (int)$item['ID'];
        }

        return $this->addEventMessage($eventName, $fields);
    }

    /**
     * Обновляет почтовые шаблоны по типу почтового события или фильтру
     * @throws HelperException
     */
    public function updateEventMessage(array|string $eventName, array $fields): bool
    {
        $items = $this->getEventMessages($eventName);

        foreach ($items as $item) {
            $this->updateEventMessageById($item["ID"], $fields);
        }

        return true;
    }

    /**
     * Обновляет почтовый шаблон по id
     * @throws HelperException
     */
    public function updateEventMessageById(int $id, array $fields): int
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
            $this->outNotice(Locale::getMessage('EVENT_MESSAGE_UPDATED', ['#NAME#' => $id]));
            return $id;
        }

        throw new HelperException($event->LAST_ERROR);
    }

    /**
     * Обновляет тип почтового события по id
     * @throws HelperException
     */
    public function updateEventTypeById(int $id, array $fields): int
    {
        $event = new CEventType();
        if ($event->Update(['ID' => $id], $fields)) {
            $this->outNotice(Locale::getMessage('EVENT_TYPE_UPDATED', ['#NAME#' => $id]));
            return $id;
        }

        throw new HelperException(Locale::getMessage('ERR_EVENT_TYPE_NOT_UPDATED'));
    }

    /**
     * @throws HelperException
     */
    public function saveEventSmsTemplate(string $eventName, array $fields): int
    {
        $this->checkRequiredKeys($fields, ['SENDER', 'RECEIVER', 'MESSAGE', 'LID']);

        $exists = $this->getEventSmsTemplate([
            'EVENT_NAME' => $eventName,
            'SENDER' => $fields['SENDER'],
            'RECEIVER' => $fields['RECEIVER'],
            'MESSAGE' => $fields['MESSAGE'],
        ]);

        if (empty($exists)) {
            return $this->addEventSmsTemplate($eventName, $fields);
        }

        $exportExists = $this->prepareExportEventSmsTemplate($exists);
        if ($this->hasDiff($exportExists, $fields)) {
            $this->outDiff($this->prepareExportEventSmsTemplate($exists), $fields);
            return $this->updateEventSmsTemplate($exists['ID'], $fields);
        }

        return (int)$exists['ID'];
    }

    /**
     * @throws HelperException
     */
    public function updateEventSmsTemplate(int $templateId, array $fields): int
    {
        $siteIds = [];
        if (isset($fields['LID'])) {
            $siteIds = $fields['LID'];
            unset($fields['LID']);
        }

        try {
            SmsTemplateTable::update($templateId, $fields);

            SmsTemplateSiteTable::updateSites($templateId, $siteIds);

            $this->outNotice(Locale::getMessage('EVENT_SMS_TEMPLATE_UPDATED', ['#NAME#' => $templateId]));

            return $templateId;
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function addEventSmsTemplate(string $eventName, array $fields): int
    {
        $siteIds = [];
        if (isset($fields['LID'])) {
            $siteIds = $fields['LID'];
            unset($fields['LID']);
        }

        $fields['EVENT_NAME'] = $eventName;

        try {
            $templateId = SmsTemplateTable::add($fields)->getId();

            SmsTemplateSiteTable::updateSites($templateId, $siteIds);

            $this->outNotice(Locale::getMessage('EVENT_SMS_TEMPLATE_CREATED', ['#NAME#' => $templateId]));

            return $templateId;
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Сохраняет почтовый шаблон.
     * Создаст, если не было, обновит если существует и отличается
     * @throws HelperException
     */
    public function saveEventMessage(string $eventName, array $fields): int
    {
        $this->checkRequiredKeys($fields, ['SUBJECT', 'LID']);

        $exists = $this->getEventMessage(
            [
                'EVENT_NAME' => $eventName,
                'SUBJECT' => $fields['SUBJECT'],
            ]
        );

        $fields = $this->prepareExportEventMessage($fields);

        if (empty($exists)) {
            return $this->addEventMessage($eventName, $fields);
        }

        $exportExists = $this->prepareExportEventMessage($exists);
        if ($this->hasDiff($exportExists, $fields)) {
            $this->outDiff($exportExists, $fields);
            return $this->updateEventMessageById($exists['ID'], $fields);
        }

        return (int)$exists['ID'];
    }

    /**
     * Сохраняет тип почтового события.
     * Создаст, если не было, обновит если существует и отличается
     *
     * @throws HelperException
     */
    public function saveEventType(string $eventName, array $fields): int
    {
        $this->checkRequiredKeys($fields, ['LID']);

        $exists = $this->getEventType([
            'EVENT_NAME' => $eventName,
            'LID' => $fields['LID'],
        ]);

        $fields = $this->prepareExportEventType($fields);

        if (empty($exists)) {
            return $this->addEventType($eventName, $fields);
        }

        $exportExists = $this->prepareExportEventType($exists);
        if ($this->hasDiff($exportExists, $fields)) {
            $this->outDiff($exportExists, $fields);
            return $this->updateEventTypeById($exists['ID'], $fields);
        }

        return (int)$exists['ID'];
    }

    /**
     * Удаляет тип почтового события
     * @throws HelperException
     */
    public function deleteEventType(array $fields): bool
    {
        $this->checkRequiredKeys($fields, ['LID', 'EVENT_NAME']);

        $exists = $this->getEventType([
            'EVENT_NAME' => $fields['EVENT_NAME'],
            'LID' => $fields['LID'],
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
     * @throws HelperException
     */
    public function deleteEventMessage(array $fields): bool
    {
        $this->checkRequiredKeys($fields, ['SUBJECT', 'EVENT_NAME']);

        $exists = $this->getEventMessage(
            [
                'EVENT_NAME' => $fields['EVENT_NAME'],
                'SUBJECT' => $fields['SUBJECT'],
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
     * @throws HelperException
     */
    public function addEventType(string $eventName, array $fields): int
    {
        $this->checkRequiredKeys($fields, ['LID', 'NAME']);
        $fields['EVENT_NAME'] = $eventName;

        $event = new CEventType;
        $id = (int)$event->Add($fields);

        if ($id) {
            $this->outNotice(Locale::getMessage('EVENT_TYPE_CREATED', ['#NAME#' => $id]));
            return $id;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException(Locale::getMessage('ERR_EVENT_TYPE_NOT_ADDED', ['#NAME#' => $eventName]));
    }

    /**
     * Добавляет почтовый шаблон
     * @throws HelperException
     */
    public function addEventMessage(string $eventName, array $fields): int
    {
        $this->checkRequiredKeys($fields, ['LID', 'SUBJECT']);

        $default = [
            'ACTIVE' => 'Y',
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => '#EMAIL_TO#',
            'BCC' => '',
            'BODY_TYPE' => 'text',
            'MESSAGE' => '',
        ];

        $fields = array_merge($default, $fields);
        $fields['EVENT_NAME'] = $eventName;

        $event = new CEventMessage;
        $id = (int)$event->Add($fields);

        if ($id) {
            $this->outNotice(Locale::getMessage('EVENT_MESSAGE_CREATED', ['#NAME#' => $id]));
            return $id;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException(
            Locale::getMessage('ERR_EVENT_MESSAGE_NOT_ADDED', ['#NAME#' => $eventName])
        );
    }

    protected function prepareEventMessage(array $item): array
    {
        $item['LID'] = $this->getEventMessageSites($item['ID']);
        return $item;
    }

    /**
     * @throws HelperException
     */
    protected function prepareEventSmsTemplate(array $item): array
    {
        $item['LID'] = $this->getEventSmsTemplateSites($item['ID']);
        return $item;
    }

    protected function prepareExportEventType(array $item): array
    {
        unset($item['ID']);
        unset($item['EVENT_NAME']);

        return $item;
    }

    protected function prepareExportEventMessage(array $item): array
    {
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

    protected function prepareExportEventSmsTemplate(array $item): array
    {
        unset($item['ID']);
        unset($item['EVENT_NAME']);
        return $item;
    }
}
