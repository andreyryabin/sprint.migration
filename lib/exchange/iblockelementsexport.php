<?php

namespace Sprint\Migration\Exchange;

use CIBlockElement;
use Sprint\Migration\AbstractExchange;
use Sprint\Migration\Exceptions\RestartException;
use XMLWriter;


class IblockElementsExport extends AbstractExchange
{
    protected $iblockId;
    protected $file;

    protected $limit = 10;

    protected $exportFields = [];
    protected $exportProperties = [];

    public function isEnabled()
    {
        return (
            $this->getHelperManager()->Iblock()->isEnabled() &&
            class_exists('XMLReader') &&
            class_exists('XMLWriter')
        );
    }

    /**
     * @throws RestartException
     */
    public function execute()
    {
        if (!isset($this->params['NavPageCount'])) {
            $dbres = $this->getElementsDbres($this->iblockId, 1);
            $this->params['NavPageCount'] = (int)$dbres->NavPageCount;
            $this->params['NavPageNomer'] = (int)$dbres->NavPageNomer;

            file_put_contents($this->file, '<?xml version="1.0" encoding="UTF-8"?>');
            file_put_contents($this->file, '<items>', FILE_APPEND);
        }

        if ($this->params['NavPageNomer'] <= $this->params['NavPageCount']) {
            $dbres = $this->getElementsDbres($this->iblockId, $this->params['NavPageNomer']);

            while ($item = $dbres->GetNextElement(false, false)) {
                $writer = new XMLWriter();
                $writer->openMemory();
                $writer->startElement('item');

                foreach ($item->GetFields() as $code => $val) {
                    if (in_array($code, $this->getExportFields())) {
                        $writer->startElement('field');
                        $writer->writeAttribute('name', $code);
                        if (!empty($val)) {
                            $writer->text($val);
                        }

                        $writer->endElement();
                    }
                }

                foreach ($item->GetProperties() as $prop) {
                    if (in_array($prop['CODE'], $this->getExportProperties())) {
                        $writer->startElement('property');
                        $writer->writeAttribute('name', $prop['CODE']);
                        if ($prop['MULTIPLE'] == 'Y') {
                            if (!empty($prop['VALUE'])) {
                                foreach ($prop['VALUE'] as $index => $value) {
                                    $writer->startElement('value');
                                    if (!empty($prop['VALUE_XML_ID'][$index])) {
                                        $writer->writeAttribute('xml_id', $prop['VALUE_XML_ID'][$index]);
                                    }
                                    $writer->text($value);
                                    $writer->endElement();
                                }
                            }
                        } else {
                            if (!empty($prop['VALUE'])) {
                                $writer->text($prop['VALUE']);
                            }
                        }

                        $writer->endElement();
                    }
                }

                //item
                $writer->endElement();
                file_put_contents($this->file, $writer->flush(), FILE_APPEND);
            }

            $this->outProgress('', $this->params['NavPageNomer'], $this->params['NavPageCount']);
            $this->params['NavPageNomer']++;
            $this->restart();
        }

        file_put_contents($this->file, '</items>', FILE_APPEND);
        unset($this->params['NavPageCount']);
        unset($this->params['NavPageNomer']);
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
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
     */
    public function setExportFields(array $exportFields)
    {
        $this->exportFields = $exportFields;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
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
     */
    public function setExportProperties(array $exportProperties)
    {
        $this->exportProperties = $exportProperties;
    }

    public function to($file)
    {
        $this->file = $file;
    }

    public function from($iblockId)
    {
        $this->iblockId = $iblockId;
    }

    protected function getElementsDbres($iblockId, $pageNum)
    {
        return CIBlockElement::GetList([
            'ID' => 'ASC',
        ], [
            'IBLOCK_ID' => $iblockId,
            'CHECK_PERMISSIONS' => 'N',
        ], false, [
            'nPageSize' => $this->getLimit(),
            'iNumPage' => $pageNum,
            'checkOutOfRange' => true,
        ]);
    }
}
