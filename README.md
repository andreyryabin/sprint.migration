Модуль миграций для CMS Битрикс ([http://www.1c-bitrix.ru/](http://www.1c-bitrix.ru/)), помогающий синхронизировать изменения между нескольким копиями бд. 
--------------------------------------------------------------------------------------------------------
* [http://marketplace.1c-bitrix.ru/solutions/sprint.migration/](http://marketplace.1c-bitrix.ru/solutions/sprint.migration/)
* [https://bitbucket.org/andrey_ryabin/sprint.migration](https://bitbucket.org/andrey_ryabin/sprint.migration)


Способы установки
-------------------------
* маркетплейс
* git submodule
* composer - composer require andreyryabin/sprint.migration


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
* **help**                          Показать список команд

Директория для миграций
-------------------------
**/local/php_interface/migrations**
или
**/bitrix/php_interface/migrations**

или указать свою директорию в файле настроек
**/local/php_interface/migrations.cfg.php**
или
**/bitrix/php_interface/migrations.cfg.php**

2 параметра 
* **migration_dir** - директория миграций относительно корня проекта
* **migration_template** - файл шаблона миграции относительно корня проекта

пример файла конфига:
```
#!php
<?php return array (
  'migration_dir' => '/../scripts/migration/sprint/',
);

```


Пример файла миграции:
-------------------------
/bitrix/php_interface/migrations/Version20140806034146.php

```
#!php

<?php
namespace Sprint\Migration;
class Version20140806034146 extends Version {
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


Ссылки
-------------------------
* [http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/11245/](http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/11245/)
* [http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/13788/](http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/13788/)
* [https://www.youtube.com/watch?v=uYZ8-XIre2Q](https://www.youtube.com/watch?v=uYZ8-XIre2Q)