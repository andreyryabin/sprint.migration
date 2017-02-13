<?php

namespace Sprint\Migration;

class Version20170213000006 extends Version {

    protected $description = "Пример работы с highload-блоками # 2";

    public function up(){
        $helper = new HelperManager();
        $hlblockId = $helper->Hlblock()->addHlblockIfNotExists(array(
            'NAME' => 'Test',
            'TABLE_NAME' => 'hl_test',
        ));

        $helper->UserTypeEntity()->addUserTypeEntitiesIfNotExists(
            'HLBLOCK_' . $hlblockId,
            [
                ['FIELD_NAME' => 'UF_NAME', "USER_TYPE_ID" => "string"],
                ['FIELD_NAME' => 'UF_PRICE', "USER_TYPE_ID" => "integer"],
                ['FIELD_NAME' => 'UF_WEIGHT', "USER_TYPE_ID" => "double"],
                ['FIELD_NAME' => 'UF_CREATED_AT', "USER_TYPE_ID" => "datetime"],
                ['FIELD_NAME' => 'UF_UPDATED_AT', "USER_TYPE_ID" => "datetime"],
            ]
        );
    }

    public function down(){
        $helper = new HelperManager();
        if ($hlblockId = $helper->Hlblock()->getHlblockId('Test')) {
            $helper->UserTypeEntity()->deleteUserTypeEntitiesIfExists(
                'HLBLOCK_' . $hlblockId,
                [
                    'UF_NAME',
                    'UF_PRICE',
                    'UF_WEIGHT',
                    'UF_CREATED_AT',
                    'UF_UPDATED_AT',
                ]
            );
            $helper->Hlblock()->deleteHlblock($hlblockId);
        }
    }

}
