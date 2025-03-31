<?php

namespace Sprint\Migration\Traits;

use Closure;
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
    public function restartIterator(string $name, array $array, Closure $callback): void
    {
        $index = (int)($this->params[$name] ?? 0);

        if (isset($array[$index])) {
            $callback($array[$index], $index);
            $this->params[$name] = $index + 1;
            $this->restart();
        }
    }

    /**
     * @throws RestartException
     */
    public function restartOnce(string $name, Closure $callback): mixed
    {
        if (!array_key_exists($name, $this->params)) {
            $res = $callback();
            $this->params[$name] = serialize($res);
            $this->restart();
        }
        return unserialize($this->params[$name]);
    }

    /**
     * @throws RestartException
     */
    public function restartWhile(string $name, Closure $callback): void
    {
        $index = $this->params[$name] ?? 0;

        if ($index !== 'finish') {
            $index = (int)$callback((int)$index);
            if ($index > 0) {
                $this->params[$name] = $index;
                $this->restart();
            } else {
                $this->params[$name] = 'finish';
            }
        }
    }
}
