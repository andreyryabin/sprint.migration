<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\Base\ExchangeDto;

class HlblockExchangeHelper extends HlblockHelper
{
    protected $cachedFields = [];

    /**
     * @param $hlblockName
     * @param $fieldName
     *
     * @return mixed
     * @throws HelperException
     */
    public function getField($hlblockName, $fieldName)
    {
        $key = $hlblockName . $fieldName;

        if (!isset($this->cachedProps[$key])) {
            $this->cachedFields[$key] = parent::getField($hlblockName, $fieldName);
        }
        return $this->cachedFields[$key];
    }

    /**
     * @return array
     * @throws HelperException
     */
    public function getHlblocksStructure(): array
    {
        $res = [];
        $hlblocks = $this->getHlblocks();
        foreach ($hlblocks as $hlblock) {
            $res[] = [
                'title' => $hlblock['NAME'],
                'value' => $hlblock['ID'],
            ];
        }
        return $res;
    }

    /**
     * @param $hlblockName
     *
     * @return array
     * @throws HelperException
     */
    public function getHlblockFieldsCodes($hlblockName)
    {
        $res = [];
        $items = $this->getFields($hlblockName);
        foreach ($items as $item) {
            if (!empty($item['FIELD_NAME'])) {
                $res[] = $item['FIELD_NAME'];
            }
        }
        return $res;
    }

    /**
     * @throws HelperException
     */
    public function getElementsExchangeDto($hlblockId, array $params, array $exportFields): ExchangeDto
    {
        $elements = $this->getElements($hlblockId, $params);

        $dto = new ExchangeDto('tmp');
        foreach ($elements as $element) {
            $dto->addChild(
                $this->createRecordDto($hlblockId, $element, $exportFields)
            );
        }

        return $dto;
    }

    /**
     * @throws HelperException
     */
    private function createRecordDto($hlblockId, array $element, array $exportFields): ExchangeDto
    {
        $item = new ExchangeDto('item');
        foreach ($element as $code => $val) {
            if (in_array($code, $exportFields)) {
                $item->addChild(
                    $this->createFieldDto([
                        'NAME' => $code,
                        'VALUE' => $val,
                        'HLBLOCK_ID' => $hlblockId,
                        'USER_TYPE_ID' => $this->getFieldType($hlblockId, $code)
                    ])
                );
            }
        }
        return $item;
    }

    /**
     * @throws HelperException
     */
    private function createFieldDto(array $field): ExchangeDto
    {
        $dto = new ExchangeDto('field', ['name' => $field['NAME']]);

        if ($field['USER_TYPE_ID'] == 'enumeration') {
            $xmlIds = $this->getFieldEnumXmlIdsByIds($field['HLBLOCK_ID'], $field['NAME'], $field['VALUE']);
            $dto->addValue($xmlIds);
        } elseif ($field['USER_TYPE_ID'] == 'file') {
            $dto->addFile($field['VALUE']);
        } else {
            $dto->addValue($field['VALUE']);
        }

        return $dto;
    }


}
