<?php

namespace Sprint\Migration;

class Version20150520000005 extends Version {

    protected $description = "Пример работы с highload-блоками";

    public function up(){

        $helper = new HelperManager();

        $hlblockId = $helper->Hlblock()->addHlblockIfNotExists(array(
            'NAME' => 'Test',
            'TABLE_NAME' => 'hl_test',
        ));

        $helper->UserTypeEntity()->addUserTypeEntityIfNotExists('HLBLOCK_' . $hlblockId, 'UF_NAME', array(
            'USER_TYPE_ID' => 'string'
        ));


        $helper->UserTypeEntity()->addUserTypeEntityIfNotExists('HLBLOCK_' . $hlblockId, 'UF_CODE', array(
            'USER_TYPE_ID' => 'string'
        ));

    }

    public function down(){
        //
    }

}
