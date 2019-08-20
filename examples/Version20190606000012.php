<?php

namespace Sprint\Migration;


use Bitrix\Main\Db\SqlQueryException;

class Version20190606000012 extends Version
{

    protected $description = "Пример работы с транзакциями";

    /**
     * @throws Exceptions\HelperException
     * @throws SqlQueryException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $helper->Sql()->transaction(function () use ($helper) {

            $iblockId = $helper->Iblock()->getIblockId('content_news');

            $helper->Iblock()->addElement($iblockId, ['NAME' => 'Новость1']);

        });
    }

    public function down()
    {
        //your code ...
    }
}
