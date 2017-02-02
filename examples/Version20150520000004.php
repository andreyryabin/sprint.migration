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
                'ACTIVE' => 'Активность',
                'ACTIVE_FROM',
                'ACTIVE_TO',
                'NAME' => 'Название',
                'CODE' => 'Символьный код',
                'SORT',
            ),
            'Tab2' => array(
                'PREVIEW_TEXT',
                'PROPERTY_LINK',
            ),
            'SEO' => array()
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


        //пример с обновлениями нескольких полей
        $tabs = $helper->AdminIblock()->extractElementForm($iblockId);

        unset($tabs['Tab2']['PREVIEW_TEXT']);

        $tabs['Tab1']['SECTIONS'] = 'Разделы';

        $helper->AdminIblock()->buildElementForm($iblockId, $tabs);

    }

    public function down(){
        //
    }

}
