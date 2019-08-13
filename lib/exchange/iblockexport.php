<?php

namespace Sprint\Migration\Exchange;

use CIBlockElement;
use Sprint\Migration\AbstractExchange;
use Sprint\Migration\Exceptions\RestartException;
use XMLWriter;


class IblockExport extends AbstractExchange
{
    protected $iblockId;
    protected $file;

    public function isEnabled()
    {
        return (
            $this->getHelperManager()->Iblock()->isEnabled() &&
            class_exists('XMLReader') &&
            class_exists('XMLWriter')
        );
    }

    public function to($file)
    {
        $this->file = $file;
    }

    public function from($iblockId)
    {
        $this->iblockId = $iblockId;
    }


    /**
     * @throws RestartException
     */
    protected function execute()
    {
        if (!isset($this->params['NavPageCount'])) {
            $dbres = $this->getElementsDbres($this->iblockId, 1);
            $this->params['NavPageCount'] = (int)$dbres->NavPageCount;
            $this->params['NavPageNomer'] = (int)$dbres->NavPageNomer;

            $this->createResourceDir($this->file);
            file_put_contents($this->file, '<?xml version="1.0" encoding="UTF-8"?>');
            file_put_contents($this->file, '<items>', FILE_APPEND);
        }

        if ($this->params['NavPageNomer'] <= $this->params['NavPageCount']) {
            $dbres = $this->getElementsDbres($this->iblockId, $this->params['NavPageNomer']);

            while ($item = $dbres->GetNextElement(false, false)) {
                $xml = new XMLWriter();
                $xml->openMemory();
                $xml->startElement('item');

                $fields = $item->GetFields();
                foreach ($fields as $code => $val) {
                    $xml->writeElement($code, $val);
                }
                $xml->endElement();
                file_put_contents($this->file, $xml->flush(), FILE_APPEND);
            }

            $this->outProgress('', $this->params['NavPageNomer'], $this->params['NavPageCount']);
            $this->params['NavPageNomer']++;
            $this->restart();
        }

        file_put_contents($this->file, '</items>', FILE_APPEND);
        unset($this->params['NavPageCount']);
        unset($this->params['NavPageNomer']);
    }


    protected function getElementsDbres($iblockId, $pageNum)
    {
        return CIBlockElement::GetList([
            'ID' => 'ASC',
        ], [
            'IBLOCK_ID' => $iblockId,
        ], false, [
            'nPageSize' => 20,
            'iNumPage' => $pageNum,
            'checkOutOfRange' => true,
        ]);
    }

    protected function createResourceDir($file)
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, BX_DIR_PERMISSIONS, true);
        }
        return $dir;
    }

}
