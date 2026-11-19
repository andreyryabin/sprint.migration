<?php

namespace Sprint\Migration\Output;

class LoggerOutput extends AbstractOutput
{
    protected function writeMessage(string $msg, string $type): void {}

    protected function writeProgress(string $msg, int $val, int $total): void {}
}
