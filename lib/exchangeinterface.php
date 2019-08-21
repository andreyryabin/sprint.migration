<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\RestartException;


interface ExchangeInterface
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

    /**
     * @param $name
     * @return mixed
     */
    public function getResource($name);

    /**
     * @return mixed
     */
    public function getClassName();
}