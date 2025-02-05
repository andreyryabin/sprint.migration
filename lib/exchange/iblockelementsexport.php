<?php

namespace Sprint\Migration\Exchange;

use Exception;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\Base\ExchangeWriter;
use XMLWriter;

class IblockElementsExport extends ExchangeWriter
{
    protected $iblockId;
    protected $exportFilter = [];
    protected $exportFields = [];
    protected $exportProperties = [];

    /**
     * @throws RestartException
     * @throws Exception
     */
    public function execute()
    {
        $iblockExchange = $this->getHelperManager()->IblockExchange();

        $params = $this->exchangeEntity->getRestartParams();
        if (!isset($params['total'])) {
            $iblockUid = $iblockExchange->getIblockUid(
                $this->getIblockId()
            );

            $params['total'] = $iblockExchange->getElementsCount(
                $this->getIblockId(),
                $this->getExportFilter()
            );
            $params['offset'] = 0;

            $this->createExchangeFile(['iblockUid' => $iblockUid]);
        }

        if ($params['offset'] <= $params['total'] - 1) {
            $dto = $iblockExchange->getElementsListExchangeDto(
                $this->getIblockId(),
                [
                    'order' => ['ID' => 'ASC'],
                    'offset' => $params['offset'],
                    'limit' => $this->getLimit(),
                    'filter' => $this->getExportFilter(),
                ],
                $this->getExportFields(),
                $this->getExportProperties()
            );

            $this->appendDtoToExchangeFile($dto);

            $params['offset'] += $dto->countChilds();

            $this->outProgress('Progress: ', $params['offset'], $params['total']);

            $this->exchangeEntity->setRestartParams($params);
            $this->exchangeEntity->restart();
        }

        $this->closeExchangeFile();

        unset($params['total']);
        unset($params['offset']);
        $this->exchangeEntity->setRestartParams($params);
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

}
