<?php

namespace Sprint\Migration\Tables;

use Bitrix\Main\Entity\DataManager;

class OptionTable extends DataManager
{

    public static function getTableName() {
        return 'b_option';
    }

    public static function getMap() {
        return array(
            'MODULE_ID' => array(
                'data_type' => 'string',
                'primary' => true,
            ),
            'NAME' => array(
                'data_type' => 'string',
                'primary' => true,
            ),
            'VALUE' => array(
                'data_type' => 'string',
                'required' => false,
            ),
            'DESCRIPTION' => array(
                'data_type' => 'string',
                'required' => false,
            ),
            'SITE_ID' => array(
                'data_type' => 'string',
                'required' => false,
            ),
        );
    }


}