<?php

namespace Sprint\Migration\Traits;

use Sprint\Migration\Exceptions\RestartException;

trait RestartableTrait
{
    /** @deprecated */
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
    public function restartIterator(string $name, array $array, callable $callback): void
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
    public function restartOnce(string $name, callable $callback): mixed
    {
        if (!array_key_exists($name, $this->params)) {
            $res = call_user_func($callback);
            $this->params[$name] = serialize($res);
            $this->restart();
        }
        return unserialize($this->params[$name]);
    }

    /**
     * @throws RestartException
     */
    public function restartWithOffset(string $name, callable $callback): void
    {
        $offset = $this->params[$name] ?? 0;
        if (is_numeric($offset)) {
            $res = (int)call_user_func($callback, (int)$offset);
            if ($res > $offset) {
                $this->params[$name] = $res;
                $this->restart();
            } else {
                $this->params[$name] = 'finish';
            }
        }
    }


    /**
     * @throws RestartException
     */
    public function restartWhile(string $name, callable $callback): void
    {
        if (!array_key_exists($name, $this->params)) {
            $res = call_user_func($callback);
            if ($res) {
                $this->restart();
            } else {
                $this->params[$name] = 'finish';
            }
        }
    }
}
