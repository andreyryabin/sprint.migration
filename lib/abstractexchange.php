<?php

namespace Sprint\Migration;


use Sprint\Migration\Exceptions\ExchangeException;
use Sprint\Migration\Exceptions\RestartException;

abstract class abstractexchange
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

    private $entity;

    /**
     * abstractexchange constructor.
     * @param RestartableInterface $entity
     * @throws ExchangeException
     */
    public function __construct(RestartableInterface $entity)
    {
        $this->entity = $entity;
        $this->params = $entity->getRestartParams();

        if (!$this->isEnabled()) {
            Throw new ExchangeException('Exchange disabled');
        }
    }

    public function __destruct()
    {
        $this->entity->setRestartParams($this->params);
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
        $this->entity->restart();
    }

    /**
     * @return HelperManager
     */
    protected function getHelperManager()
    {
        return HelperManager::getInstance();
    }
}