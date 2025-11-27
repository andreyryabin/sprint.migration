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
use Bitrix\Sale\Internals\OrderPropsVariantTable;

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
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     */
    public function getOrderPropertyById(int $id): array
    {
        return OrderPropsTable::getById($id)->fetch();
    }

    /**
     * @throws SystemException
     * @throws ArgumentException
     */
    public function getOrderPropertyByIdentifierField(string $personTypeId, string $identifierField, string $value): array
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
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     */
    public function getOrderPropertyVariants(array $propertyIds): array
    {
        $result = [];

        $dbPropertyVariants = OrderPropsVariantTable::query()
            ->setSelect(['*'])
            ->whereIn('ORDER_PROPS_ID', $propertyIds)
            ->exec();
        while ($propertyVariant = $dbPropertyVariants->fetch()) {
            $result[$propertyVariant['ORDER_PROPS_ID']][] = $propertyVariant;
        }

        return $result;
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

        return (int) $result->getId();
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

        return (int) $result->getId();
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

            $identifierField = strtoupper($identifierField);

            $exists = $this->getOrderPropertyByIdentifierField(
                (string) $property['PERSON_TYPE_ID'],
                $identifierField,
                (string) $property[$identifierField]
            );

            if (empty($exists)) {
                $result = $this->addOrderProperty($property);
            } else {
                $result = $this->updateOrderProperty((int) $exists['ID'], $property);
                $this->outDiff($this->prepareProperty($exists), $property);
            }

        } else {
            $result = $this->addOrderProperty($property);
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    private function addPropertyVariant(array $fields): void
    {
        $result = OrderPropsVariantTable::add($fields);
        if(!$result->isSuccess()) {
            throw new HelperException(
                Locale::getMessage(
                    'ERR_ORDER_PROPERTY_VARIANT_NOT_ADDED',
                    [
                        '#NAME#' => $fields['NAME'],
                        '#MESSAGE#' => implode(', ', $result->getErrorMessages())
                    ]
                )
            );
        }

        $this->outNotice(Locale::getMessage('ORDER_PROPERTY_VARIANT_ADDED', ['#NAME#' => $fields['NAME']]));
    }

    /**
     * @throws HelperException
     * @throws Exception
     */
    private function updatePropertyVariant(int $id, array $fields): void
    {
        $result = OrderPropsVariantTable::update($id, $fields);
        if(!$result->isSuccess()) {
            throw new HelperException(
                Locale::getMessage(
                    'ERR_ORDER_PROPERTY_VARIANT_NOT_UPDATED',
                    [
                        '#NAME#' => $fields['NAME'],
                        '#MESSAGE#' => implode(', ', $result->getErrorMessages())
                    ]
                )
            );
        }

        $this->outNotice(Locale::getMessage('ORDER_PROPERTY_VARIANT_UPDATED', ['#NAME#' => $fields['NAME']]));
    }

    /**
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws HelperException
     * @throws Exception
     */
    public function saveOrderPropertyVariants(int $propertyId, array $variants): void
    {
        $property = $this->getOrderPropertyById($propertyId);
        if(!$property) {
            return;
        }

        $existsVariants = array_reduce(
            current($this->getOrderPropertyVariants([$propertyId])) ?: [],
            function($carry, $item) {
                $carry[$item['VALUE']] = $item;
                return $carry;
            },
            []
        );

        foreach ($variants as $variant) {
            $variant = array_merge(
                $this->preparePropertyVariant($variant),
                ['ORDER_PROPS_ID' => $propertyId]
            );

            $this->checkRequiredKeys($variant, ['ORDER_PROPS_ID', 'NAME']);

            if($existsVariant = $existsVariants[$variant['VALUE']]) {
                $this->updatePropertyVariant((int) $existsVariant['ID'], $variant);
            } else {
                $this->addPropertyVariant($variant);
            }
        }
    }

    protected function prepareProperty(array $item): array
    {
        $this->unsetKeys($item, ['ID']);

        return $item;
    }

    protected function preparePropertyVariant(array $item): array
    {
        $this->unsetKeys($item, ['ID', 'ORDER_PROPS_ID']);

        return $item;
    }
}