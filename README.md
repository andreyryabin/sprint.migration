# Миграции для разработчиков (1С-Битрикс) #

* Маркетплейс 1с-Битрикс: [http://marketplace.1c-bitrix.ru/solutions/sprint.migration/](http://marketplace.1c-bitrix.ru/solutions/sprint.migration/)
* Обновления: [http://marketplace.1c-bitrix.ru/solutions/sprint.migration/#tab-log-link](http://marketplace.1c-bitrix.ru/solutions/sprint.migration/#tab-log-link)
* Репозиторий проекта (bitbucket): [https://bitbucket.org/andrey_ryabin/sprint.migration](https://bitbucket.org/andrey_ryabin/sprint.migration)
* Репозиторий проекта (github): [https://github.com/andreyryabin/sprint.migration](https://github.com/andreyryabin/sprint.migration)
* Composer пакет: [https://packagist.org/packages/andreyryabin/sprint.migration](https://packagist.org/packages/andreyryabin/sprint.migration)
* Rss обновлений модуля: [https://bitbucket.org/andrey_ryabin/sprint.migration/rss](https://bitbucket.org/andrey_ryabin/sprint.migration/rss)
* Трекер задач: [https://bitbucket.org/andrey_ryabin/sprint.migration/issues/new](https://bitbucket.org/andrey_ryabin/sprint.migration/issues/new)
* Механизм миграций, обзорная статья: [http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/11245/](http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/11245/)
* Пошаговое выполнение миграции: [http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/13788/](http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/13788/)
* Архивирование старых миграций: [http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/20941/](http://dev.1c-bitrix.ru/community/webdev/user/39653/blog/20941/)
* Статьи по миграциям [https://dev.1c-bitrix.ru/search/?tags=sprint.migration](https://dev.1c-bitrix.ru/search/?tags=sprint.migration)
* Видео с работой модуля (интерфейс немного поменялся) [https://www.youtube.com/watch?v=uYZ8-XIre2Q](https://www.youtube.com/watch?v=uYZ8-XIre2Q)

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

require_once realpath(__DIR__) . '/local/modules/sprint.migration/tools/migrate.php';

```

Примеры команд
-------------------------
* php migrate.php create (создать новую миграцию)
* php migrate.php list --search=text (список миграций отфильтрованных по названию и описанию)
* php migrate.php migrate (накатить все)
* php migrate.php execute <version> (накатить выбранную миграцию)
* php migrate.php mark <version> --as=installed (отметить миграцию как установленную не запуская ее)
* php migrate.php mark unknown --as=new (отметить все неизвестные миграции как новые, фактически удаление их из бд)

Все команды: https://bitbucket.org/andrey_ryabin/sprint.migration/src/master/commands.txt?fileviewer=file-view-default


Пример файла миграции
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

Все примеры: https://bitbucket.org/andrey_ryabin/sprint.migration/src/master/examples/?at=master

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
  'title' => '',
  'migration_dir' => '',
  'migration_table' => '',
  'migration_extend_class' => '',
  'version_prefix' => '',
  'tracker_task_url' => '',
  'stop_on_errors' => '',
  'version_builders' => array(),
);
```

**title** - название конфига

**migration_dir** - директория для миграций (относительно DOC_ROOT), по умолчанию: **/local/php_interface/migrations** или **/bitrix/php_interface/migrations**

**migration_table** - таблица в бд с миграциями, по умолчанию sprint_migration_versions

**migration_extend_class** - класс от которого наследуются миграции, по умолчанию Version (ваш класс должен наследоваться от Version)

**tracker_task_url** - Шаблон ссылки на задачу в трекере, в описании миграции можно указать номер задачи, например #12345, а в шаблоне должна быть конструкция $1, 
например http://www.redmine.org/issues/$1/, работает только в админке

**version_prefix** - Заголовок класса миграции, по умолчанию Version (полное имя класса состоит из заголовка и даты)

**version_builders** - Конструкторы миграций

**stop_on_errors** - Останавливать выполнение миграций при ошибках, варианты значений: yes|no, по умолчанию no

Ни один из параметров не является обязательным.

При указании в конфиге несуществующей директорий для миграций (migration_dir) или таблицы в бд (migration_table) 
модуль создаст их.

Текущий конфиг помечается звездочкой * как в админке, в блоке конфигов, так и в консоли (команда config).

Переключить конфиг в админке можно нажав кнопку "переключить" напротив нужного конфига
В консоли - используя параметр --config={NAME} к любой команде

Примеры:
* php migrate.php config --config=release001 (переключить конфиг на release001 и посмотреть список конфигов)
* php migrate.php migrate --config=release001 (переключить конфиг на release001 и накатить все миграции)


Пример создания миграции с помощью конструктора
-------------------------
Создайте класс-конструктор и объявите его в конфиге
```
<?php return array (
  'version_builders' => array(
    'MyVersion' => '\MyNamespace\MyVersion',
  ),
);
```

Ваш класс должен наследоваться от Sprint\Migration\AbstractBuilder

Реализуйте алгоритм создания миграции по аналогии 

с конструктором /modules/sprint.migration/classes/Sprint/Migration/Builders/IblockExport.php

и шаблоном для него /modules/sprint.migration/templates/IblockExport.php

Пример создания миграции которая наследуется от вашего класса
-------------------------

Укажите ваш класс в конфиге
```
<?php return array (
	'migration_extend_class' => '\Acme\MyVersion',
    //'migration_extend_class' => '\Acme\MyVersion as MyVersion',
);
```

Создайте этот класс
```
<?php
namespace Acme;
use \Sprint\Migration\Version;

class MyVersion extends Version
{
    // ваш код
}
```

Создайте миграцю migrate.php create, результат:
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

При установке\откате всех миграций сразу, в случае если одну из миграций не удается выполнить, 
она пропускается и выполняются следующие миграции после нее, 
это поведение можно отрегулировать параметром в конфиге stop_on_errors = yes
При таком варианте выполнение миграций остановится на миграции с ошибкой

Код миграций должен быть независимым друг от друга, например если в одной миграции создается инфоблок, то в другой, 
перед тем как добавить какое-либо свойство, необходимо проверить существование этого инфоблока.

Такие методы есть в Sprint\Migration\Helpers\IblockHelper, например addIblockIfNotExists, addPropertyIfNotExists


Скриншоты
-------------------------
![админка](https://bitbucket.org/repo/aejkky/images/4102016731-admin-interface.png)
