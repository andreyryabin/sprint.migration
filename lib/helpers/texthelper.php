<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class TextHelper extends Helper
{

    public function explodeValues(
        string $input,
        string $separator = ' ',
        bool $numeric = false,
        bool $unique = true,
    ): array {
        $values = array_filter(explode($separator, trim($input)), fn($v) => $v !== '');

        if ($numeric) {
            $values = array_map('intval', array_filter($values, 'ctype_digit'));
        }

        if ($unique) {
            $values = array_unique($values);
        }

        return array_values($values);
    }
}
