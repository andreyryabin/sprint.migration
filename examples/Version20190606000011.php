<?php

namespace Sprint\Migration;


use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Exception;


class Version20190606000011 extends Version
{

    protected $description = "Пример работы с доступами групп к инфоблоками и highload-блоками";

    /**
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws Exception
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        //выберем группу по коду
        $groupId = $helper->UserGroup()->getGroupId('content_editor');

        //выставим ей права на запись на все инфоблоки
        $iblocks = $helper->Iblock()->getIblocks();

        foreach ($iblocks as $iblock) {
            $permissions = $helper->Iblock()->getGroupPermissions($iblock['ID']);
            $permissions[$groupId] = 'W';
            $helper->Iblock()->setGroupPermissions($iblock['ID'], $permissions);
        }

        //выставим ей права на запись на все хайлоадблоки
        $hlblocks = $helper->Hlblock()->getHlblocks();

        foreach ($hlblocks as $hlblock) {
            $permissions = $helper->Hlblock()->getGroupPermissions($hlblock['ID']);
            $permissions[$groupId] = 'W';
            $helper->Hlblock()->setGroupPermissions($hlblock['ID'], $permissions);
        }
    }

    /**
     * @return bool|void
     */
    public function down()
    {
        //your code ...
    }
}
