<?php

namespace Sprint\Migration;

use ReflectionClass;
use ReflectionException;
use Sprint\Migration\Exceptions\ExchangeException;
use Sprint\Migration\Exceptions\RestartException;

abstract class ExchangeEntity
{
    use OutTrait;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @throws RestartException
     */
    public function restart()
    {
        throw new RestartException();
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
     *
     * @throws ExchangeException
     * @return string
     */
    public function getResourceFile($name)
    {
        $classInfo = new ReflectionClass($this);
        return dirname($classInfo->getFileName()) . '/' . $classInfo->getShortName() . '_files/' . $name;
    }

    /**
     * @throws ExchangeException
     * @return string
     */
    public function getClassName()
    {
        $classInfo = new ReflectionClass($this);
        $name = $classInfo->getShortName();

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
     *
     * @throws ExchangeException
     */
    public function exitWithMessage($msg)
    {
        throw new ExchangeException($msg);
    }

    /**
     * @param $cond
     * @param $msg
     *
     * @throws ExchangeException
     */
    public function exitIf($cond, $msg)
    {
        if ($cond) {
            throw new ExchangeException($msg);
        }
    }

    /**
     * @param $var
     * @param $msg
     *
     * @throws ExchangeException
     */
    public function exitIfEmpty($var, $msg)
    {
        if (empty($var)) {
            throw new ExchangeException($msg);
        }
    }
}
