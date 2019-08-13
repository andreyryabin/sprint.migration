<?php

namespace Sprint\Migration;


use Sprint\Migration\Exceptions\ExchangeException;
use Sprint\Migration\Exceptions\RestartException;

abstract class abstractexchange
{
    protected $service;

    protected $params = [];

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

    abstract public function execute();

    /**
     * abstractexchange constructor.
     * @param RestartableEntity $service
     * @throws ExchangeException
     */
    public function __construct(RestartableEntity $service)
    {
        $this->service = $service;
        $this->params = $service->getRestartParams();

        if (!$this->isEnabled()) {
            Throw new ExchangeException('Exchange disabled');
        }
    }

    public function isEnabled()
    {
        return true;
    }

    protected function saveParams()
    {
        $this->service->setRestartParams($this->params);
    }

    /**
     * @throws RestartException
     */
    protected function restart()
    {
        $this->service->setRestartParams($this->params);
        $this->service->restart();
    }

    /**
     * @return HelperManager
     */
    protected function getHelperManager()
    {
        return HelperManager::getInstance();
    }
}