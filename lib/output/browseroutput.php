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

    protected function writeMessage(string $msg, string $type): void
    {
        $this->messages[] = ['type' => $type, 'message' => htmlspecialchars($msg)];
    }


    protected function writeProgress(string $msg, int $val, int $total): void
    {
        if ($val !== $total) {
            $this->progress[] = ['message' => htmlspecialchars($msg), 'value' => $val, 'total' => $total];
        } else {
            $this->messages[] = ['type' => 'info', 'message' => htmlspecialchars("$msg: $val/$total")];
        }
    }
}
