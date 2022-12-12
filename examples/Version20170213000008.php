<?php

namespace Sprint\Migration;

class Version20170213000008 extends Version
{
    protected $description = "Пример работы миграции с сохранением промежуточных данных в бд";

    /**
     * @throws Exceptions\MigrationException
     * @return bool|void
     */
    public function up()
    {
        //сохраняем данные этой миграции
        $storage1 = $this->getStorageManager();

        $storage1->saveData('var1', '1234567');
        $storage1->saveData('var2', [
            'bbb'  => 'axcx',
            'bbbb' => 'axcx',
        ]);

        //получаем данные этой миграции
        $var1 = $storage1->getSavedData('var1');
        $var2 = $storage1->getSavedData('var2');

        //удаляем выбранные данные этой миграции
        $storage1->deleteSavedData('var1');

        //удаляем все данные этой миграции
        $storage1->deleteSavedData();

        //получаем сохраненные данные какой-либо другой миграции
        $storage2 = $this->getStorageManager('Version20170213000007');
        $var1 = $storage2->getSavedData('var1');
    }

    /**
     * @return bool|void
     */
    public function down()
    {
        //your code ...
    }
}
