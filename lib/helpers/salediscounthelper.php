<?php

namespace Sprint\Migration\Helpers;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Internals\DiscountGroupTable;
use Bitrix\Sale\Internals\DiscountTable;
use CIBlockElement;
use CIBlockPropertyEnum;
use CIBlockSection;
use CSaleDiscount;
use Exception;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;

class SaleDiscountHelper extends Helper
{
    public function isEnabled(): bool
    {
        return $this->checkModules(['sale']);
    }

    /**
     * @throws HelperException
     */
    public function getDiscounts(array $filter = []): array
    {
        try {
            return DiscountTable::getList([
                'select' => $this->getDiscountSelect(),
                'filter' => $filter,
                'order'  => [
                    'LID'  => 'ASC',
                    'SORT' => 'ASC',
                    'NAME' => 'ASC',
                    'ID'   => 'ASC',
                ],
            ])->fetchAll();
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function getDiscountById(int $discountId): bool|array
    {
        try {
            $item = DiscountTable::getList([
                'select' => $this->getDiscountSelect(),
                'filter' => ['=ID' => $discountId],
                'limit'  => 1,
            ])->fetch();

            return $item ?: false;
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function getDiscountId(string $lid, string $xmlId): int
    {
        if (empty($lid) || empty($xmlId)) {
            return 0;
        }

        try {
            $item = DiscountTable::getList([
                'select' => ['ID'],
                'filter' => [
                    '=LID'    => $lid,
                    '=XML_ID' => $xmlId,
                ],
                'order' => ['ID' => 'ASC'],
                'limit' => 1,
            ])->fetch();

            return (int)($item['ID'] ?? 0);
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function ensureDiscountXmlIds(array $items): array
    {
        foreach ($items as $index => $item) {
            if (empty($item['XML_ID'])) {
                $items[$index] = $this->ensureDiscountXmlId($item);
            }
        }

        return $items;
    }

    /**
     * @throws HelperException
     */
    public function ensureDiscountXmlId(array $item): array
    {
        $this->checkRequiredKeys($item, ['ID', 'LID', 'NAME']);

        if (!empty($item['XML_ID'])) {
            return $item;
        }

        $item['XML_ID'] = $this->makeDiscountXmlId($item);

        try {
            $result = DiscountTable::update((int)$item['ID'], [
                'XML_ID' => $item['XML_ID'],
            ]);

            if (!$result->isSuccess()) {
                throw new HelperException($result->getErrorMessages());
            }

            return $item;
        } catch (HelperException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function makeDiscountXmlId(array $item): string
    {
        $slug = $this->makeCodeSlug((string)$item['NAME']);
        if ($slug === '') {
            $slug = 'DISCOUNT';
        }

        return sprintf('SALE_DISCOUNT_%d_%s', (int)$item['ID'], $slug);
    }

    protected function makeCodeSlug(string $value): string
    {
        if (class_exists('CUtil')) {
            $value = \CUtil::translit($value, 'ru', [
                'replace_space' => '_',
                'replace_other' => '_',
                'change_case'   => 'U',
            ]);
        }

        $value = strtoupper($value);
        $value = preg_replace('/[^A-Z0-9_]+/', '_', $value);
        $value = trim((string)$value, '_');

        return preg_replace('/_+/', '_', $value);
    }

    /**
     * @throws HelperException
     */
    public function exportDiscountById(int $discountId): array
    {
        $item = $this->getDiscountById($discountId);

        if (!empty($item)) {
            return $this->prepareExportDiscount($item);
        }

        throw new HelperException("Sale discount with ID=$discountId not found");
    }

    /**
     * @throws HelperException
     */
    public function exportDiscounts(array $discountIds): array
    {
        return array_map(
            fn($discountId) => $this->exportDiscountById($discountId),
            $this->makeNonEmptyArray($discountIds)
        );
    }

    /**
     * @throws HelperException
     */
    public function saveDiscount(array $fields): int
    {
        $this->checkRequiredKeys($fields, ['LID', 'XML_ID', 'NAME', 'USER_GROUPS', 'CONDITIONS', 'ACTIONS']);

        $fields = $this->prepareDiscountFields($fields);
        $discountId = $this->getDiscountId($fields['LID'], $fields['XML_ID']);

        if (!$discountId) {
            return $this->addDiscount($fields);
        }

        $exists = $this->prepareExportDiscount($this->getDiscountById($discountId));
        if ($this->checkDiff($exists, $this->prepareExportDiscount($fields))) {
            return $this->updateDiscount($discountId, $fields);
        }

        return $discountId;
    }

    /**
     * @throws HelperException
     */
    public function addDiscount(array $fields): int
    {
        $this->checkRequiredKeys($fields, ['LID', 'XML_ID', 'NAME', 'USER_GROUPS', 'CONDITIONS', 'ACTIONS']);

        $fields = $this->prepareDiscountFieldsForSave($fields);
        $discountId = CSaleDiscount::Add($fields);

        if ($discountId) {
            $this->outNotice(Locale::getMessage('SALE_DISCOUNT_UPDATED', ['#NAME#' => $fields['NAME']]));
            return (int)$discountId;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException("Sale discount \"{$fields['NAME']}\" not added");
    }

    /**
     * @throws HelperException
     */
    public function updateDiscount(int $discountId, array $fields): int
    {
        $this->checkRequiredKeys($fields, ['LID', 'XML_ID', 'NAME', 'USER_GROUPS', 'CONDITIONS', 'ACTIONS']);

        $fields = $this->prepareDiscountFieldsForSave($fields);
        $result = CSaleDiscount::Update($discountId, $fields);

        if ($result) {
            $this->outNotice(Locale::getMessage('SALE_DISCOUNT_UPDATED', ['#NAME#' => $fields['NAME']]));
            return (int)$result;
        }

        $this->throwApplicationExceptionIfExists();
        throw new HelperException("Sale discount \"{$fields['NAME']}\" not updated");
    }

    public function deleteDiscount(int $discountId): bool
    {
        $discount = new CSaleDiscount();
        return (bool)$discount->Delete($discountId);
    }

    /**
     * @throws HelperException
     */
    public function deleteDiscountIfExists(string $lid, string $xmlId): bool
    {
        $discountId = $this->getDiscountId($lid, $xmlId);
        if (!$discountId) {
            return false;
        }

        return $this->deleteDiscount($discountId);
    }

    protected function getDiscountSelect(): array
    {
        return [
            'ID',
            'XML_ID',
            'LID',
            'NAME',
            'PRICE_FROM',
            'PRICE_TO',
            'CURRENCY',
            'DISCOUNT_VALUE',
            'DISCOUNT_TYPE',
            'ACTIVE',
            'SORT',
            'ACTIVE_FROM',
            'ACTIVE_TO',
            'PRIORITY',
            'LAST_DISCOUNT',
            'LAST_LEVEL_DISCOUNT',
            'VERSION',
            'CONDITIONS_LIST',
            'ACTIONS_LIST',
            'PREDICTIONS_LIST',
            'USE_COUPONS',
            'PRESET_ID',
        ];
    }

    /**
     * @throws HelperException
     */
    protected function prepareExportDiscount(array $item): array
    {
        $this->checkRequiredKeys($item, ['LID', 'XML_ID', 'NAME']);

        if (isset($item['CONDITIONS_LIST'])) {
            $item['CONDITIONS'] = $item['CONDITIONS_LIST'];
        }
        if (isset($item['ACTIONS_LIST'])) {
            $item['ACTIONS'] = $item['ACTIONS_LIST'];
        }
        if (isset($item['PREDICTIONS_LIST'])) {
            $item['PREDICTIONS'] = $item['PREDICTIONS_LIST'];
        }

        if (!isset($item['USER_GROUPS']) && !empty($item['ID'])) {
            $item['USER_GROUPS'] = $this->exportUserGroups((int)$item['ID']);
        }

        foreach (['CONDITIONS', 'ACTIONS', 'PREDICTIONS'] as $field) {
            if (!empty($item[$field]) && is_array($item[$field])) {
                $item[$field] = $this->exportConditionTree($item[$field]);
            }
        }

        $item = $this->prepareDiscountFields($item);

        $this->unsetKeys($item, [
            'ID',
            'CONDITIONS_LIST',
            'ACTIONS_LIST',
            'PREDICTIONS_LIST',
        ]);

        return $item;
    }

    /**
     * @throws HelperException
     */
    protected function prepareDiscountFields(array $item): array
    {
        $default = [
            'PRICE_FROM'          => null,
            'PRICE_TO'            => null,
            'CURRENCY'            => '',
            'DISCOUNT_VALUE'      => 0,
            'DISCOUNT_TYPE'       => 'P',
            'ACTIVE'              => 'Y',
            'SORT'                => 100,
            'ACTIVE_FROM'         => null,
            'ACTIVE_TO'           => null,
            'PRIORITY'            => 1,
            'LAST_DISCOUNT'       => 'Y',
            'LAST_LEVEL_DISCOUNT' => 'N',
            'VERSION'             => CSaleDiscount::VERSION_15,
            'CONDITIONS'          => [],
            'ACTIONS'             => [],
            'PREDICTIONS'         => [],
            'USE_COUPONS'         => 'N',
            'PRESET_ID'           => '',
            'USER_GROUPS'         => [],
        ];

        $item = array_merge($default, $item);
        $item['ACTIVE_FROM'] = $this->exportDate($item['ACTIVE_FROM']);
        $item['ACTIVE_TO'] = $this->exportDate($item['ACTIVE_TO']);
        $item['USER_GROUPS'] = array_values($item['USER_GROUPS']);

        return array_intersect_key(
            $item,
            array_flip([
                'XML_ID',
                'LID',
                'NAME',
                'PRICE_FROM',
                'PRICE_TO',
                'CURRENCY',
                'DISCOUNT_VALUE',
                'DISCOUNT_TYPE',
                'ACTIVE',
                'SORT',
                'ACTIVE_FROM',
                'ACTIVE_TO',
                'PRIORITY',
                'LAST_DISCOUNT',
                'LAST_LEVEL_DISCOUNT',
                'VERSION',
                'CONDITIONS',
                'ACTIONS',
                'PREDICTIONS',
                'USE_COUPONS',
                'PRESET_ID',
                'USER_GROUPS',
            ])
        );
    }

    /**
     * @throws HelperException
     */
    protected function prepareDiscountFieldsForSave(array $item): array
    {
        $item = $this->prepareDiscountFields($item);

        $item['USER_GROUPS'] = $this->revertUserGroups($item['USER_GROUPS']);
        $item['ACTIVE_FROM'] = $this->revertDate($item['ACTIVE_FROM']);
        $item['ACTIVE_TO'] = $this->revertDate($item['ACTIVE_TO']);

        foreach (['CONDITIONS', 'ACTIONS', 'PREDICTIONS'] as $field) {
            if (!empty($item[$field]) && is_array($item[$field])) {
                $item[$field] = $this->revertConditionTree($item[$field]);
            }
        }

        foreach (['PRICE_FROM', 'PRICE_TO', 'CURRENCY', 'ACTIVE_FROM', 'ACTIVE_TO', 'PRESET_ID'] as $field) {
            if ($item[$field] === null || $item[$field] === '') {
                unset($item[$field]);
            }
        }

        if (empty($item['PREDICTIONS'])) {
            unset($item['PREDICTIONS']);
        }

        return $item;
    }

    /**
     * @throws HelperException
     */
    protected function exportUserGroups(int $discountId): array
    {
        try {
            $groupIterator = DiscountGroupTable::getList([
                'select' => ['GROUP_ID'],
                'filter' => ['=DISCOUNT_ID' => $discountId],
                'order'  => ['GROUP_ID' => 'ASC'],
            ]);

            $groups = [];
            $groupHelper = new UserGroupHelper();

            while ($group = $groupIterator->fetch()) {
                $groups[] = $groupHelper->getGroupCodeIfExists((int)$group['GROUP_ID']);
            }

            return $groups;
        } catch (HelperException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    protected function revertUserGroups(array $groups): array
    {
        $groupHelper = new UserGroupHelper();

        return array_map(
            fn($groupCode) => $groupHelper->getGroupIdIfExists((string)$groupCode),
            $groups
        );
    }

    protected function exportDate(mixed $value): ?string
    {
        if ($value instanceof DateTime) {
            return $value->format('Y-m-d H:i:s');
        }

        if (empty($value)) {
            return null;
        }

        return (string)$value;
    }

    protected function revertDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $timestamp = strtotime((string)$value);
        if (!$timestamp) {
            return (string)$value;
        }

        return ConvertTimeStamp($timestamp, 'FULL');
    }

    /**
     * @throws HelperException
     */
    protected function exportConditionTree(array $node): array
    {
        return $this->transformConditionTree($node, true);
    }

    /**
     * @throws HelperException
     */
    protected function revertConditionTree(array $node): array
    {
        return $this->transformConditionTree($node, false);
    }

    /**
     * @throws HelperException
     */
    protected function transformConditionTree(array $node, bool $export): array
    {
        if (!empty($node['CLASS_ID'])) {
            $classId = (string)$node['CLASS_ID'];

            if ($export && $this->isIblockPropertyClassId($classId)) {
                $property = $this->getPropertyByClassId($classId);
                $node = $this->transformDataValues($node, fn($value) => $this->transformPropertyValue($property, $value, true));
                $node['CLASS_ID'] = $this->exportPropertyClassId($property);
            } elseif (!$export && $this->isExportedIblockPropertyClassId($classId)) {
                $property = $this->getPropertyByExportedClassId($classId);
                $node = $this->transformDataValues($node, fn($value) => $this->transformPropertyValue($property, $value, false));
                $node['CLASS_ID'] = $this->revertPropertyClassId($property);
            } else {
                $node = $this->transformDataValues(
                    $node,
                    fn($value) => $this->transformConditionValue($classId, $value, $export)
                );
            }
        }

        if (!empty($node['CHILDREN']) && is_array($node['CHILDREN'])) {
            foreach ($node['CHILDREN'] as $index => $child) {
                if (is_array($child)) {
                    $node['CHILDREN'][$index] = $this->transformConditionTree($child, $export);
                }
            }
        }

        return $node;
    }

    /**
     * @throws HelperException
     */
    protected function transformDataValues(array $node, callable $callback): array
    {
        foreach (['value', 'Value'] as $field) {
            if (isset($node['DATA'][$field])) {
                $node['DATA'][$field] = $callback($node['DATA'][$field]);
            }
        }

        return $node;
    }

    protected function isIblockPropertyClassId(string $classId): bool
    {
        return preg_match('/^CondIBProp:\d+:\d+$/', $classId) === 1;
    }

    protected function isExportedIblockPropertyClassId(string $classId): bool
    {
        return str_starts_with($classId, 'CondIBPropUid:');
    }

    /**
     * @throws HelperException
     */
    protected function getPropertyByClassId(string $classId): array
    {
        if (!preg_match('/^CondIBProp:(\d+):(\d+)$/', $classId, $matches)) {
            throw new HelperException("Iblock property condition \"$classId\" has invalid format");
        }

        $iblockId = (int)$matches[1];
        $propertyId = (int)$matches[2];
        $property = (new IblockHelper())->getProperty($iblockId, ['ID' => $propertyId]);

        if (empty($property['ID']) || empty($property['CODE'])) {
            throw new HelperException("Iblock property with ID=$propertyId not found or has empty CODE");
        }

        return $property;
    }

    /**
     * @throws HelperException
     */
    protected function getPropertyByExportedClassId(string $classId): array
    {
        $parts = explode(':', $classId, 4);
        if (count($parts) !== 4 || $parts[0] !== 'CondIBPropUid' || $parts[1] === '' || $parts[2] === '' || $parts[3] === '') {
            throw new HelperException("Iblock property condition \"$classId\" has invalid format");
        }

        $iblockId = (new IblockHelper())->getIblockIdByUid($parts[1] . ':' . $parts[2]);
        $property = (new IblockHelper())->getProperty($iblockId, $parts[3]);

        if (empty($property['ID'])) {
            throw new HelperException("Iblock property {$parts[3]} not found in iblock {$parts[1]}:{$parts[2]}");
        }

        return $property;
    }

    /**
     * @throws HelperException
     */
    protected function exportPropertyClassId(array $property): string
    {
        if (empty($property['IBLOCK_ID']) || empty($property['CODE'])) {
            throw new HelperException('Iblock property has empty IBLOCK_ID or CODE');
        }

        return sprintf(
            'CondIBPropUid:%s:%s',
            (new IblockHelper())->getIblockUid((int)$property['IBLOCK_ID']),
            $property['CODE']
        );
    }

    protected function revertPropertyClassId(array $property): string
    {
        return sprintf('CondIBProp:%d:%d', (int)$property['IBLOCK_ID'], (int)$property['ID']);
    }

    /**
     * @throws HelperException
     */
    protected function transformPropertyValue(array $property, mixed $value, bool $export): mixed
    {
        if (($property['PROPERTY_TYPE'] ?? '') === 'L') {
            return $this->mapConditionValue($value, fn($enumValue) => $export
                ? $this->exportPropertyEnumRef($enumValue)
                : $this->revertPropertyEnumRef($property, $enumValue)
            );
        }

        if (($property['PROPERTY_TYPE'] ?? '') === 'E') {
            return $this->mapConditionValue($value, fn($elementId) => $export
                ? $this->exportElementRef($elementId)
                : $this->revertElementRef($elementId)
            );
        }

        if (($property['PROPERTY_TYPE'] ?? '') === 'G') {
            return $this->mapConditionValue($value, fn($sectionId) => $export
                ? $this->exportSectionRef($sectionId)
                : $this->revertSectionRef($sectionId)
            );
        }

        return $value;
    }

    /**
     * @throws HelperException
     */
    protected function exportPropertyEnumRef(mixed $enumId): mixed
    {
        if (!is_numeric($enumId)) {
            return $enumId;
        }

        $enum = CIBlockPropertyEnum::GetByID((int)$enumId);
        if (empty($enum['ID'])) {
            throw new HelperException("Iblock property enum with ID=$enumId not found");
        }

        if (empty($enum['XML_ID'])) {
            throw new HelperException("Iblock property enum with ID=$enumId has empty XML_ID");
        }

        return $enum['XML_ID'];
    }

    /**
     * @throws HelperException
     */
    protected function revertPropertyEnumRef(array $property, mixed $enumXmlId): mixed
    {
        if (is_numeric($enumXmlId)) {
            return (int)$enumXmlId;
        }

        if (!is_string($enumXmlId) || $enumXmlId === '') {
            return $enumXmlId;
        }

        $enumId = (new IblockHelper())->getPropertyEnumIdByXmlId(
            (int)$property['IBLOCK_ID'],
            (string)$property['CODE'],
            $enumXmlId
        );

        if (!$enumId) {
            throw new HelperException("Iblock property enum $enumXmlId not found for property {$property['CODE']}");
        }

        return (int)$enumId;
    }

    /**
     * @throws HelperException
     */
    protected function transformConditionValue(string $classId, mixed $value, bool $export): mixed
    {
        if ($classId === 'CondIBIBlock') {
            return $this->mapConditionValue($value, fn($id) => $export
                ? $this->exportIblockRef($id)
                : $this->revertIblockRef($id)
            );
        }

        if ($classId === 'CondIBSection' || $classId === 'GifterCondIBSection') {
            return $this->mapConditionValue($value, fn($id) => $export
                ? $this->exportSectionRef($id)
                : $this->revertSectionRef($id)
            );
        }

        if (
            $classId === 'CondIBElement'
            || $classId === 'GifterCondIBElement'
            || $classId === 'CondBsktFldProduct'
        ) {
            return $this->mapConditionValue($value, fn($id) => $export
                ? $this->exportElementRef($id)
                : $this->revertElementRef($id)
            );
        }

        return $value;
    }

    protected function mapConditionValue(mixed $value, callable $callback): mixed
    {
        if (is_array($value)) {
            if ($this->isEntityRef($value)) {
                return $callback($value);
            }

            return array_map($callback, $value);
        }

        return $callback($value);
    }

    protected function isEntityRef(mixed $value): bool
    {
        return (
            is_array($value)
            && isset($value['IBLOCK'])
            && (isset($value['XML_ID']) || isset($value['CODE']))
        );
    }

    /**
     * @throws HelperException
     */
    protected function exportIblockRef(mixed $iblockId): mixed
    {
        if (!is_numeric($iblockId)) {
            return $iblockId;
        }

        return (new IblockHelper())->getIblockUid((int)$iblockId);
    }

    /**
     * @throws HelperException
     */
    protected function revertIblockRef(mixed $iblockUid): mixed
    {
        if (is_numeric($iblockUid)) {
            return (int)$iblockUid;
        }

        if (!is_string($iblockUid) || !str_contains($iblockUid, ':')) {
            return $iblockUid;
        }

        return (new IblockHelper())->getIblockIdByUid($iblockUid);
    }

    /**
     * @throws HelperException
     */
    protected function exportSectionRef(mixed $sectionId): mixed
    {
        if (!is_numeric($sectionId)) {
            return $sectionId;
        }

        $section = CIBlockSection::GetByID((int)$sectionId)->Fetch();
        if (empty($section['ID'])) {
            throw new HelperException("Iblock section with ID=$sectionId not found");
        }

        return $this->makeIblockEntityRef($section);
    }

    /**
     * @throws HelperException
     */
    protected function revertSectionRef(mixed $sectionRef): mixed
    {
        if (is_numeric($sectionRef)) {
            return (int)$sectionRef;
        }

        if (!$this->isEntityRef($sectionRef)) {
            return $sectionRef;
        }

        $iblockHelper = new IblockHelper();
        $iblockId = $iblockHelper->getIblockIdByUid($sectionRef['IBLOCK']);
        $filter = isset($sectionRef['XML_ID'])
            ? ['=XML_ID' => $sectionRef['XML_ID']]
            : ['=CODE' => $sectionRef['CODE']];

        return $iblockHelper->getSectionIdIfExists($iblockId, $filter);
    }

    /**
     * @throws HelperException
     */
    protected function exportElementRef(mixed $elementId): mixed
    {
        if (!is_numeric($elementId)) {
            return $elementId;
        }

        $element = CIBlockElement::GetByID((int)$elementId)->Fetch();
        if (empty($element['ID'])) {
            throw new HelperException("Iblock element with ID=$elementId not found");
        }

        return $this->makeIblockEntityRef($element);
    }

    /**
     * @throws HelperException
     */
    protected function revertElementRef(mixed $elementRef): mixed
    {
        if (is_numeric($elementRef)) {
            return (int)$elementRef;
        }

        if (!$this->isEntityRef($elementRef)) {
            return $elementRef;
        }

        $iblockHelper = new IblockHelper();
        $iblockId = $iblockHelper->getIblockIdByUid($elementRef['IBLOCK']);
        $filter = isset($elementRef['XML_ID'])
            ? ['=XML_ID' => $elementRef['XML_ID']]
            : ['=CODE' => $elementRef['CODE']];

        return $iblockHelper->getElementIdIfExists($iblockId, $filter);
    }

    /**
     * @throws HelperException
     */
    protected function makeIblockEntityRef(array $item): array
    {
        $result = [
            'IBLOCK' => (new IblockHelper())->getIblockUid((int)$item['IBLOCK_ID']),
        ];

        if (!empty($item['XML_ID'])) {
            $result['XML_ID'] = $item['XML_ID'];
        } elseif (!empty($item['CODE'])) {
            $result['CODE'] = $item['CODE'];
        } else {
            throw new HelperException("Iblock entity with ID={$item['ID']} has empty XML_ID and CODE");
        }

        return $result;
    }
}
