<?php

namespace Sprint\Migration\Output;

class ConsoleOutput extends AbstractOutput
{
    private array $colors  = [
        '/'            => "\x1b[0m",
        'tab'          => "\x1b[0m",
        'unknown'      => "\x1b[0;34m",
        'installed'    => "\x1b[0;32m",
        'new'          => "\x1b[0;31m",
        'blue'         => "\x1b[0;34m",
        'pink'         => "\x1b[0;35m",
        'green'        => "\x1b[0;32m",
        'up'           => "\x1b[0;32m",
        'red'          => "\x1b[0;31m",
        'down'         => "\x1b[0;31m",
        'yellow'       => "\x1b[0;93m",
        'label'        => "\x1b[47;30m",
        'label:blue'   => "\x1b[104;30m",
        'label:pink'   => "\x1b[105;30m",
        'label:red'    => "\x1b[41;37m",
        'label:green'  => "\x1b[102;30m",
        'label:yellow' => "\x1b[103;30m",
        'b'            => "\x1b[1m",
    ];
    private bool  $needEol = false;

    public function outProgress(string $msg, int $val, int $total): void
    {
        $this->needEol = true;
        $msg = '[label]' . $msg . ' ' . $val . ' / ' . $total . '[/]';

        fwrite(STDOUT, "\r" . $this->applyFormat($msg));
    }

    private function applyFormat($msg)
    {
        foreach ($this->colors as $key => $val) {
            $msg = str_replace('[' . $key . ']', $val, $msg);
        }

        return $msg;
    }

    public function out(string $msg, ...$vars): void
    {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        $msg = $this->applyFormat($msg);

        if ($this->needEol) {
            $this->needEol = false;
            fwrite(STDOUT, PHP_EOL . $msg . PHP_EOL);
        } elseif ($msg) {
            fwrite(STDOUT, $msg . PHP_EOL);
        }
    }

    public function input($field): string
    {
        if (!empty($field['items'])) {
            $this->outStructure($field);
        } elseif (!empty($field['select'])) {
            $this->outSelect($field);
        }

        fwrite(STDOUT, PHP_EOL . $field['title'] . ':');

        $val = fgets(STDIN);
        $val = trim($val);

        if ($field['multiple']) {
            $val = explode(' ', $val);
            $val = array_filter($val);
        }

        return $val;
    }

    private function outStructure($field): void
    {
        foreach ($field['items'] as $group) {
            $this->out('---' . $group['title']);
            foreach ($group['items'] as $item) {
                $this->out(' > ' . $item['value'] . ' (' . $item['title'] . ')');
            }
        }
    }

    private function outSelect($field): void
    {
        foreach ($field['select'] as $item) {
            $this->out(' > ' . $item['value'] . ' (' . $item['title'] . ')');
        }
    }
}
