<?php

namespace Sprint\Migration;

use ReflectionClass;
use Sprint\Migration\Exceptions\RestartException;

abstract class ExchangeEntity
{
    protected $params = [];
    private   $versionConfig;

    public function getVersionConfig(): VersionConfig
    {
        return $this->versionConfig;
    }

    /**
     * Не использовать
     *
     * @param VersionConfig $versionConfig
     *
     * @return void
     */
    public function setVersionConfig(VersionConfig $versionConfig)
    {
        $this->versionConfig = $versionConfig;
    }

    public function getClassName(): string
    {
        return (new ReflectionClass($this))->getShortName();
    }

    /**
     * @throws RestartException
     */
    public function restart()
    {
        throw new RestartException();
    }

    public function getRestartParams(): array
    {
        return $this->params;
    }

    public function setRestartParams(array $params = [])
    {
        $this->params = $params;
    }

    /**
     * @throws RestartException
     */
    protected function restartIterator(string $name, array $array, callable $callback): void
    {
        $index = $this->params[$name] ?? 0;

        if (isset($array[$index])) {
            call_user_func($callback, $array[$index], $index);
            $this->params[$name] = $index + 1;
            $this->restart();
        }
    }

    /**
     * @throws RestartException
     */
    protected function restartOnce(string $name, callable $callback)
    {
        if (!array_key_exists($name, $this->params)) {
            $res = call_user_func($callback);
            $this->params[$name] = serialize($res);
            $this->restart();
        }
        return unserialize($this->params[$name]);
    }
}
