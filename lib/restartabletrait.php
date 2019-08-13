<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\RestartException;


trait RestartableTrait
{

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
     * Need For Sprint\Migration\VersionManager
     * @param array $params
     */
    public function setRestartParams($params = [])
    {
        $this->params = $params;
    }

    /**
     * @return HelperManager
     */
    protected function getHelperManager()
    {
        return HelperManager::getInstance();
    }
}