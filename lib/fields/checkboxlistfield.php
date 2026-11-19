<?php

namespace Sprint\Migration\Fields;

use Sprint\Migration\Exceptions\ConsoleException;
use Sprint\Migration\Output\ConsoleOutput;

class CheckboxListField extends RadioListField
{
    public function getDefaultValue(): array
    {
        return [];
    }

    /**
     * @throws ConsoleException
     */
    public function getValueFromConsole(ConsoleOutput $output): mixed
    {
        $value = parent::getValueFromConsole($output);

        return array_filter(explode(' ', trim($value)));
    }
}