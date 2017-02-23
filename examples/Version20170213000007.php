<?php

namespace Sprint\Migration;


class Version20170213000007 extends Version {

    protected $description = "Пример работы с шаблонами сайта";

    public function up(){
        $helper = new HelperManager();

        //получить список шаблонов
        $helper->Site()->getSiteTemplates('s1');


        //задать шаблоны
        $helper->Site()->setSiteTemplates('s1', array(

            //Для папки или файла
            array(
                'TEMPLATE' => 'main',
                'IN_DIR' => '/auth.php',
            ),

            //Период времени
            array(
                'TEMPLATE' => 'main',
                'IN_PERIOD' => array('02.03.2017', '02.05.2017'),
            ),

            //Для групп пользователей
            array(
                'TEMPLATE' => 'main',
                'IN_GROUP' => array(1,2,3),
            ),

            //Параметр в URL
            array(
                'TEMPLATE' => 'main',
                'GET_PARAM' => array('print' => 'Y'),
            ),

            //Выражение PHP
            array(
                'TEMPLATE' => 'main',
                'CONDITION' => 'empty(1)',
            ),

            //Без условия
            array(
                'TEMPLATE' => 'main',
                'CONDITION' => '',
            ),
        ));

    }

    public function down(){
        //
    }

}
