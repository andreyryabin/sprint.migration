Модуль содержит механизм миграций, помогающий синхронизировать изменения между нескольким копиями бд. 
--------------------------------------------------------------------------------------------------------

создаем скрипт для запуска миграций через консоль
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
Create <description> add new migration with description
Status get migrations list
Migrate --up --down - up or down all migrations
Up <count> up <count> migrations
Down <count> down <count> migrations
Execute <version> --up --down up or down this migration
Redo <version> down+up this migration
Help

пример вызова команд
-------------------------
php migrate.php migrate
php migrate.php execute Version20150119122646 --down
php migrate.php up 3