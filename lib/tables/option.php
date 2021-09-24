<?php

namespace Sprint\Migration\Tables;

use Bitrix\Main\Entity\DataManager;

/**
 * @deprecated Класс больше не используется
 */
class OptionTable extends DataManager
{

    public static function getTableName()
    {
        return 'b_option';
    }

    public static function getMap()
    {
        return [
            'MODULE_ID' => [
                'data_type' => 'string',
                'primary' => true,
            ],
            'NAME' => [
                'data_type' => 'string',
                'primary' => true,
            ],
            'VALUE' => [
                'data_type' => 'string',
                'required' => false,
            ],
            'DESCRIPTION' => [
                'data_type' => 'string',
                'required' => false,
            ],
            'SITE_ID' => [
                'data_type' => 'string',
                'required' => false,
            ],
        ];
    }


}
