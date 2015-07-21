Модуль миграций для CMS Битрикс ([http://www.1c-bitrix.ru/](http://www.1c-bitrix.ru/)), помогающий синхронизировать изменения между нескольким копиями бд. 
--------------------------------------------------------------------------------------------------------
* Маркетплейс 1с-Битрикс: [http://marketplace.1c-bitrix.ru/solutions/sprint.migration/](http://marketplace.1c-bitrix.ru/solutions/sprint.migration/)
* Репозиторий проекта: [https://bitbucket.org/andrey_ryabin/sprint.migration](https://bitbucket.org/andrey_ryabin/sprint.migration)
* Composer пакет: [https://packagist.org/packages/andreyryabin/sprint.migration](https://packagist.org/packages/andreyryabin/sprint.migration)


При установке модуля через composer (composer require andreyryabin/sprint.migration)
самостоятельно сделайте симлинк директории vendor/andreyryabin/sprint.migration
в директорию local/modules/sprint.migration или bitrix/modules/sprint.migration

Консоль
-------------------------
Создаем скрипт migrate.php для запуска миграций через консоль

его содержимое:


```
#!php

#!/usr/bin/env php
<?php

$_SERVER["DOCUMENT_ROOT"] = __DIR__ . '/../htdocs/';
require($_SERVER["DOCUMENT_ROOT"]."local/modules/sprint.migration/tools/migrate.php");

```

Пример вызова команд
-------------------------
* php migrate.php migrate
* php migrate.php execute Version20150119122646 --down
* php migrate.php up 3

Доступные команды
-------------------------
* **create**                        Создать файл с миграцией
* create <description>              Создать файл с миграцией и описанием
* **status**                        Суммарная статистика по миграциям
* **list**                          Список миграций
* **migrate**                       Накатить  все миграции
* migrate --up                      Накатить  все миграции
* migrate --down                    Откатить  все миграции
* **up**                            Накатить  одну миграцию
* up <limit>                        Накатить  несколько миграций
* **down**                          Откатить  одну миграцию
* down <limit>                      Откатить  несколько миграций
* **execute** <version>             Накатить миграцию
* execute <version> --up            Накатить миграцию
* execute <version> --down          Откатить миграцию
* **redo** <version>                Откатить + накатить миграцию
* **info** <version>                Получить описание миграции
* **help**                          Показать список команд

Директория для миграций
-------------------------
по умолчанию: **/local/php_interface/migrations** или **/bitrix/php_interface/migrations**

или указать свою директорию в файле настроек
**/local/php_interface/migrations.cfg.php** или **/bitrix/php_interface/migrations.cfg.php**

```
#!php
<?php return array (
  'migration_dir' => '/../scripts/migration/sprint/',
);

```


Пример файла миграции:
-------------------------
/bitrix/php_interface/migrations/Version20150520000001.php

```
#!php

<?php
namespace Sprint\Migration;

class Version20150520000001 extends Version {

    protected $description = "";

    public function up(){
        //
    }

    public function down(){
        //
    }
}
```

в нем необходимо реализовать 2 метода up и down - которые запускаются при накате и откате миграции,
например создание инфоблоков или какое либо другое изменение, которое должны получить все копии проекта.


Админка
-------------------------
![админка](https://bitbucket.org/repo/aejkky/images/1841502107-gkrDVvOs9MQ62p.jpg)

Полезные ссылки
-------------------------
* Механизм миграций, обзорная статья: [http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/11245/](http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/11245/)
* Пошаговое выполнение миграции, примеры скриптов, видео: [http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/13788/](http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/13788/)