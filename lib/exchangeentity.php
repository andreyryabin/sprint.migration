<?php

namespace Sprint\Migration;

use ReflectionClass;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RestartException;

abstract class ExchangeEntity
{
    /**
     * @var array
     */
    protected $params = [];
    private   $versionConfig;

    /**
     * @return VersionConfig
     */
    public function getVersionConfig()
    {
        return $this->versionConfig;
    }

    /**
     * Не использовать
     * @param VersionConfig $versionConfig
     *
     * @return void
     */
    public function setVersionConfig(VersionConfig $versionConfig)
    {
        $this->versionConfig = $versionConfig;
    }

    public function getClassName()
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
}
