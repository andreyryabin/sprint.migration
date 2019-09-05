<?php

namespace Sprint\Migration\Exchange;

use CIBlockElement;
use Sprint\Migration\AbstractExchange;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Module;
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

            Module::createDir(dirname($this->file));

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

                        if (in_array($code, ['PREVIEW_PICTURE', 'DETAIL_PICTURE'])) {
                            $this->writeFile($writer, $val);
                        } else {
                            $this->writeValue($writer, $val);
                        }
                        $writer->endElement();
                    }
                }

                foreach ($item->GetProperties() as $prop) {
                    if (in_array($prop['CODE'], $this->getExportProperties())) {
                        $method = 'writeProperty' . $prop['PROPERTY_TYPE'];
                        if (method_exists($this, $method)) {
                            $writer->startElement('property');
                            $writer->writeAttribute('name', $prop['CODE']);
                            $this->$method($writer, $prop);
                            $writer->endElement();
                        }
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

    public function writePropertySNEG(XMLWriter $writer, $prop)
    {
        if (!empty($prop['VALUE'])) {
            if (is_array($prop['VALUE'])) {
                foreach ($prop['VALUE'] as $index => $value) {
                    $this->writeValue($writer, $value);
                }
            } else {
                $this->writeValue($writer, $prop['VALUE']);
            }
        }
    }

    public function writePropertyS(XMLWriter $writer, $prop)
    {
        $this->writePropertySNEG($writer, $prop);
    }

    public function writePropertyN(XMLWriter $writer, $prop)
    {
        $this->writePropertySNEG($writer, $prop);
    }

    public function writePropertyE(XMLWriter $writer, $prop)
    {
        $this->writePropertySNEG($writer, $prop);
    }

    public function writePropertyG(XMLWriter $writer, $prop)
    {
        $this->writePropertySNEG($writer, $prop);
    }

    public function writePropertyL(XMLWriter $writer, $prop)
    {
        if (!empty($prop['VALUE_XML_ID'])) {
            if (is_array($prop['VALUE_XML_ID'])) {
                foreach ($prop['VALUE_XML_ID'] as $index => $value) {
                    $this->writeValue($writer, $value);
                }
            } else {
                $this->writeValue($writer, $prop['VALUE_XML_ID']);
            }
        }
    }

    public function writePropertyF(XMLWriter $writer, $prop)
    {
        if (!empty($prop['VALUE'])) {
            if (is_array($prop['VALUE'])) {
                foreach ($prop['VALUE'] as $index => $value) {
                    $this->writeFile($writer, $value);
                }
            } else {
                $this->writeFile($writer, $prop['VALUE']);
            }
        }
    }

    protected function writeValue(XMLWriter $writer, $val, $attributes = [])
    {
        if (!empty($val)) {
            $writer->startElement('value');
            foreach ($attributes as $atcode => $atval) {
                if (!empty($atval)) {
                    $writer->writeAttribute($atcode, $atval);
                }
            }
            $writer->text($val);
            $writer->endElement();
        }
    }

    protected function writeFile(XMLWriter $writer, $fileId)
    {
        $file = \CFile::GetFileArray($fileId);
        if (!empty($file)) {
            $filePath = Module::getDocRoot() . $file['SRC'];
            $newPath = $this->getExportDir() . '/' . $file['SUBDIR'] . '/' . $file['FILE_NAME'];

            Module::createDir(dirname($newPath));

            copy($filePath, $newPath);

            $this->writeValue($writer, $file['SUBDIR'] . '/' . $file['FILE_NAME'], [
                'description' => $file['DESCRIPTION'],
            ]);
        }
    }

    protected function getExportDir()
    {
        return dirname($this->file);
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
