# Миграции для разработчиков (1С-Битрикс) #

* Маркетплейс: [http://marketplace.1c-bitrix.ru/solutions/sprint.migration/](http://marketplace.1c-bitrix.ru/solutions/sprint.migration/)
* Composer: [https://packagist.org/packages/andreyryabin/sprint.migration](https://packagist.org/packages/andreyryabin/sprint.migration)
* Документация: [https://github.com/andreyryabin/sprint.migration/wiki](https://github.com/andreyryabin/sprint.migration/wiki)
* Материалы: [https://dev.1c-bitrix.ru/search/?tags=sprint.migration](https://dev.1c-bitrix.ru/search/?tags=sprint.migration)

Установка через composer
-------------------------
Пример вашего composer.json с установкой модуля в local/modules/
```
{
  "extra": {
    "installer-paths": {
      "local/modules/{$name}/": ["type:bitrix-module"]
    }
  },
  "require": {
    "andreyryabin/sprint.migration": "dev-master"
  },
}

```

Консоль
-------------------------
Для работы через консоль используется скрипт 
/bitrix/modules/sprint.migration/tools/migrate.php

Можно запускать его напрямую или сделать алиас, 
создав файл, например, /local/bin/migrate.php и прописав в нем:

```
#!/usr/bin/env php
<?php

require_once __DIR__ . '/../../bitrix/modules/sprint.migration/tools/migrate.php';

```

Примеры команд
-------------------------
* php migrate.php add (создать новую миграцию)
* php migrate.php ls  (показать список миграций )
* php migrate.php up (накатить все миграции) 
* php migrate.php up <version> (накатить выбранную миграцию)
* php migrate.php down (откатить все миграции)
* php migrate.php down <version> (откатить выбранную миграцию)

Все команды: https://github.com/andreyryabin/sprint.migration/blob/master/commands.txt


Скриншоты
-------------------------
![админка](https://bitbucket.org/repo/aejkky/images/4102016731-admin-interface.png)