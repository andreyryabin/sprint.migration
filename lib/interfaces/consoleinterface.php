<?php

namespace Sprint\Migration\Interfaces;

use Sprint\Migration\Exceptions\ConsoleException;

interface ConsoleInterface
{
    /**
     * @throws ConsoleException
     */
    public function read(string $caption): string;

    /**
     * @throws ConsoleException
     */
    public function print(string $msg, ...$vars): void;

}
