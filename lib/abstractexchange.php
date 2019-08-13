<?php

namespace Sprint\Migration;


use Sprint\Migration\Exceptions\ExchangeException;
use Sprint\Migration\Exceptions\RestartException;

abstract class abstractexchange
{
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
     * @param $params
     * @throws ExchangeException
     */
    public function __construct(&$params)
    {
        $this->params = $params;

        if (!$this->isEnabled()) {
            Throw new ExchangeException('Exchange disabled');
        }
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
        Throw new RestartException();
    }

    /**
     * @return HelperManager
     */
    protected function getHelperManager()
    {
        return HelperManager::getInstance();
    }
}