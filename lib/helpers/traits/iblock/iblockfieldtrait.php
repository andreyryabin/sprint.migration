<?php

namespace Sprint\Migration\Helpers\Traits\Iblock;

use CIBlock;
use Sprint\Migration\Locale;

trait IblockFieldTrait
{
    /**
     * Получает список полей инфоблока
     */
    public function getIblockFields(int $iblockId): array
    {
        $fields = CIBlock::GetFields($iblockId);

        return is_array($fields) ? $fields : [];
    }

    /**
     * Сохраняет поля инфоблока
     */
    public function saveIblockFields(int $iblockId, array $fields = []): bool
    {
        $exists = CIBlock::GetFields($iblockId);

        $exportExists = $this->prepareExportIblockFields($exists);
        $fields = $this->prepareExportIblockFields($fields);

        $fields = array_replace_recursive($exportExists, $fields);

        if (empty($exists)) {
            return $this->updateIblockFields($iblockId, $fields);
        }

        if ($this->checkDiff($exportExists, $fields)) {
            return $this->updateIblockFields($iblockId, $fields);
        }

        return true;
    }

    /**
     * Получает список полей инфоблока
     * Данные подготовлены для экспорта в миграцию или схему
     */
    public function exportIblockFields(int $iblockId): array
    {
        return $this->prepareExportIblockFields(
            $this->getIblockFields($iblockId)
        );
    }

    /**
     * Обновляет поля инфоблока
     */
    public function updateIblockFields(int $iblockId, array $fields): bool
    {
        if ($iblockId && !empty($fields)) {
            CIBlock::SetFields($iblockId, $fields);
            $this->outNotice(Locale::getMessage('IB_FIELDS_UPDATED', ['#NAME#' => $iblockId]));
            return true;
        }
        return false;
    }

    public function exportIblockElementFields(int $iblockId): array
    {
        return $this->prepareExportIblockElementFields(
            $this->getIblockFields($iblockId)
        );
    }

    protected function prepareExportIblockFields(array $fields): array
    {
        return array_filter($fields, function ($field) {
            return ($field['VISIBLE'] != 'N');
        });
    }

    protected function prepareExportIblockElementFields(array $fields): array
    {
        return array_filter($fields, function ($field, $code) {
            return !($field['VISIBLE'] == 'N' || preg_match('/^(SECTION_|LOG_)/', $code));
        }, ARRAY_FILTER_USE_BOTH);
    }
}
