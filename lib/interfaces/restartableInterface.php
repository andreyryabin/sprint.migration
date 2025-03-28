<?php

namespace Sprint\Migration\Interfaces;

use Closure;
use Sprint\Migration\Exceptions\RestartException;

interface RestartableInterface
{
    /**
     * @throws RestartException
     */
    public function restart();

    public function getRestartParams(): array;

    public function setRestartParams(array $params = []): void;

    /**
     * @throws RestartException
     */
    public function restartIterator(string $name, array $array, Closure $callback): void;

    /**
     * @throws RestartException
     */
    public function restartOnce(string $name, Closure $callback): mixed;

    /**
     * @throws RestartException
     */
    public function restartWhile(string $name, Closure $callback): void;
}
