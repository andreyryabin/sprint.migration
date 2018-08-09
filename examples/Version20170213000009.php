<?php

namespace Sprint\Migration;


class Version20170213000009 extends Version
{

    protected $description = "Пример работы с почтовыми шаблонами";

    public function up() {
        $helper = new HelperManager();

        $helper->Event()->addEventTypeIfNotExists('NEW_MATERIAL', array(
            'LID' => 'ru',
            'NAME' => 'Добавлен новый материал',
            'DESCRIPTION' => $this->getDesc(),
        ));

        $helper->Event()->addEventTypeIfNotExists('NEW_MATERIAL', array(
            'LID' => 'en',
            'NAME' => 'Добавлен новый материал',
            'DESCRIPTION' => $this->getDesc(),
        ));


        $helper->Event()->addEventMessageIfNotExists('NEW_MATERIAL', array(
            'ACTIVE' => 'Y',
            'LID' => 's1',
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => 'user@example.com',
            'BCC' => '',
            'SUBJECT' => 'Добавлен новый материал',
            'BODY_TYPE' => 'text',
            'MESSAGE' => $this->getMessage(),
        ));

    }

    public function down() {
        //
    }


    private function getDesc() {
        return <<<TEXT1
#NAME# - Название
#TAG_NAME# - Тема
#PREVIEW_TEXT# - Комментарий
#URL# - Ссылка на материал     

TEXT1;

    }

    private function getMessage() {
        return <<<TEXT2
Название
#NAME#

Тема
#TAG_NAME#

Комментарий
#PREVIEW_TEXT#

Ссылка на материал
#URL#

TEXT2;


    }
}
