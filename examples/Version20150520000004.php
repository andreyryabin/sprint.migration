<?php

namespace Sprint\Migration;

class Version20150520000004 extends Version {

    protected $description = "Пример настройки отображения списка и формы для элементов инфоблока в админке";

    public function up(){

        $helper = new HelperManager();

        $iblockId = $helper->Iblock()->getIblockId('content_news', 'content');

        $helper->AdminIblock()->buildElementForm($iblockId, array(
            'Tab1' => array(
                'ID' => '*',
                'ACTIVE' => '*',
                'DATE_ACTIVE_FROM' => '*',
                'NAME' => '*',
                'SORT' => 'Сортировка',
            ),
            'Tab2' => array(
                'CODE' => 'Код',
                'PREVIEW_TEXT' => '*',
                'PROPERTY_LINK' => '*',
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
