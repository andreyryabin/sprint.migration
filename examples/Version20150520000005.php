<?php

namespace Sprint\Migration;

class Version20150520000005 extends Version
{

    protected $description = "Пример работы с highload-блоками";

    public function up()
    {

        $helper = $this->getHelperManager();

        $hlblockId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'Test',
            'TABLE_NAME' => 'hl_test',
        ]);

        $helper->Hlblock()->saveField($hlblockId, 'UF_NAME', [
            'USER_TYPE_ID' => 'string',
        ]);

        $helper->Hlblock()->saveField($hlblockId, 'UF_CODE', [
            'USER_TYPE_ID' => 'string',
        ]);

    }

    public function down()
    {
        //your code ...
    }

}
