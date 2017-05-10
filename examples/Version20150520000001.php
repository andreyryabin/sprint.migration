<?php

namespace Sprint\Migration;
use \Sprint\Migration\Helpers\IblockHelper;
use \Sprint\Migration\Helpers\EventHelper;
use \Sprint\Migration\Helpers\UserTypeEntityHelper;

class Version20150520000001 extends Version {

    protected $description = "Добавляем инфоблок новости, добавляем настройки SEO";

    public function up(){
        $helper = new HelperManager();

        $helper->Iblock()->addIblockTypeIfNotExists(array(
            'ID' => 'content',
            'LANG'=>Array(
                'en'=>Array(
                    'NAME'=>'Контент',
                    'SECTION_NAME'=>'Sections',
                    'ELEMENT_NAME'=>'Elements'
                ),
                'ru'=>Array(
                    'NAME'=>'Контент',
                    'SECTION_NAME'=>'Разделы',
                    'ELEMENT_NAME'=>'Элементы'
                ),
            ),
        ));

        $iblockId1 = $helper->Iblock()->addIblockIfNotExists(array(
            'NAME' => 'Новости',
            'CODE' => 'content_news',
            'IBLOCK_TYPE_ID' => 'content',
            'LIST_PAGE_URL' => '',
            'DETAIL_PAGE_URL' => '#SITE_DIR#/news/#ELEMENT_ID#',

            //SEO-настройки
            'IPROPERTY_TEMPLATES' => array(
                'SECTION_META_TITLE' => '123',
                'SECTION_META_KEYWORDS' => '123',
                'SECTION_META_DESCRIPTION' => '',
                'SECTION_PAGE_TITLE' => '',
                'ELEMENT_META_TITLE' => '',
                'ELEMENT_META_KEYWORDS' => '',
                'ELEMENT_META_DESCRIPTION' => '',
                'ELEMENT_PAGE_TITLE' => '',
                'SECTION_PICTURE_FILE_ALT' => '',
                'SECTION_PICTURE_FILE_TITLE' => '',
                'SECTION_PICTURE_FILE_NAME' => '',
                'SECTION_DETAIL_PICTURE_FILE_ALT' => '',
                'SECTION_DETAIL_PICTURE_FILE_TITLE' => '',
                'SECTION_DETAIL_PICTURE_FILE_NAME' => '',
                'ELEMENT_PREVIEW_PICTURE_FILE_ALT' => '',
                'ELEMENT_PREVIEW_PICTURE_FILE_TITLE' => '',
                'ELEMENT_PREVIEW_PICTURE_FILE_NAME' => '',
                'ELEMENT_DETAIL_PICTURE_FILE_ALT' => '',
                'ELEMENT_DETAIL_PICTURE_FILE_TITLE' => '',
                'ELEMENT_DETAIL_PICTURE_FILE_NAME' => '',
            )
        ));

        $helper->Iblock()->updateIblockFields($iblockId1, array(
            'CODE' => array(
                'DEFAULT_VALUE' => array(
                    'TRANSLITERATION' => 'Y',
                    'UNIQUE' => 'Y',
                )
            )
        ));

        $helper->Iblock()->addPropertyIfNotExists($iblockId1, array(
            'NAME' => 'Ссылка',
            'CODE' => 'LINK',
        ));

        $this->outSuccess('Инфоблок создан');

    }

    public function down(){
        $helper = new HelperManager();
        $ok = $helper->Iblock()->deleteIblockIfExists('content_news');

        if ($ok){
            $this->outSuccess('Инфоблок удален');
        } else {
            $this->outError('Ошибка удаления инфоблока');
        }
    }

}
