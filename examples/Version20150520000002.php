<?php

namespace Sprint\Migration;

class Version20150520000002 extends Version {

    protected $description = "Пошаговая миграция";


    public function up(){
        //Добавляем 100 элементов

        $helper = new HelperManager();
        $iblockId1 = $helper->Iblock()->getIblockId('content_news');
        $this->exitIfEmpty($iblockId1, 'Инфоблок content_news не найден');

        if (!isset($this->params['add'])){
            $this->params['add'] = 0;
        }

        $cnt = 100;

        if ($this->params['add'] <= $cnt){
            $this->outProgress('Прогресс добавления', $this->params['add'], $cnt);

            $helper->Iblock()->addElement($iblockId1, array('NAME' => 'name'.microtime()));

            $this->params['add']++;

            $this->restart();
        }

    }

    public function down(){
        //Удаляем все элементы по 10 штук за раз

        $helper = new HelperManager();
        $iblockId1 = $helper->Iblock()->getIblockId('content_news');
        $this->exitIfEmpty($iblockId1, 'Инфоблок content_news не найден');

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbRes = \CIBlockElement::GetList(array(), array('IBLOCK_ID' => $iblockId1), false, array('nTopCount' => 10));

        $bFound = 0;

        while ($aItem = $dbRes->Fetch()){
            $helper->Iblock()->deleteElement($aItem['ID']);
            $this->out('deleted %d', $aItem['ID']);
            $bFound++;
        }

        if ($bFound){
            $this->restart();
        }

    }

}
