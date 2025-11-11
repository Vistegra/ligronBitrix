<?php

declare(strict_types=1);

namespace OrderApi\DB\Models;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;
use OrderApi\DB\Helpers\ModelFieldHelper as F;

abstract class DealerUserTable extends DataManager
{
  public static function getConnectionName(): string
  {
    return 'calc';
  }

  /**
   * @throws SystemException
   */
  public static function getTableName(): string
  {
    throw new SystemException('Нельзя использовать класс DealerUserTable напрямую. Используйте метод для генерации класса сущности getEntityClassByPrefix');
  }

  /**
   * @throws SystemException
   */
  public static function getMap(): array
  {
    return [
      new Fields\IntegerField('ID', [
        'primary' => true,
        'autocomplete' => true,
        'unsigned' => true,
        'fetch_data_modification' => F::toInt(),
      ]),

     /* new Fields\IntegerField('permission_id', [
        'required' => true,
        'unsigned' => true
      ]),*/

      new Fields\StringField('login', [
        'required' => true,
        'size' => 100,
        'character_set' => 'utf8'
      ]),

      // Пароль (хешированный)
      new Fields\StringField('password', [
        'required' => true,
        'size' => 60,
        'character_set' => 'utf8'
      ]),

      new Fields\StringField('name', [
        'nullable' => true,
        'size' => 255,
        'character_set' => 'utf8'
      ]),

      new Fields\StringField('contacts', [
        'nullable' => true,
        'size' => 255,
        'character_set' => 'utf8'
      ]),

      new Fields\DatetimeField('register_date', [
        'default_value' => F::now(),
      ]),

      new Fields\IntegerField('activity', [
        'default_value' => 1,
        'size' => 1
      ]),

      /*new Fields\StringField('customization', [
        'nullable' => true,
        'size' => 1000,
        'default_value' => '{}',
        'character_set' => 'utf8'
      ]),*/

      /*new Fields\StringField('hash', [
        'nullable' => true,
        'size' => 60,
        'character_set' => 'utf8'
      ]),*/
    ];
  }

  /**
   * Возвращает класс ORM для таблицы с нужным префиксом из соединения calc.
   *
   * @param string $prefix Префикс таблицы (например, 'dea_', 'pro_')
   * @return string Имя ORM-класса
   * @throws \Bitrix\Main\ArgumentException
   * @throws \Bitrix\Main\SystemException
   */
  public static function getEntityClassByPrefix(string $prefix): string
  {
    $className = "DealerUser_{$prefix}Table";
    $baseClassName = "DealerUserTable";

    $fullClassName = '\\' . __NAMESPACE__ . '\\' . $className;

    // Если класс уже создан — возвращаем
    if (class_exists($fullClassName)) {
      return $fullClassName;
    }

    // Создаем динамический класс - используем полное имя класса
    $classCode = "namespace " . __NAMESPACE__ . " {
        class {$className} extends {$baseClassName} 
        {
            public static function getTableName(): string
            {
                return '{$prefix}users';
            }
        }
    }";

    eval($classCode);

    return $fullClassName;
  }

}