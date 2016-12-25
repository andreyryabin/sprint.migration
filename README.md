Модуль миграций для CMS Битрикс ([http://www.1c-bitrix.ru/](http://www.1c-bitrix.ru/)), помогающий синхронизировать изменения между нескольким копиями бд.
--------------------------------------------------------------------------------------------------------
* Маркетплейс 1с-Битрикс: [http://marketplace.1c-bitrix.ru/solutions/sprint.migration/](http://marketplace.1c-bitrix.ru/solutions/sprint.migration/)
* Репозиторий проекта (bitbucket): [https://bitbucket.org/andrey_ryabin/sprint.migration](https://bitbucket.org/andrey_ryabin/sprint.migration)
* Репозиторий проекта (github): [https://github.com/andreyryabin/sprint.migration](https://github.com/andreyryabin/sprint.migration)
* Composer пакет: [https://packagist.org/packages/andreyryabin/sprint.migration](https://packagist.org/packages/andreyryabin/sprint.migration)
* Rss обновлений модуля: [https://bitbucket.org/andrey_ryabin/sprint.migration/rss](https://bitbucket.org/andrey_ryabin/sprint.migration/rss)



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
Создаем скрипт migrate.php для запуска миграций через консоль

его содержимое:


```
#!/usr/bin/env php
<?php

require($_SERVER["DOCUMENT_ROOT"]."local/modules/sprint.migration/tools/migrate.php");

```

Примеры команд
-------------------------
* php migrate.php create (создать новую миграцию)
* php migrate.php list --search=text (список миграций отфильтрованных по названию и описанию)
* php migrate.php migrate (накатить все)
* php migrate.php execute <version> (накатить выбранную миграцию)
* php migrate.php mark <version> --as=installed (отметить миграцию как установленную не запуская ее)

Все команды: https://bitbucket.org/andrey_ryabin/sprint.migration/src/master/commands.txt?fileviewer=file-view-default


Пример файла миграции:
-------------------------
/bitrix/php_interface/migrations/Version20150520000001.php

```
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


Состояния миграций
-------------------------
* New                               Новая миграция (только файл)
* Installed                         Установленная миграция (файл + запись об установке)
* Unknown                           Неизвестная миграция (только запись об установке)

Сообщения выполняющихся миграций
-------------------------
* Version0000 (up) success          Установка миграции прошла успешно
* Version0000 (down) success        Откат миграции прошел успешно
* Version0000 (down) error: (text)  Откат миграции не произошел из-за ошибки (текст ошибки)

Конфиги модуля
-------------------------
Основной (по умолчанию)
**/local/php_interface/migrations.cfg.php** или **/bitrix/php_interface/migrations.cfg.php**

Дополнительные
**/local/php_interface/migrations.{NAME}.php** или **/bitrix/php_interface/migrations.{NAME}.php**


```
<?php return array (
  'migration_dir' => '',
  'migration_template' => '',
  'migration_table' => '',
  'migration_extend_class' => '',
  'title' => '',
  'version_prefix' => '',
  'tracker_task_url' => '',
);
```

**title** - название конфига

**migration_dir** - директория для миграций (относительно DOC_ROOT), по умолчанию: **/local/php_interface/migrations** или **/bitrix/php_interface/migrations**

**migration_table** - таблица в бд с миграциями, по умолчанию sprint_migration_versions

**migration_extend_class** - класс от которого наследуются миграции, по умолчанию Version (ваш класс должен наследоваться от Version)

**tracker_task_url** - шаблон для замены строк вида #номер_задачи на ссылки, в шаблоне должна быть конструкция $1, 
например http://www.redmine.org/issues/$1/, работает только в админке

**version_prefix** - Префикс в имени класса миграции, по умолчанию Version (полное имя класса состоит из префикса + даты)

Ни один из параметров не является обязательным.

При указании в конфиге несуществующей директорий для миграций (migration_dir) или таблицы в бд (migration_table) 
модуль создаст их.

Текущий конфиг помечается звездочкой * как в админке, в блоке конфигов, так и в консоли (команда config).

Переключить конфиг в админке можно нажав кнопку "переключить" напротив нужного конфига
В консоли - используя параметр --config={NAME} к любой команде

Примеры:
* php migrate.php config --config=release001 (переключить конфиг на release001 и посмотреть список конфигов)
* php migrate.php migrate --config=release001 (переключить конфиг на release001 и накатить все миграции)


Пример вашего класса от которого наследуются классы миграций
-------------------------

* укажите ваш класс в конфиге
```
<?php return array (
    'migration_extend_class' => '\Acme\MyVersion as MyVersion',
);
```
или

```
<?php return array (
    'migration_extend_class' => '\Acme\MyVersion',
);
```

* создайте этот класс
```
<?php
namespace Acme;
use \Sprint\Migration\Version;

class MyVersion extends Version
{
    // ваш код
}
```

* создайте миграцю migrate.php create, результат:
```
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
* конфигах migrations.{NAME}.php

Миграция не выполняется если в методах up() или down()
вызывается return false или произошло исключение, в остальных случаях миграция выполняется успешно

Миграции при установке всех сразу выполняются от более старых к новым, при откате наоборот - от более новых к старым

При установке\откате всех миграций сразу, в случае если одну из миграций не удается выполнить, она пропускается и выполняются следующие миграции после нее, это сделано намеренно чтобы не стопорилась установка\откат миграций. 

Поэтому код миграций должен быть независимым друг от друга, например если в одной миграции создается инфоблок, то в другой, перед тем как добавить какое-либо свойство, необходимо проверить существование этого инфоблока.

Такие методы есть в Sprint\Migration\Helpers\IblockHelper, например addIblockIfNotExists, addPropertyIfNotExists




Скриншоты
-------------------------
![админка](https://bitbucket.org/repo/aejkky/images/4102016731-admin-interface.png)


Полезные ссылки
-------------------------
* Механизм миграций, обзорная статья: [http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/11245/](http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/11245/)
* Пошаговое выполнение миграции, примеры скриптов, видео: [http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/13788/](http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/13788/)
* Видео с работой модуля (интерфейс уже немного поменялся) [https://www.youtube.com/watch?v=uYZ8-XIre2Q](https://www.youtube.com/watch?v=uYZ8-XIre2Q)
* Пожелания и ошибки присылайте сюда: [https://bitbucket.org/andrey_ryabin/sprint.migration/issues/new](https://bitbucket.org/andrey_ryabin/sprint.migration/issues/new)
  