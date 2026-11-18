<?php

namespace Sprint\Migration\Output;


use CAdminMessage;

class BrowserOutput extends AbstractOutput
{
    private function hasAdmin(): bool
    {
        static $hasAdmin = null;
        return $hasAdmin ??= class_exists(CAdminMessage::class, false);
    }

    protected function writeMessage(string $msg, string $type): void
    {
        if ($this->hasAdmin()) {
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
        if ($this->hasAdmin()) {
            CAdminMessage::ShowMessage([
                'MESSAGE'        => $msg,
                'TYPE'           => 'PROGRESS',
                'PROGRESS_TOTAL' => $val,
                'PROGRESS_VALUE' => $total,
            ]);
        } else {
            echo "$msg: $val / $total";
        }
    }
}
