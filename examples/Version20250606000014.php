<?php

namespace Sprint\Migration;

class Version20250606000014 extends Version
{
    protected $description = "Пример зависимости миграции от других";


    public function up()
    {

        // данная проверка бросит исключение если указанные миграции не установлены
        $this->checkRequiredVersions(['Version20190606000013', Version20190606000012::class]);

    }

    public function down()
    {
        //your code ...
    }
}
