<?php

namespace Sprint\Migration;


class Version20170213000009 extends Version
{

    protected $description = "Пример работы с почтовыми шаблонами";

    /**
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();

        $helper->Event()->saveEventType('NEW_MATERIAL', [
            'LID' => 'ru',
            'NAME' => 'Добавлен новый материал',
            'DESCRIPTION' => $this->getDesc(),
        ]);

        $helper->Event()->saveEventType('NEW_MATERIAL', [
            'LID' => 'en',
            'NAME' => 'Добавлен новый материал',
            'DESCRIPTION' => $this->getDesc(),
        ]);


        $helper->Event()->saveEventMessage('NEW_MATERIAL', [
            'ACTIVE' => 'Y',
            'LID' => 's1',
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => 'user@example.com',
            'BCC' => '',
            'SUBJECT' => 'Добавлен новый материал',
            'BODY_TYPE' => 'text',
            'MESSAGE' => $this->getMessage(),
        ]);

    }

    /**
     * @return bool|void
     */
    public function down()
    {
        //your code ...
    }


    private function getDesc()
    {
        return <<<TEXT1
#NAME# - Название
#TAG_NAME# - Тема
#PREVIEW_TEXT# - Комментарий
#URL# - Ссылка на материал     

TEXT1;
    }

    private function getMessage()
    {
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
