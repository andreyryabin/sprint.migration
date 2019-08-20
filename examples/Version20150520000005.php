<?php

namespace Sprint\Migration;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;

class Version20150520000005 extends Version
{

    protected $description = "Пример работы с highload-блоками";

    /**
     * @throws Exceptions\HelperException
     * @throws ArgumentException
     * @throws SystemException
     * @return bool|void
     */
    public function up()
    {

        $helper = $this->getHelperManager();

        $hlblockId = $helper->Hlblock()->saveHlblock([
            'NAME' => 'Test',
            'TABLE_NAME' => 'hl_test',
        ]);

        $helper->Hlblock()->saveField($hlblockId, [

            'USER_TYPE_ID' => 'string',
            'FIELD_NAME' => 'UF_NAME',
        ]);

        $helper->Hlblock()->saveField($hlblockId, [
            'USER_TYPE_ID' => 'string',
            'FIELD_NAME' => 'UF_CODE',
        ]);

    }

    /**
     * @throws Exceptions\HelperException
     * @throws Exceptions\RestartException
     * @return bool|void
     */
    public function down()
    {
        //your code ...
    }

}
