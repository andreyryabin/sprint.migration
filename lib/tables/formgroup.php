<?php

namespace Sprint\Migration\Tables;

use Bitrix\Main\Entity\DataManager;

class FormGroupTable extends DataManager
{
    public static function getTableName()
    {
        return 'b_form_2_group';
    }

    public static function getMap()
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
