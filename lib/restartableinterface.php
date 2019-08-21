<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\RestartException;


interface RestartableInterface
{
    /**
     * @throws RestartException
     */
    public function restart();

    /**
     * @return array
     */
    public function getRestartParams();

    /**
     * @param array $params
     */
    public function setRestartParams($params = []);

}