Модуль миграций для CMS Битрикс ([http://www.1c-bitrix.ru/](http://www.1c-bitrix.ru/)), помогающий синхронизировать изменения между нескольким копиями бд. 
--------------------------------------------------------------------------------------------------------
[http://marketplace.1c-bitrix.ru/solutions/sprint.migration/](http://marketplace.1c-bitrix.ru/solutions/sprint.migration/)
[https://bitbucket.org/andrey_ryabin/sprint.migration](https://bitbucket.org/andrey_ryabin/sprint.migration)

Создаем скрипт для запуска миграций через консоль
migrate.php

его содержимое:


```
#!php

#!/usr/bin/env php
<?php

$_SERVER["DOCUMENT_ROOT"] = __DIR__ . '/../htdocs/';
require($_SERVER["DOCUMENT_ROOT"]."local/modules/sprint.migration/tools/migrate.php");

```

Доступные команды
-------------------------
* **create** <description> add new migration with description
* **status** get migrations info
* **list** get migrations list
* **migrate** --up --down up or down all migrations
* **up** <limit> up limit migrations
* **down** <limit> down migrations
* **execute** <version> --up --down up or down this migration
* **redo** <version> down+up this migration

Пример вызова команд
-------------------------
* php migrate.php migrate
* php migrate.php execute Version20150119122646 --down
* php migrate.php up 3


Директория для миграций
-------------------------
**/local/php_interface/migrations**
или
**/bitrix/php_interface/migrations**
или 
**задается в настройках модуля**
настройки хранятся в файле /local/php_interface/migrations.cfg.php
или в /bitrix/php_interface/migrations.cfg.php

2 параметра 
* **migration_dir** - директория миграций относительно корня проекта
* **migration_template** - файл шаблона миграции относительно корня проекта

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
[http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/11245/](http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/11245/)
[http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/13788/](http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/13788/)
[https://www.youtube.com/watch?v=uYZ8-XIre2Q](https://www.youtube.com/watch?v=uYZ8-XIre2Q)
