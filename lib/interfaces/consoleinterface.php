<?php

namespace Sprint\Migration\Interfaces;

use Sprint\Migration\Exceptions\ConsoleException;

interface ConsoleInterface
{
    /**
     * @throws ConsoleException
     */
    public function read(string $msg, ...$vars): string;

    /**
     * @throws ConsoleException
     */
    public function print(string $msg, ...$vars): void;

    /**
     * @throws ConsoleException
     */
    public function lines(string $msg, string $lines): void;
}
