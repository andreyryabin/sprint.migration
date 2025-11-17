<?php

namespace Sprint\Migration\Helpers;

use Exception;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;
use Bitrix\Sale\PersonTypeTable;
use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Sprint\Migration\Exceptions\HelperException;

class OrderPropertiesHelper extends Helper
{
    public function isEnabled(): bool
    {
        return $this->checkModules(['sale']);
    }

    /**
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     */
    public function getPersonTypes(): array
    {
        return PersonTypeTable::query()
            ->setSelect(['ID', 'NAME'])
            ->exec()
            ->fetchAll();
    }

    /**
     * @throws SystemException
     * @throws ArgumentException
     */
    public function getOrderPropertyByIdentifier(string $personTypeId, string $identifierField, string $value): array
    {
        return current($this->getOrderPropertiesByFilter([
            ['PERSON_TYPE_ID', '=', $personTypeId],
            [$identifierField, '=', $value],
        ])) ?: [];
    }

    /**
     * @throws SystemException
     * @throws ArgumentException
     */
    public function getOrderPropertiesByPersonType(int $personTypeId): array
    {
        return $this->getOrderPropertiesByFilter([['PERSON_TYPE_ID', '=', $personTypeId]]);
    }

    /**
     * @throws ArgumentException
     * @throws SystemException
     */
    private function getOrderPropertiesByFilter(array $filter = []): array
    {
        return OrderPropsTable::query()
            ->setSelect(['*'])
            ->where(ConditionTree::createFromArray($filter))
            ->fetchAll();
    }

    /**
     * @throws HelperException
     * @throws Exception
     */
    private function addOrderProperty(array $fields): int
    {
        $result = OrderPropsTable::add($fields);
        if(!$result->isSuccess()) {
            throw new HelperException(
                Locale::getMessage(
                    'ERR_ORDER_PROPERTY_NOT_ADDED',
                    [
                        '#NAME#' => $fields['NAME'],
                        '#MESSAGE#' => implode(', ', $result->getErrorMessages())
                    ]
                )
            );
        }

        $this->outNotice(Locale::getMessage('ORDER_PROPERTY_ADDED', ['#NAME#' => $fields['NAME']]));

        return $result->getId();
    }

    /**
     * @throws HelperException
     * @throws Exception
     */
    private function updateOrderProperty(int $id, array $fields): int
    {
        $result = OrderPropsTable::update($id, $fields);
        if(!$result->isSuccess()) {
            throw new HelperException(
                Locale::getMessage(
                    'ERR_ORDER_PROPERTY_NOT_UPDATED',
                    [
                        '#NAME#' => $fields['NAME'] ? : $id,
                        '#MESSAGE#' => implode(', ', $result->getErrorMessages())
                    ]
                )
            );
        }

        $this->outNotice(Locale::getMessage('ORDER_PROPERTY_UPDATED', ['#NAME#' => $fields['NAME']]));

        return $result->getId();
    }

    /**
     * @throws HelperException
     * @throws SystemException
     * @throws ArgumentException
     */
    public function saveOrderProperty(array $property, ?string $identifierField = null): int
    {
        $this->checkRequiredKeys($property, [
            'PERSON_TYPE_ID', 'NAME', 'TYPE' , 'PROPS_GROUP_ID', 'ENTITY_TYPE'
        ]);

        $property = $this->prepareProperty($property);

        if($identifierField) {
            $exists = $this->getOrderPropertyByIdentifier(
                (string) $property['PERSON_TYPE_ID'],
                $identifierField,
                (string) $property[$identifierField]
            );
            if (empty($exists)) {
                $result = $this->addOrderProperty($property);
            } else {
                $result = $this->updateOrderProperty((int) $exists['ID'], $property);
                $this->outDiff(
                    $this->prepareProperty($exists),
                    $property
                );
            }
        } else {
            $result = $this->addOrderProperty($property);
        }

        return $result;
    }

    protected function prepareProperty(array $item): array
    {
        $this->unsetKeys($item, ['ID']);

        return $item;
    }
}