<?php

namespace Sprint\Migration\Exchange;

use Exception;
use Sprint\Migration\AbstractExchange;
use Sprint\Migration\Exceptions\RestartException;
use XMLWriter;

class IblockElementsExport extends AbstractExchange
{
    protected $iblockId;
    protected $exportFilter     = [];
    protected $exportFields     = [];
    protected $exportProperties = [];

    /**
     * @return array
     */
    public function getExportFilter()
    {
        return $this->exportFilter;
    }

    /**
     * @param array $exportFilter
     *
     * @return IblockElementsExport
     */
    public function setExportFilter(array $exportFilter)
    {
        $this->exportFilter = $exportFilter;
        return $this;
    }

    /**
     * @return array
     */
    public function getExportFields()
    {
        return $this->exportFields;
    }

    /**
     * @param array $exportFields
     *
     * @return IblockElementsExport
     */
    public function setExportFields(array $exportFields)
    {
        $this->exportFields = $exportFields;
        return $this;
    }

    /**
     * @return array
     */
    public function getExportProperties()
    {
        return $this->exportProperties;
    }

    /**
     * @param array $exportProperties
     *
     * @return IblockElementsExport
     */
    public function setExportProperties(array $exportProperties)
    {
        $this->exportProperties = $exportProperties;
        return $this;
    }

    public function getIblockId()
    {
        return $this->iblockId;
    }

    public function setIblockId($iblockId)
    {
        $this->iblockId = $iblockId;
        return $this;
    }

    /**
     * @throws RestartException
     * @throws Exception
     */
    public function execute()
    {
        $iblockExchange = $this->getHelperManager()->IblockExchange();

        $params = $this->exchangeEntity->getRestartParams();
        if (!isset($params['total'])) {
            $params['total'] = $iblockExchange->getElementsCount(
                $this->getIblockId(),
                $this->getExportFilter()
            );
            $params['offset'] = 0;

            $this->createExchangeDir();

            $iblockUid = $iblockExchange->getIblockUid(
                $this->getIblockId()
            );

            $this->appendToExchangeFile('<?xml version="1.0" encoding="UTF-8"?>');
            $this->appendToExchangeFile('<items iblockUid="' . $iblockUid . '" exchangeVersion="' . self::EXCHANGE_VERSION . '">');
        }

        if ($params['offset'] <= $params['total'] - 1) {
            $items = $iblockExchange->getElementsEx(
                $this->getIblockId(),
                [
                    'order'  => ['ID' => 'ASC'],
                    'offset' => $params['offset'],
                    'limit'  => $this->getLimit(),
                    'filter' => $this->getExportFilter(),
                ]
            );

            foreach ($items as $item) {
                $writer = new XMLWriter();
                $writer->openMemory();
                $writer->startElement('item');

                foreach ($item['FIELDS'] as $code => $val) {
                    if (in_array($code, $this->getExportFields())) {
                        $method = $this->getWriteFieldMethod($code);
                        if (method_exists($this, $method)) {
                            $writer->startElement('field');
                            $writer->writeAttribute('name', $code);
                            $this->$method($writer, $val);
                            $writer->endElement();
                        }
                    }
                }

                foreach ($item['PROPS'] as $prop) {
                    if (in_array($prop['CODE'], $this->getExportProperties())) {
                        $method = $this->getWritePropertyMethod($prop);
                        if (method_exists($this, $method)) {
                            $writer->startElement('property');
                            $writer->writeAttribute('name', $prop['CODE']);
                            $this->$method($writer, $prop);
                            $writer->endElement();
                        }
                    }
                }

                $writer->endElement();
                $this->appendToExchangeFile($writer->flush());
                $params['offset']++;
            }

            $this->outProgress('', $params['offset'], $params['total']);

            $this->exchangeEntity->setRestartParams($params);
            $this->restart();
        }

        $this->appendToExchangeFile('</items>');
        unset($params['total']);
        unset($params['offset']);
        $this->exchangeEntity->setRestartParams($params);
    }

    protected function getWriteFieldMethod($code)
    {
        if (in_array($code, ['PREVIEW_PICTURE', 'DETAIL_PICTURE'])) {
            return 'writeFieldF';
        } elseif ($code == 'IBLOCK_SECTION') {
            return 'writeFieldSection';
        } else {
            return 'writeFieldS';
        }
    }

    /**
     * @param XMLWriter $writer
     * @param           $val
     *
     * @throws Exception
     */
    protected function writeFieldF(XMLWriter $writer, $val)
    {
        $this->writeFile($writer, $val);
    }

    protected function writeFieldSection(XMLWriter $writer, $val)
    {
        $iblockExchange = $this->getHelperManager()->IblockExchange();

        $val = $iblockExchange->getSectionUniqNamesByIds(
            $this->getIblockId(),
            $val
        );
        $this->writeValue($writer, $val);
    }

    protected function writeFieldS(XMLWriter $writer, $val)
    {
        $this->writeValue($writer, $val);
    }

    protected function getWritePropertyMethod($prop)
    {
        $type = $prop['PROPERTY_TYPE'];

        if (in_array($type, ['L', 'F', 'G'])) {
            return 'writeProperty' . ucfirst($type);
        } else {
            return 'writePropertyS';
        }
    }

    protected function writePropertyS(XMLWriter $writer, $prop)
    {
        if ($prop['MULTIPLE'] == 'Y') {
            foreach ($prop['VALUE'] as $index => $val1) {
                $attributes = [];
                if (!empty($prop['DESCRIPTION'][$index])) {
                    $attributes = ['description' => $prop['DESCRIPTION'][$index]];
                }
                $this->writeSingleValue($writer, $val1, $attributes);
            }
        } else {
            $attributes = [];
            if (!empty($prop['DESCRIPTION'])) {
                $attributes = ['description' => $prop['DESCRIPTION']];
            }
            $this->writeSingleValue($writer, $prop['VALUE'], $attributes);
        }
    }

    protected function writePropertyG(XMLWriter $writer, $prop)
    {
        $iblockExchange = $this->getHelperManager()->IblockExchange();

        $prop['VALUE'] = is_array($prop['VALUE']) ? $prop['VALUE'] : [$prop['VALUE']];
        $prop['VALUE'] = $iblockExchange->getSectionUniqNamesByIds(
            $prop['LINK_IBLOCK_ID'],
            $prop['VALUE']
        );
        $this->writeValue($writer, $prop['VALUE']);
    }

    protected function writePropertyL(XMLWriter $writer, $prop)
    {
        $this->writeValue($writer, $prop['VALUE_XML_ID']);
    }

    /**
     * @param XMLWriter $writer
     * @param           $prop
     *
     * @throws Exception
     */
    protected function writePropertyF(XMLWriter $writer, $prop)
    {
        $this->writeFile($writer, $prop['VALUE']);
    }
}
