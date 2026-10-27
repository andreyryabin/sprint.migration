<?php

namespace Sprint\Migration\Helpers;

use CLanguage;
use Exception;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;

class LangHelper extends Helper
{

    public function getLangs(array $filter = []): array
    {
        $by = 'def';
        $order = 'desc';

        return $this->fetchAll(CLanguage::GetList($by, $order, $filter));
    }

    /**
     * @throws HelperException
     */
    public function getLangsIfExists(array $filter = []): array
    {

        $items = $this->getLangs($filter);
        if (!empty($items)) {
            return $items;
        }

        throw new HelperException(Locale::getMessage('ERR_LANGUAGES_NOT_FOUND'));
    }

    public function getDefaultLang(): array|false
    {
        $by = 'def';
        $order = 'desc';

        return CLanguage::GetList($by, $order, ['ACTIVE' => 'Y'])->Fetch();
    }

    /**
     * @throws HelperException
     */
    public function getDefaultLangIdIfExists(): string
    {

        $item = $this->getDefaultLang();
        if (isset($item['LID'])) {
            return (string)$item['LID'];
        }

        throw new HelperException(Locale::getMessage('ERR_DEFAULT_LANGUAGE_NOT_FOUND'));
    }

    public function exportLangs(array $filter = []): array
    {
        return array_map(
            fn($item) => $this->exportItem($item),
            $this->getLangs($filter)
        );
    }

    /**
     * @throws HelperException
     */
    private function exportItem(array $item): array
    {
        $this->unsetKeys($item, [
            'ID',
            'LANGUAGE_ID',
            'FORMAT_DATE',
            'FORMAT_DATETIME',
            'FORMAT_NAME',
            'WEEK_START',
            'CHARSET',
            'DIRECTION',
        ]);

        if (isset($item['CULTURE_ID'])) {
            $item['CULTURE_CODE'] = (new CultureHelper)->getCultureCodeById($item['CULTURE_ID']);
            unset($item['CULTURE_ID']);
        }

        return $item;
    }

    /**
     * @throws HelperException
     */
    public function saveLang(array $fields): string
    {
        $this->checkRequiredKeys($fields, ['LID']);

        $exists = $this->getLangByLid($fields['LID']);

        if (empty($exists)) {
            return $this->addLang($fields);
        }

        if ($this->checkDiff($this->exportItem($exists), $fields)) {
            return $this->updateLang($exists['LID'], $fields);
        }

        return $exists['LID'];

    }

    /**
     * @throws HelperException
     */
    public function addLang(array $fields): string
    {
        $this->checkRequiredKeys($fields, ['LID']);
        try {

            if (isset($fields['CULTURE_CODE'])) {
                $fields['CULTURE_ID'] = (new CultureHelper)->getCultureIdByCode($fields['CULTURE_CODE']);
                unset($fields['CULTURE_CODE']);
            }

            $langId = (new CLanguage)->Add($fields);

            if ($langId) {
                $this->outNotice(Locale::getMessage('LANG_CREATED', ['#LID#' => $fields['LID']]));
                return (string)$langId;
            }

            $this->throwApplicationExceptionIfExists();
            throw new HelperException("Language \"{$fields['LID']}\" not created");
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getLangByLid(string $langId): array|false
    {
        return CLanguage::GetByID($langId)->Fetch();
    }

    /**
     * @throws HelperException
     */
    public function updateLang(string $langId, array $fields): string
    {
        try {

            if (isset($fields['CULTURE_CODE'])) {
                $fields['CULTURE_ID'] = (new CultureHelper)->getCultureIdByCode($fields['CULTURE_CODE']);
                unset($fields['CULTURE_CODE']);
            }

            $result = (new CLanguage)->Update($langId, $fields);

            if ($result) {
                $this->outNotice(Locale::getMessage('LANG_UPDATED', ['#CODE#' => $fields['CODE']]));
                return $langId;
            }

            $this->throwApplicationExceptionIfExists();
            throw new HelperException("Language \"{$fields['LID']}\" not updated");
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
