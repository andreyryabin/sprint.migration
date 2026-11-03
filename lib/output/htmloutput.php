<?php

namespace Sprint\Migration\Output;

class HtmlOutput extends AbstractOutput
{

    private array $messages = [];

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function out(string $msg, ...$vars): void
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        $this->messages[] = $msg;
    }

    public function outProgress(string $msg, int $val, int $total): void
    {
        $msg = '[label]' . $msg . ' ' . $val . ' / ' . $total . '[/]';

        echo '<div class="sp-progress">' . $msg . '</div>';
    }

}
