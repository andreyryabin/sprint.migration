<?php

namespace Sprint\Migration\Helpers;

use CRubric;
use CAgent;
use COption;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;

class SubscribeHelper extends Helper
{
    public function isEnabled(): bool
    {
        return $this->checkModules(['subscribe']);
    }

    public function getRubrics(array $filter = []): array
    {
        $dbres = CRubric::GetList(
            [
                'LID'  => 'ASC',
                'SORT' => 'ASC',
                'NAME' => 'ASC',
                'ID'   => 'ASC',
            ],
            $filter
        );

        return $this->fetchAll($dbres);
    }

    public function getRubricById(int $rubricId): bool|array
    {
        return CRubric::GetByID($rubricId)->Fetch();
    }

    public function getRubricId(string $lid, string $code): int
    {
        if (empty($lid) || empty($code)) {
            return 0;
        }

        $item = CRubric::GetList(
            ['ID' => 'ASC'],
            [
                'LID'  => $lid,
                'CODE' => $code,
            ]
        )->Fetch();

        return (int)($item['ID'] ?? 0);
    }

    /**
     * @throws HelperException
     */
    public function exportRubricById(int $rubricId): array
    {
        $item = $this->getRubricById($rubricId);

        if (!empty($item)) {
            return $this->prepareExportRubric($item);
        }

        throw new HelperException("Subscribe rubric with ID=$rubricId not found");
    }

    /**
     * @throws HelperException
     */
    public function exportRubrics(array $rubricIds): array
    {
        return array_map(
            fn($rubricId) => $this->exportRubricById($rubricId),
            $this->makeNonEmptyArray($rubricIds)
        );
    }

    /**
     * @throws HelperException
     */
    public function saveRubric(array $fields): int
    {
        $this->checkRequiredKeys($fields, ['LID', 'CODE', 'NAME']);

        $fields = $this->prepareRubricFields($fields);
        $rubricId = $this->getRubricId($fields['LID'], $fields['CODE']);

        if (!$rubricId) {
            return $this->addRubric($fields);
        }

        $exists = $this->prepareExportRubric($this->getRubricById($rubricId));
        if ($this->checkDiff($exists, $this->prepareExportRubric($fields))) {
            return $this->updateRubric($rubricId, $fields);
        }

        return $rubricId;
    }

    /**
     * @throws HelperException
     */
    public function addRubric(array $fields): int
    {
        $this->checkRequiredKeys($fields, ['LID', 'CODE', 'NAME']);

        $rubric = new CRubric();
        $fields = $this->prepareRubricFieldsForSave($fields);
        $this->preparePostingTemplateAgent($fields);
        $rubricId = $rubric->Add($fields);

        if ($rubricId) {
            $this->outNotice(Locale::getMessage('SUBSCRIBE_RUBRIC_UPDATED', ['#NAME#' => $fields['NAME']]));
            return (int)$rubricId;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException($rubric->LAST_ERROR ?: "Subscribe rubric \"{$fields['NAME']}\" not added");
    }

    /**
     * @throws HelperException
     */
    public function updateRubric(int $rubricId, array $fields): int
    {
        $this->checkRequiredKeys($fields, ['LID', 'CODE', 'NAME']);

        $rubric = new CRubric();
        $fields = $this->prepareRubricFieldsForSave($fields);
        $this->preparePostingTemplateAgent($fields);
        $result = $rubric->Update($rubricId, $fields);

        if ($result) {
            $this->outNotice(Locale::getMessage('SUBSCRIBE_RUBRIC_UPDATED', ['#NAME#' => $fields['NAME']]));
            return $rubricId;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException($rubric->LAST_ERROR ?: "Subscribe rubric \"{$fields['NAME']}\" not updated");
    }

    public function deleteRubric(int $rubricId): bool
    {
        return (bool)CRubric::Delete($rubricId);
    }

    public function deleteRubricIfExists(string $lid, string $code): bool
    {
        $rubricId = $this->getRubricId($lid, $code);
        if (!$rubricId) {
            return false;
        }

        return $this->deleteRubric($rubricId);
    }

    protected function prepareExportRubric(array $item): array
    {
        $this->checkRequiredKeys($item, ['LID', 'CODE', 'NAME']);

        $item = $this->prepareRubricFields($item);

        $this->unsetKeys($item, [
            'ID',
            'LAST_EXECUTED',
        ]);

        return $item;
    }

    protected function preparePostingTemplateAgent(array $item): void
    {
        if (
            ($item['ACTIVE'] ?? '') === 'Y'
            && ($item['AUTO'] ?? '') === 'Y'
            && COption::GetOptionString('subscribe', 'subscribe_template_method') !== 'cron'
        ) {
            CAgent::RemoveAgent('CPostingTemplate::Execute();', 'subscribe');
        }
    }

    protected function prepareRubricFieldsForSave(array $item): array
    {
        $item = $this->prepareRubricFields($item);

        if (($item['AUTO'] ?? '') === 'Y' && empty($item['LAST_EXECUTED'])) {
            $item['LAST_EXECUTED'] = ConvertTimeStamp(time(), 'FULL');
        }

        return $item;
    }

    protected function prepareRubricFields(array $item): array
    {
        $default = [
            'DESCRIPTION'   => '',
            'SORT'          => 100,
            'ACTIVE'        => 'Y',
            'AUTO'          => 'N',
            'DAYS_OF_MONTH' => '',
            'DAYS_OF_WEEK'  => '',
            'TIMES_OF_DAY'  => '',
            'TEMPLATE'      => '',
            'VISIBLE'       => 'Y',
            'FROM_FIELD'    => '',
        ];

        $item = array_merge($default, $item);

        return array_intersect_key(
            $item,
            array_flip([
                'LID',
                'CODE',
                'NAME',
                'DESCRIPTION',
                'SORT',
                'ACTIVE',
                'AUTO',
                'DAYS_OF_MONTH',
                'DAYS_OF_WEEK',
                'TIMES_OF_DAY',
                'TEMPLATE',
                'VISIBLE',
                'FROM_FIELD',
                'LAST_EXECUTED',
            ])
        );
    }
}
