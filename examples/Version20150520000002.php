<?php

namespace Sprint\Migration;

use CIBlockElement;

class Version20150520000002 extends Version
{

    protected $description = "Пошаговая миграция";

    /**
     * @throws Exceptions\HelperException
     * @throws Exceptions\RestartException
     * @return bool|void
     */
    public function up()
    {
        //Добавляем 100 элементов

        $helper = $this->getHelperManager();
        $iblockId1 = $helper->Iblock()->getIblockIdIfExists('content_news');

        if (!isset($this->params['add'])) {
            $this->params['add'] = 0;
        }

        $cnt = 100;

        if ($this->params['add'] <= $cnt) {
            $this->outProgress('Прогресс добавления', $this->params['add'], $cnt);

            $helper->Iblock()->addElement($iblockId1, ['NAME' => 'name' . microtime()]);

            $this->params['add']++;

            $this->restart();
        }

    }

    /**
     * @throws Exceptions\HelperException
     * @throws Exceptions\RestartException
     * @return bool|void
     */
    public function down()
    {
        //Удаляем все элементы по 10 штук за раз

        $helper = $this->getHelperManager();
        $iblockId1 = $helper->Iblock()->getIblockIdIfExists('content_news');

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbRes = CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId1], false, ['nTopCount' => 10]);

        $bFound = 0;

        while ($aItem = $dbRes->Fetch()) {
            $helper->Iblock()->deleteElement($aItem['ID']);
            $this->out('deleted %d', $aItem['ID']);
            $bFound++;
        }

        if ($bFound) {
            $this->restart();
        }

    }

}
