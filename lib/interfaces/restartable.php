<?php

namespace Sprint\Migration\Interfaces;

interface Restartable
{
    public function restart();

    public function getRestartParams(): array;

    public function setRestartParams(array $params = []): void;

    public function restartIterator(string $name, array $array, callable $callback): void;

    public function restartOnce(string $name, callable $callback): mixed;

    public function restartWithOffset(string $name, callable $callback): void;
    public function restartWhile(string $name, callable $callback): void;
}
