<?php

namespace Sprint\Migration;

class Version20150520000004 extends Version
{

    protected $description = "Пример отображения списка и формы для элементов инфоблока в админке";

    public function up()
    {

        $helper = new HelperManager();

        $iblockId = $helper->Iblock()->getIblockIdIfExists('content_news', 'content');

        //Пример отображения формы редактирования элемента
        $helper->UserOptions()->saveElementForm($iblockId, [
            'Tab1' => [
                'ACTIVE' => 'Активность',
                'ACTIVE_FROM',
                'ACTIVE_TO',
                'NAME' => 'Название',
                'CODE' => 'Символьный код',
                'SORT',
            ],
            'Tab2' => [
                'PREVIEW_TEXT',
                'PROPERTY_LINK',
            ],
            'SEO' => [],
        ]);

        //Пример отображения формы редактирования категории
        $helper->UserOptions()->saveSectionForm($iblockId, [
            'Категория' => [
                'ID' => 'ID',
                'ACTIVE' => 'Раздел активен',
                'IBLOCK_SECTION_ID' => 'Родительский раздел',
                'NAME' => 'Название',
                'USER_FIELDS_ADD' => 'Добавить пользовательское свойство',
            ],
        ]);

        //Пример отображения списка элементов
        $helper->UserOptions()->saveElementList($iblockId, [
            'NAME',
            'SORT',
            'ID',
            'PROPERTY_LINK',
        ], [
            'order' => 'desc',
            'by' => 'id',
            'page_size' => 10,
        ]);


        //пример с обновлениями нескольких полей
        $tabs = $helper->UserOptions()->extractElementForm($iblockId);

        unset($tabs['Tab2']['PREVIEW_TEXT']);

        $tabs['Tab1']['SECTIONS'] = 'Разделы';

        $helper->UserOptions()->saveElementForm($iblockId, $tabs);

    }

    public function down()
    {
        //
    }

}
