<?php

namespace Sprint\Migration;

class Version20150520000004 extends Version {

    protected $description = "Пример настройки отображения списка и формы для элементов инфоблока в админке";

    public function up(){

        $helper = new HelperManager();

        $iblockId = $helper->Iblock()->getIblockId('content_news', 'content');
        $this->exitIfEmpty($iblockId, 'Инфоблок content_news не найден');

        $helper->AdminIblock()->buildElementForm($iblockId, array(
            'Tab1' => array(
                'ACTIVE|Активность',
                'ACTIVE_FROM',
                'ACTIVE_TO',
                'NAME|Название',
                'CODE|Символьный код',
                'SORT',
            ),
            'Tab2' => array(
                'PREVIEW_TEXT',
                'PROPERTY_LINK',
            )
        ));

        $helper->AdminIblock()->buildElementList($iblockId, array(
            'NAME',
            'SORT',
            'ID',
            'PROPERTY_LINK',
        ), array(
            'order' => 'desc',
            'by' => 'id',
            'page_size' => 10
        ));

    }

    public function down(){
        //
    }

}
