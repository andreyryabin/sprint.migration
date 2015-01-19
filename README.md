Модуль содержит механизм миграций, помогающий синхронизировать изменения между нескольким копиями бд. 
--------------------------------------------------------------------------------------------------------

Вики модуля [https://bitbucket.org/andrey_ryabin/sprint.migration/wiki/Home](https://bitbucket.org/andrey_ryabin/sprint.migration/wiki/Home)


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