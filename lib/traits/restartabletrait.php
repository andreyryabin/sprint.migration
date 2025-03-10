<?php

namespace Sprint\Migration\Traits;

use Sprint\Migration\Exceptions\RestartException;

trait RestartableTrait
{
    protected array $params = [];

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

    public function setRestartParams(array $params = []): void
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
