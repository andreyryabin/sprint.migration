<?php

namespace Sprint\Migration\Traits;

use Closure;
use Sprint\Migration\Exceptions\RestartException;

trait RestartableTrait
{
    protected array $params = [];

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
        $index = (int) ($this->params[$name] ?? 0);

        if (isset($array[$index])) {
            $callback($array[$index], $index);
            $this->params[$name] = $index + 1;
            $this->restart();
        }
    }

    /**
     * @throws RestartException
     */
    public function restart()
    {
        throw new RestartException();
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
        return unserialize($this->params[$name], ['allowed_classes' => false]);
    }

    /**
     * @throws RestartException
     */
    public function restartWhile(string $name, Closure $callback): void
    {
        $offset = $this->params[$name] ?? 0;

        if ($offset !== 'finish') {
            $offset = (int) $callback((int) $offset);
            if ($offset > 0) {
                $this->params[$name] = $offset;
                $this->restart();
            } else {
                $this->params[$name] = 'finish';
            }
        }
    }
}
