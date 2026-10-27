<?php

namespace Sprint\Migration\Helpers;

use Bitrix\Main\Localization\CultureTable;
use Exception;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;

class CultureHelper extends Helper
{
    public function isEnabled(): bool
    {
        return true;
    }

    public function exportCultures(array $filter = []): array
    {
        return array_map(
            fn($item) => $this->exportItem($item),
            $this->getCultures($filter)
        );
    }

    private function exportItem(array $item): array
    {
        $this->unsetKeys($item, [
            'ID',
        ]);

        return $item;
    }

    public function getCultures(array $filter = []): array
    {
        return CultureTable::getList([
            'filter' => $filter,
        ])->fetchAll();

    }

    /**
     * @throws HelperException
     */
    public function saveCulture(array $fields): int
    {
        $this->checkRequiredKeys($fields, ['CODE']);

        $exists = $this->getCultureByCode($fields['CODE']);

        if (empty($exists)) {
            return $this->addCulture($fields);
        }

        if ($this->checkDiff($this->exportItem($exists), $fields)) {
            return $this->updateCulture($exists['ID'], $fields);
        }

        return $exists['ID'];

    }

    /**
     * @throws HelperException
     */
    public function addCulture(array $fields): int
    {
        try {
            $result = CultureTable::add($fields);

            if ($result->isSuccess()) {
                $this->outNotice(Locale::getMessage('CULTURE_CREATED', ['#CODE#' => $fields['CODE']]));

                return $result->getId();
            }

            throw new HelperException(implode(PHP_EOL, $result->getErrorMessages()));
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getCultureByCode(string $code): array|false
    {
        return CultureTable::getList([
            'filter' => ['=CODE' => $code],
            'limit'  => 1,
        ])->fetch();
    }


    /**
     * @throws HelperException
     */
    public function getCultureCodeById(int $id): string
    {
        $row = CultureTable::getRowById($id);

        if (!empty($row['CODE'])) {
            return $row['CODE'];
        }

        throw new HelperException("Culture code with id=\"$id\" not found");
    }

    /**
     * @throws HelperException
     */
    public function getCultureIdByCode(string $code)
    {
        $row = $this->getCultureByCode($code);

        if (!empty($row['ID'])) {
            return $row['ID'];
        }

        throw new HelperException("Culture id with code=\"$code\" not found");
    }

    /**
     * @throws HelperException
     */
    public function updateCulture(int $cultureId, array $fields): int
    {
        try {
            $result = CultureTable::update($cultureId, $fields);

            if ($result->isSuccess()) {
                $this->outNotice(Locale::getMessage('CULTURE_UPDATED', ['#CODE#' => $fields['CODE']]));

                return $result->getId();
            }

            throw new HelperException(implode(PHP_EOL, $result->getErrorMessages()));
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }


}
