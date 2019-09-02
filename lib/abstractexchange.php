<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\ExchangeException;
use Sprint\Migration\Exceptions\RestartException;

abstract class AbstractExchange
{
    use OutTrait {
        out as protected;
        outIf as protected;
        outProgress as protected;
        outNotice as protected;
        outNoticeIf as protected;
        outInfo as protected;
        outInfoIf as protected;
        outSuccess as protected;
        outSuccessIf as protected;
        outWarning as protected;
        outWarningIf as protected;
        outError as protected;
        outErrorIf as protected;
        outDiff as protected;
        outDiffIf as protected;
    }

    protected $params = [];

    protected $exchangeEntity;

    /**
     * abstractexchange constructor.
     * @param ExchangeEntity $exchangeEntity
     * @throws ExchangeException
     */
    public function __construct(ExchangeEntity $exchangeEntity)
    {
        $this->exchangeEntity = $exchangeEntity;
        $this->params = $exchangeEntity->getRestartParams();

        if (!$this->isEnabled()) {
            Throw new ExchangeException('Exchange disabled');
        }
    }

    public function __destruct()
    {
        $this->exchangeEntity->setRestartParams($this->params);
    }

    public function isEnabled()
    {
        return true;
    }

    /**
     * @throws RestartException
     */
    protected function restart()
    {
        $this->exchangeEntity->setRestartParams($this->params);
        $this->exchangeEntity->restart();
    }

    /**
     * @return HelperManager
     */
    protected function getHelperManager()
    {
        return HelperManager::getInstance();
    }
}