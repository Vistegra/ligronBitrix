<?php

declare(strict_types=1);

namespace OrderApiV2\DB\Models;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;
// ИСПРАВЛЕНО: используем хелпер из V2
use OrderApiV2\Helpers\ModelFieldHelper as F;

abstract class OldDealerUserTable extends DataManager
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
    throw new SystemException('Нельзя использовать класс напрямую.');
  }

  /**
   * @throws SystemException
   */
  public static function getMap(): array
  {
    return [
      new Fields\IntegerField('id', [
        'primary' => true,
        'autocomplete' => true,
        'unsigned' => true,
        'fetch_data_modification' => F::toInt(),
      ]),
      new Fields\StringField('login', [
        'required' => true,
        'size' => 100,
        'character_set' => 'utf8'
      ]),
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
        'character_set' => 'utf8',
        'save_data_modification' => F::toJsonEncode(),
        'fetch_data_modification' => F::toJsonDecode(),
      ]),
      new Fields\DatetimeField('register_date', [
        'default_value' => F::now(),
      ]),
      new Fields\IntegerField('activity', [
        'default_value' => 1,
        'size' => 1
      ]),
    ];
  }

  public static function getEntityClassByPrefix(string $prefix): string
  {

    $className = "OldDealerUser_{$prefix}Table";
    $baseClassName = "OldDealerUserTable";

    $fullClassName = '\\' . __NAMESPACE__ . '\\' . $className;

    if (class_exists($fullClassName)) {
      return $fullClassName;
    }

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