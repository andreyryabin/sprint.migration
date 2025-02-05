<?php

namespace Sprint\Migration\Exchange;

use Exception;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\Base\ExchangeWriter;


class HlblockElementsExport extends ExchangeWriter
{
    protected $hlblockId;
    protected $exportFields = [];

    public function setHlblockId($hlblockId)
    {
        $this->hlblockId = $hlblockId;
        return $this;
    }

    /**
     * @throws RestartException
     * @throws HelperException
     * @throws Exception
     */
    public function execute()
    {
        $hblockExchange = $this->getHelperManager()->HlblockExchange();

        $params = $this->exchangeEntity->getRestartParams();
        if (!isset($params['total'])) {
            $hlblockUid = $hblockExchange->getHlblockUid($this->hlblockId);

            $params['total'] = $hblockExchange->getElementsCount($this->hlblockId);
            $params['offset'] = 0;

            $this->createExchangeFile(['hlblockUid' => $hlblockUid]);
        }

        if ($params['offset'] <= $params['total'] - 1) {

            $dto = $hblockExchange->getElementsExchangeDto(
                $this->hlblockId,
                [
                    'order' => ['ID' => 'ASC'],
                    'offset' => $params['offset'],
                    'limit' => $this->getLimit(),
                ],
                $this->getExportFields()
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

    protected function getExportFields()
    {
        return $this->exportFields;
    }

    public function setExportFields(array $exportFields): static
    {
        $this->exportFields = $exportFields;
        return $this;
    }
}
