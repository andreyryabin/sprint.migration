<?php

namespace Sprint\Migration\Tables;

use Bitrix\Main\ORM\Data;

class OptionTable extends Data\DataManager
{
    public static function getTableName(): string
    {
        return 'b_option';
    }

    public static function getMap(): array
    {
        return [
            'MODULE_ID'   => [
                'data_type' => 'string',
                'primary'   => true,
            ],
            'NAME'        => [
                'data_type' => 'string',
                'primary'   => true,
            ],
            'VALUE'       => [
                'data_type' => 'string',
                'required'  => false,
            ],
            'DESCRIPTION' => [
                'data_type' => 'string',
                'required'  => false,
            ],
            'SITE_ID'     => [
                'data_type' => 'string',
                'required'  => false,
            ],
        ];
    }
}
