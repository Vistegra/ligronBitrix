<?php

declare(strict_types=1);

namespace OrderApi\DB\Models;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;

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
        'unsigned' => true
      ]),

      new Fields\IntegerField('PERMISSION_ID', [
        'required' => true,
        'unsigned' => true
      ]),

      new Fields\StringField('LOGIN', [
        'required' => true,
        'size' => 100,
        'character_set' => 'utf8'
      ]),

      new Fields\StringField('PASSWORD', [
        'required' => true,
        'size' => 60,
        'character_set' => 'utf8'
      ]),

      new Fields\StringField('NAME', [
        'nullable' => true,
        'size' => 255,
        'character_set' => 'utf8'
      ]),

      new Fields\StringField('CONTACTS', [
        'nullable' => true,
        'size' => 255,
        'character_set' => 'utf8'
      ]),

      new Fields\DatetimeField('REGISTER_DATE', [
        'default_value' => function() {
          return new \Bitrix\Main\Type\DateTime();
        }
      ]),

      new Fields\IntegerField('ACTIVITY', [
        'default_value' => 1,
        'size' => 1
      ]),

      new Fields\StringField('CUSTOMIZATION', [
        'nullable' => true,
        'size' => 1000,
        'default_value' => '{}',
        'character_set' => 'utf8'
      ]),

      new Fields\StringField('HASH', [
        'nullable' => true,
        'size' => 60,
        'character_set' => 'utf8'
      ]),
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
      var_dump('$fullClassName');
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

    // Инициализируем сущность
    //  $fullClassName::getEntity();

    return $fullClassName;
  }

}