<?php

namespace Sprint\Migration;

use ReflectionClass;
use ReflectionException;
use Sprint\Migration\Exceptions\ExchangeException;
use Sprint\Migration\Exceptions\RestartException;


abstract class ExchangeEntity
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
        outMessages as protected;
    }
    /**
     * @var array
     */
    protected $params = [];

    /**
     * @throws RestartException
     */
    public function restart()
    {
        Throw new RestartException();
    }

    /**
     * @return array
     */
    public function getRestartParams()
    {
        return $this->params;
    }

    /**
     * @param array $params
     */
    public function setRestartParams($params = [])
    {
        $this->params = $params;
    }

    /**
     * @param $name
     * @throws ExchangeException
     * @return string
     */
    public function getResourceFile($name)
    {
        try {
            $classInfo = new ReflectionClass($this);
            return dirname($classInfo->getFileName()) . '/' . $classInfo->getShortName() . '_files/' . $name;
        } catch (ReflectionException $e) {
            $this->exitWithMessage($e->getMessage());
        }
    }

    /**
     * @throws ExchangeException
     * @return string
     */
    public function getClassName()
    {
        try {
            $classInfo = new ReflectionClass($this);
            $name = $classInfo->getShortName();
        } catch (ReflectionException $e) {
            $name = '';
        }

        $this->exitIfEmpty(
            $name,
            Locale::getMessage(
                'ERR_CLASS_NOT_FOUND',
                [
                    '#NAME#' => $name,
                ]
            )
        );
        return $name;
    }


    /**
     * @param $msg
     * @throws ExchangeException
     */
    public function exitWithMessage($msg)
    {
        Throw new ExchangeException($msg);
    }

    /**
     * @param $cond
     * @param $msg
     * @throws ExchangeException
     */
    public function exitIf($cond, $msg)
    {
        if ($cond) {
            Throw new ExchangeException($msg);
        }
    }

    /**
     * @param $var
     * @param $msg
     * @throws ExchangeException
     */
    public function exitIfEmpty($var, $msg)
    {
        if (empty($var)) {
            Throw new ExchangeException($msg);
        }
    }
}
