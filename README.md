Модуль миграций для CMS Битрикс ([http://www.1c-bitrix.ru/](http://www.1c-bitrix.ru/)), помогающий синхронизировать изменения между нескольким копиями бд.
--------------------------------------------------------------------------------------------------------
* Маркетплейс 1с-Битрикс: [http://marketplace.1c-bitrix.ru/solutions/sprint.migration/](http://marketplace.1c-bitrix.ru/solutions/sprint.migration/)
* Репозиторий проекта: [https://bitbucket.org/andrey_ryabin/sprint.migration](https://bitbucket.org/andrey_ryabin/sprint.migration)
* Репозиторий проекта: [https://github.com/andreyryabin/sprint.migration](https://github.com/andreyryabin/sprint.migration)
* Composer пакет: [https://packagist.org/packages/andreyryabin/sprint.migration](https://packagist.org/packages/andreyryabin/sprint.migration)

Установка через composer
-------------------------
Пример вашего composer.json с установкой модуля в local/modules/
```
#!json
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
Создаем скрипт migrate.php для запуска миграций через консоль

его содержимое:


```
#!php

#!/usr/bin/env php
<?php

require($_SERVER["DOCUMENT_ROOT"]."local/modules/sprint.migration/tools/migrate.php");

```

Пример вызова команд
-------------------------
* php migrate.php migrate
* php migrate.php execute Version20150119122646 --down
* php migrate.php up 3

Доступные команды
-------------------------
https://bitbucket.org/andrey_ryabin/sprint.migration/src/master/commands.txt?fileviewer=file-view-default


Директория для миграций
-------------------------
по умолчанию: **/local/php_interface/migrations** или **/bitrix/php_interface/migrations**


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

в нем необходимо реализовать 2 метода up и down - которые запускаются при установке и откате миграции,
например создание инфоблоков или какое либо другое изменение, которое должны получить все копии проекта.



Конфиг модуля
-------------------------
Расположение
**/local/php_interface/migrations.cfg.php** или **/bitrix/php_interface/migrations.cfg.php**

```
#!php
<?php return array (
  'migration_dir' => '',
  'migration_template' => '',
  'migration_table' => '',
  'migration_extend_class' => '',
);
```

**migration_dir** - директория для миграций (относительно DOC_ROOT), по умолчанию local/php_interface/migrations или bitrix/php_interface/migrations

**migration_table** - таблица в бд с миграциями, по умолчанию sprint_migration_versions

**migration_extend_class** - класс от которого наследуются миграция, по умолчанию Version (ваш класс должен наследоваться от Version)


Ни один из параметров не является обязательным

Пример вашего класса от которого наследуются классы миграций

* укажите ваш класс в конфиге
```
#!php
<?php return array (
    'migration_extend_class' => '\Acme\MyVersion as MyVersion',
);
```
или

```
#!php
<?php return array (
    'migration_extend_class' => '\Acme\MyVersion',
);
```

* создайте этот класс
```
#!php
namespace Acme;
use \Sprint\Migration\Version;

class MyVersion extends Version
{
    // ваш код
}
```

* создайте миграцю migrate.php create, получится примерно так
```
#!php
<?php

namespace Sprint\Migration;
use \Acme\MyVersion as MyVersion;

class Version20151113185212 extends MyVersion {

    protected $description = "";

    public function up(){
        //
    }

    public function down(){
        //
    }

}
```


Информация для разработчиков
--------------------------------
Использовать какие-либо внутренние классы модуля не рекомендуется.

Совместимость от версии к версии модуля гарантируется для:
* методов класса Version (который наследуют ваши миграции)
* методов в хелперах (подключаются в классе миграции)
* консольных команд описанных в commands.txt
* конфига migrations.cfg.php

Миграция не выполняется если в методах up() или down()
вызывается return false или произошло исключение, в остальных случаях миграция выполняется успешно

Миграции при установке всех сразу выполняются от более старых к новым (в имени класса и файла зашита дата)
при откате наоборот - от более новых к старым

При установке\откате всех миграций сразу, в случае если одну из миграций не удается выполнить, она пропускается и выполняются следующие миграции после нее, это сделано намеренно чтобы не стопорилась установка\откат миграций. 

Поэтому код миграций должен быть независимым друг от друга, например если в одной миграции создается инфоблок, то в другой, перед тем как добавить какое-либо свойство, необходимо проверить существование этого инфоблока.

Такие методы есть в Sprint\Migration\Helpers\IblockHelper, например addIblockIfNotExists, addPropertyIfNotExists



Админка
-------------------------
![админка](https://bitbucket.org/repo/aejkky/images/1841502107-gkrDVvOs9MQ62p.jpg)

Полезные ссылки
-------------------------
* Механизм миграций, обзорная статья: [http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/11245/](http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/11245/)
* Пошаговое выполнение миграции, примеры скриптов, видео: [http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/13788/](http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/13788/)
* Видео с работой модуля (интерфейс уже немного поменялся) [https://www.youtube.com/watch?v=uYZ8-XIre2Q](https://www.youtube.com/watch?v=uYZ8-XIre2Q)
