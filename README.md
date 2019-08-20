# Миграции для разработчиков (1С-Битрикс) #

Помогает синхронизировать изменения между нескольким копиями базы данных

Все изменения для базы данных пишутся в файлы миграций, эти файлы, как и весь код проекта, хранятся в системе контроля версий (например git)
и попадают в копии разработчиков, после чего им необходимо выполнить установку новых миграций, чтобы обновить бд.

Работать можно как через консоль, так и через админку.

* Маркетплейс: [http://marketplace.1c-bitrix.ru/solutions/sprint.migration/](http://marketplace.1c-bitrix.ru/solutions/sprint.migration/)
* Composer: [https://packagist.org/packages/andreyryabin/sprint.migration](https://packagist.org/packages/andreyryabin/sprint.migration)
* Документация: [https://github.com/andreyryabin/sprint.migration/wiki](https://github.com/andreyryabin/sprint.migration/wiki)
* Материалы: [https://dev.1c-bitrix.ru/search/?tags=sprint.migration](https://dev.1c-bitrix.ru/search/?tags=sprint.migration)
* Группа в телеграм: [https://t-do.ru/sprint_migration_bitrix](https://t-do.ru/sprint_migration_bitrix)

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
создав файл в корне проекта, bin/migrate и прописав в нем:

```
#!/usr/bin/env php
<?php

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sprint.migration/tools/migrate.php';

```

Примеры команд
-------------------------
* php bin/migrate add (создать новую миграцию)
* php bin/migrate ls  (показать список миграций )
* php bin/migrate up (накатить все миграции) 
* php bin/migrate up [version] (накатить выбранную миграцию)
* php bin/migrate down (откатить все миграции)
* php bin/migrate down [version] (откатить выбранную миграцию)

Все команды: https://github.com/andreyryabin/sprint.migration/blob/master/commands.txt


Тегирование миграций
-------------------------
При установке новых миграций их можно пометить произвольным тегом: 
php bin/migrate up --add-tag=release001

Это бывает удобно в случае отката релиза, когда требуется вернуть его в начальное состояние, 
при условии что написан код отката 

Откат миграций по тегу:
php bin/migrate down --tag=release001


Скриншоты
-------------------------
Админка миграций
![bitrix-sprint-migration-1.png](https://raw.githubusercontent.com/wiki/andreyryabin/sprint.migration/assets/bitrix-sprint-migration-1.png)

Формы создания миграций
![bitrix-sprint-migration-2.png](https://raw.githubusercontent.com/wiki/andreyryabin/sprint.migration/assets/bitrix-sprint-migration-2.png)

