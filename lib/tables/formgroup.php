<?php

namespace Sprint\Migration\Tables;

use Bitrix\Main\ORM\Data;

class FormGroupTable extends Data\DataManager
{
    public static function getTableName(): string
    {
        return 'b_form_2_group';
    }

    public static function getMap(): array
    {
        return [
            'ID'         => [
                'data_type' => 'string',
                'primary'   => true,
            ],
            'FORM_ID'    => [
                'data_type' => 'string',
            ],
            'GROUP_ID'   => [
                'data_type' => 'string',
            ],
            'PERMISSION' => [
                'data_type' => 'string',
            ],

        ];
    }
}
