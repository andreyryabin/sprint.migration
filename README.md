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
* **status** get migrations list
* **migrate** --up --down - up or down all migrations
* **up** <count> up <count> migrations
* **down** <count> down <count> migrations
* **execute** <version> --up --down up or down this migration
* **redo** <version> down+up this migration
* **help**

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
![админка](https://bitbucket.org/repo/aejkky/images/2628633264-gV2VywBFGajlmv.jpg)