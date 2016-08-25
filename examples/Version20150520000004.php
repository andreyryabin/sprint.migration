<?php

namespace Sprint\Migration;
use Sprint\Migration\Helpers\IblockElementAdminFormHelper;
use Sprint\Migration\Helpers\IblockElementAdminListHelper;
use \Sprint\Migration\Helpers\IblockHelper;
use \Sprint\Migration\Helpers\EventHelper;
use \Sprint\Migration\Helpers\UserTypeEntityHelper;

class Version20150520000004 extends Version {

    protected $description = "Пример настройки отображения списка и формы для элементов инфоблока в админке";

    public function up(){

        $adminListHelper = new IblockElementAdminListHelper(3);

        $adminListHelper->addColumn('ID');
        $adminListHelper->addColumn('NAME');
        $adminListHelper->setListSort('id', 'desc');
        $adminListHelper->setPageSize(50);

        $adminListHelper->execute();


        $adminFormHelper = new IblockElementAdminFormHelper(3);

        $adminFormHelper->addTab('Tab1');

        $adminFormHelper->addField('NAME');
        $adminFormHelper->addField('CODE');
        $adminFormHelper->addField('PREVIEW_PICTURE', 'Картинка');
        $adminFormHelper->addField('PROPERTY_XXX');

        $adminFormHelper->execute();

    }

    public function down(){
    }

}
