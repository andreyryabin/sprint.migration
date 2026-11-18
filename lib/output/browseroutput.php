<?php

namespace Sprint\Migration\Output;


use CAdminMessage;
use Psr\Log\LoggerInterface;

class BrowserOutput extends AbstractOutput
{
    private bool $isAdminMessage;

    public function __construct(?LoggerInterface $logger = null)
    {
        parent::__construct(
            $logger,
        );

        $this->isAdminMessage = class_exists(
            CAdminMessage::class,
        );
    }

    protected function writeMessage(string $msg, string $type): void
    {
        if ($this->isAdminMessage) {
            CAdminMessage::ShowMessage([
                'MESSAGE' => $msg,
                'TYPE'    => (in_array($type, ['error', 'warning']) ? 'ERROR' : 'OK'),
            ]);
        } else {
            echo $msg;
        }
    }

    protected function writeProgress(string $msg, int $val, int $total): void
    {
        if ($this->isAdminMessage) {
            CAdminMessage::ShowMessage(
                [
                    'MESSAGE'        => $msg,
                    'TYPE'           => 'PROGRESS',
                    'PROGRESS_TOTAL' => $val,
                    'PROGRESS_VALUE' => $total,
                ],
            );
        } else {
            echo "$msg: $val/$total";
        }
    }
}
