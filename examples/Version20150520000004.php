<?php

namespace Sprint\Migration;

class Version20150520000004 extends Version
{

    protected $description = "Пример отображения списка и формы для элементов инфоблока в админке";

    /**
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {

        $helper = $this->getHelperManager();

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
        ]);

        //Пример отображения списка элементов
        $helper->UserOptions()->saveElementList($iblockId, [
            'order' => 'desc',
            'by' => 'id',
            'page_size' => 10,
            'columns' => [
                'NAME',
                'SORT',
                'ID',
                'PROPERTY_LINK',
            ],
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
    }

    /**
     * @return bool|void
     */
    public function down()
    {
        //
    }

}
