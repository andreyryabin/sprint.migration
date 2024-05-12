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
}
