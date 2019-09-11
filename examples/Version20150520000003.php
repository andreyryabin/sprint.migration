<?php

namespace Sprint\Migration;

class Version20150520000003 extends Version
{

    protected $description = "Примеры вывода сообщений";

    /**
     * @return bool|void
     */
    public function up()
    {
        $this->out('Примеры вывода сообщений');
        $this->out('Используется [b]sprintf[/], напишем пару чисел %d %d и строку %s', 199, 200, 'hello');
        $this->out('[blue]Но еще можно[/] [red]раскрашивать[/] и делать [b]жирным[/]');
        $this->outSuccess('Все готово на %d%%', 100);
        $this->outError('Ошибка');
        $this->outProgress('Прогресс', 10, 100);
    }

    /**
     * @return bool|void
     */
    public function down()
    {
        //your code ...
    }

}
