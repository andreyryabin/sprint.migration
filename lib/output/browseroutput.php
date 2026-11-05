<?php

namespace Sprint\Migration\Output;

class BrowserOutput extends AbstractOutput
{

    private array $messages = [];
    private array $progress = [];

    public function getLog(): array
    {
        return ['messages' => $this->messages, 'progress' => $this->progress];
    }

    protected function makeMessage(string $msg, string $type): void
    {
        $this->messages[] = ['type' => $type, 'message' => $msg];
    }


    protected function makeProgress(string $msg, int $val, int $total): void
    {
        if ($val !== $total) {
            $this->progress[] = ['message' => $msg, 'value' => $val, 'total' => $total];
        } else {
            $this->messages[] = ['type' => 'info', 'message' => "$msg: $val/$total"];
        }
    }
}
