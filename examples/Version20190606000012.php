<?php

namespace Sprint\Migration;

use Bitrix\Main\Db\SqlQueryException;

class Version20190606000012 extends Version
{
    protected $description = "Sql помощник";

    /**
     * @throws Exceptions\HelperException
     * @throws SqlQueryException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        //Пример работы с транзакциями
        $helper->Sql()->transaction(function () use ($helper) {
            $iblockId = $helper->Iblock()->getIblockId('content_news');

            $helper->Iblock()->addElement($iblockId, ['NAME' => 'Новость1']);
        });

        //Пример работы с индексами
        $helper->Sql()->addIndexIfNotExists('b_table_123', 'index_name', ['NAME']);
    }

    public function down()
    {
        //your code ...
    }
}
