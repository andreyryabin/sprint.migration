# Миграции для разработчиков (1С-Битрикс) #
[![Latest Stable Version](https://poser.pugx.org/andreyryabin/sprint.migration/v/stable.svg)](https://packagist.org/packages/andreyryabin/sprint.migration/)
[![Total Downloads](https://img.shields.io/packagist/dt/andreyryabin/sprint.migration.svg?style=flat)](https://packagist.org/packages/andreyryabin/sprint.migration)

Помогает переносить изменения между нескольким копиями проекта.

Все изменения для базы данных пишутся в файлы миграций, эти файлы, как и весь код проекта, хранятся в системе контроля версий (например git) и попадают в копии разработчиков, после чего им необходимо выполнить установку новых миграций, чтобы обновить бд.

Работать можно как через консоль, так и через админку.

* Маркетплейс: [http://marketplace.1c-bitrix.ru/solutions/sprint.migration/](http://marketplace.1c-bitrix.ru/solutions/sprint.migration/)
* Composer: [https://packagist.org/packages/andreyryabin/sprint.migration](https://packagist.org/packages/andreyryabin/sprint.migration)
* Документация: [https://github.com/andreyryabin/sprint.migration/wiki](https://github.com/andreyryabin/sprint.migration/wiki)
* Материалы: [https://dev.1c-bitrix.ru/community/webdev/user/39653/blog/](https://dev.1c-bitrix.ru/community/webdev/user/39653/blog/)
* Группа в телеграм: [https://t.me/sprint_migration_bitrix](https://t.me/sprint_migration_bitrix)

Особая благодарность
-------------------------
Самой лучшей IDE на свете!\
[![Phpstorm](https://raw.githubusercontent.com/wiki/andreyryabin/sprint.migration/assets/phpstorm.png)](https://www.jetbrains.com/?from=sprint.migration)

А также всем помощникам!\
[https://github.com/andreyryabin/sprint.migration/blob/master/contributors.txt](https://github.com/andreyryabin/sprint.migration/blob/master/contributors.txt)


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
Работа в консоли
-------------------------
[https://github.com/andreyryabin/sprint.migration/wiki/Работа-в-консоли](https://github.com/andreyryabin/sprint.migration/wiki/Работа-в-консоли)


Скриншоты
-------------------------
Админка миграций
![bitrix-sprint-migration-1.png](https://raw.githubusercontent.com/wiki/andreyryabin/sprint.migration/assets/bitrix-sprint-migration-1.png)

Формы создания миграций
![bitrix-sprint-migration-2.png](https://raw.githubusercontent.com/wiki/andreyryabin/sprint.migration/assets/bitrix-sprint-migration-2.png)


Полезные ссылки
-------------------------
[Миграции шаблонов бизнес-процессов для Битрикс24. Вот что для этого нужно](https://habr.com/ru/companies/ibs/articles/788566/)
