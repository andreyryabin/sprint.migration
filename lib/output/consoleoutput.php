<?php

namespace Sprint\Migration\Output;

use Sprint\Migration\Exceptions\ConsoleException;

class ConsoleOutput extends AbstractOutput
{
    private array $colors = [
        '[/]'         => "\x1b[0m",
        '[new]'       => "\x1b[0;31m",
        '[unknown]'   => "\x1b[0;34m",
        '[installed]' => "\x1b[0;32m",

        '[info]'    => "\x1b[0;34m",
        '[warning]' => "\x1b[0;31m",
        '[notice]'  => "\x1b[0;32m",
        '[error]'   => "\x1b[41;37m",
        '[success]' => "\x1b[102;30m",

        '[blue]'   => "\x1b[0;34m",
        '[pink]'   => "\x1b[0;35m",
        '[green]'  => "\x1b[0;32m",
        '[red]'    => "\x1b[0;31m",
        '[yellow]' => "\x1b[0;93m",

        '[label]'        => "\x1b[47;30m",
        '[label:blue]'   => "\x1b[104;30m",
        '[label:pink]'   => "\x1b[105;30m",
        '[label:red]'    => "\x1b[41;37m",
        '[label:green]'  => "\x1b[102;30m",
        '[label:yellow]' => "\x1b[103;30m",

        '[b]'   => "\x1b[1m",
        '[tab]' => "\x1b[0m",
    ];

    protected function writeProgress(string $msg, int $val, int $total): void
    {
        fwrite(STDOUT, "\r" . $this->colorize("$msg $val / $total", 'label'));
        if ($val >= $total) {
            fwrite(STDOUT, PHP_EOL);
        }
    }


    protected function writeMessage(string $msg, string $type): void
    {
        $msg = $this->colorize($msg, $type);

        fwrite(STDOUT, $msg . PHP_EOL);

    }

    /**
     * @throws ConsoleException
     */
    public function input($field): string|array
    {
        if (!empty($field['items'])) {
            $this->outStructure($field);
        } elseif (!empty($field['select'])) {
            $this->outSelect($field);
        }

        fwrite(STDOUT, PHP_EOL . $field['title'] . ':');

        $val = fgets(STDIN);
        if ($val === false) {
            // stdin закрыт (запуск без терминала, перенаправление из /dev/null или конец ввода):
            // новых ответов не будет, иначе конструктор бесконечно повторял бы вопрос
            throw new ConsoleException('Input stream is closed, run the command in a terminal or pass the answers via stdin');
        }
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

    private function colorize(string $msg, string $type): string
    {
        if ($type) {
            $msg = "[$type]" . $msg . "[/]";
        }

        foreach ($this->colors as $key => $val) {
            $msg = str_replace('[' . $key . ']', $val, $msg);
        }

        return $msg;
    }
}
