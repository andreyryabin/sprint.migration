<?php

namespace Sprint\Migration\Exchange;

use Exception;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\Base\ExchangeDto;
use Sprint\Migration\Exchange\Base\ExchangeWriter;
use Sprint\Migration\Helpers\IblockExchangeHelper;
use XMLWriter;

class IblockElementsExport extends ExchangeWriter
{
    protected $iblockId;
    protected array $exportFilter = [];
    protected array $exportFields = [];
    protected array $exportProperties = [];

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

    /**
     * @throws HelperException
     */
    protected function getRecordsDto(int $offset, int $limit): ExchangeDto
    {
        $iblockExchangeHelper = new IblockExchangeHelper();

        return $iblockExchangeHelper->getElementsListExchangeDto(
            $this->getIblockId(),
            [
                'order' => ['ID' => 'ASC'],
                'offset' => $offset,
                'limit' => $limit,
                'filter' => $this->getExportFilter(),
            ],
            $this->getExportFields(),
            $this->getExportProperties()
        );
    }
}
