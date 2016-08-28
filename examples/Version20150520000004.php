<?php

namespace Sprint\Migration;

class Version20150520000004 extends Version {

    protected $description = "Пример настройки отображения списка и формы для элементов инфоблока в админке";

    public function up(){

        $helper = new HelperManager();

        $helper->adminIblock()->buildElementForm(3, array(
            'Tab1' => array(
                'NAME' => '*',
                'SORT' => 'Сортировка',
            ),
            'Tab2' => array(
                'CODE' => 'Код',
                'PROPERTY_MENU_TOP' => '*',
                'PROPERTY_3' => 'PROPERTY_3',
            )
        ));

        $helper->adminIblock()->buildElementList(3, array(
            'NAME',
            'SORT',
            'ID',
            'PROPERTY_MENU_TOP',
            'PROPERTY_3',
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
