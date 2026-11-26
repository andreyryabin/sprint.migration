<?php

namespace Sprint\Migration\Output;

use Sprint\Migration\Exceptions\ConsoleException;
use Sprint\Migration\Fields\BuilderField;
use Sprint\Migration\Fields\CheckboxListField;
use Sprint\Migration\Fields\RadioListField;
use Sprint\Migration\Fields\SelectField;
use Sprint\Migration\Interfaces\ConsoleInterface;

class ConsoleOutput extends AbstractOutput implements ConsoleInterface
{
    private array $colors = [
        '[new]'       => "\x1b[0;31m",
        '[unknown]'   => "\x1b[0;34m",
        '[installed]' => "\x1b[0;32m",

        '[info]'    => "\x1b[0;34m",
        '[warning]' => "\x1b[0;31m",
        '[notice]'  => "\x1b[0;32m",
        '[error]'   => "\x1b[0;31m",
        '[success]' => "\x1b[0;32m",

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

        '[b]' => "\x1b[1m",
        '[-]' => " - ",

        '[/]' => "\x1b[0m",
    ];


    /**
     * @throws ConsoleException
     */
    public function read(string $msg, ...$vars): string
    {
        $msg = $this->resolve($msg, $vars);
        $msg = $this->colorize($msg, '');
        fwrite(STDOUT, $msg);

        $val = fgets(STDIN);
        if ($val === false) {
            // stdin закрыт (запуск без терминала, перенаправление из /dev/null или конец ввода):
            // новых ответов не будет, иначе конструктор бесконечно повторял бы вопрос
            throw new ConsoleException(
                'Input stream is closed, run the command in a terminal or pass the answers via stdin',
            );
        }

        //защищает от разрыва строки и т.п.
        return trim($val);
    }

    public function lines(string $msg, string $lines): void
    {
        //консольный white-space: pre-line;

        $lines = $this->colorize($lines, '');

        $lines = array_filter(explode(PHP_EOL, trim($lines)), fn($v) => $v !== '');

        foreach ($lines as $line) {
            fwrite(STDOUT, $msg . $line . PHP_EOL);
        }
    }

    /**
     * @throws ConsoleException
     */
    public function readFieldValue(BuilderField $field, string $code): string
    {
        if ($field instanceof CheckboxListField) {
            $this->printSelectField($field, $code);
            return $this->read("Select any (separated by space): ");
        }

        if ($field instanceof RadioListField) {
            $this->printSelectField($field, $code);
            return $this->read("Select one: ");
        }

        if ($field instanceof SelectField) {
            $this->printSelectField($field, $code);
            return $this->read("Select one: ");
        }

        return $this->read("[pink](%s) - %s:[/] ", $code, $field->getTitle());
    }

    private function printSelectField(SelectField $field, string $code): void
    {
        $this->print("[pink](%s) - %s[/]", $code, $field->getTitle());
        $grouped = [];
        foreach ($field->getOptions() as $item) {
            $group = $item['group'] ?? '-';
            $grouped[$group][] = $item;
        }

        foreach ($grouped as $groupName => $groupItems) {
            if ($groupName !== '-') {
                $this->print('[blue]  %s[/]', $groupName);
            }
            foreach ($groupItems as $item) {
                $this->print('    ([yellow]%s[/]) %s', $item['id'], $item['name']);
            }
        }
    }

    protected function writeMessage(string $msg, string $type): void
    {
        $msg = $this->colorize($msg, $type);

        fwrite(STDOUT, $msg . PHP_EOL);
    }

    protected function writeProgress(string $msg, int $val, int $total): void
    {
        $msg = $this->colorize("$msg: $val / $total", 'label');

        fwrite(STDOUT, "\r" . $msg);
        if ($val >= $total) {
            fwrite(STDOUT, PHP_EOL);
        }
    }

    private function colorize(string $msg, string $type): string
    {
        return str_replace(
            array_keys($this->colors),
            array_values($this->colors),
            $type ? "[$type]{$msg}[/]" : $msg,
        );
    }

}
