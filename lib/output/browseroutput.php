<?php

namespace Sprint\Migration\Output;


use CAdminMessage;

class BrowserOutput extends AbstractOutput
{
    protected function writeMessage(string $msg, string $type): void
    {
        CAdminMessage::ShowMessage([
                                       'MESSAGE' => $msg,
                                       'TYPE' => (in_array($type, ['error', 'warning']) ? 'ERROR' : 'OK'),
                                   ]);
    }

    protected function writeProgress(string $msg, int $val, int $total): void
    {
        CAdminMessage::ShowMessage([
                                       'MESSAGE' => $msg,
                                       'TYPE' => 'PROGRESS',
                                       'PROGRESS_TOTAL' => $val,
                                       'PROGRESS_VALUE' => $total,
                                   ]);
    }
}
