<?php

namespace Sprint\Migration;

class Version20150520000003 extends Version {

    protected $description = "Примеры вывода сообщений";

    public function up(){
        $this->out('Примеры вывода сообщений');
        $this->out('Используется [b]sprintf[/] на самом деле, напишем пару чисел %d %d и строку %s', 199, 200, 'hello');
        $this->out('Но еще можно [red]раскрашивать[/] и [b]жирным[/] делать например');
        $this->out('А если [blue]выполняете в админке[/], увидите нативные сообщения');
        $this->outSuccess('Все готово на %d%%', 100);
        $this->outError('Ошибка');
        $this->outProgress('Прогресс', 10, 100);
    }

    public function down(){
    }

}
