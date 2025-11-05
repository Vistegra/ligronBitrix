<?php

declare(strict_types=1);

namespace OrderApi\DB\Models;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;

/**
 * Таблица user в SQL Server (база WebCalcNew)
 * Соединение: webcalc
 */
class WebUserTable extends DataManager
{

  public static function getConnectionName(): string
  {
    return 'webcalc';
  }

  public static function getTableName(): string
  {
    return 'user';
  }

  /**
   * @return array
   * @throws SystemException
   */
  public static function getMap(): array
  {
    // Универсальная очистка строк (убирает \r\n\t\0)
    $clean = function () {
      return [
        function ($value) {
          if (!is_string($value)) {

            return $value;
          }
          return trim(preg_replace('/[\r\n\t\x0B\0]+/', '', $value));
        }
      ];
    };

    return [
      new Fields\IntegerField('id', [
        'primary'      => true,
        'autocomplete' => true,
        'unsigned'     => true,
      ]),

      new Fields\BooleanField('active', [
        'values'        => [0, 1],
        'default_value' => 1,
      ]),

      new Fields\StringField('code_user', [
        'size'     => 10,
        'nullable' => true,
        'fetch_data_modification' => $clean,
      ]),

      // ФИО
      new Fields\StringField('name', [
        'size'     => 50,
        'nullable' => true,
        'fetch_data_modification' => $clean,
      ]),

      // manager: 1 = менеджер, 0 = менеджер
      new Fields\BooleanField('manager', [
        'values'        => [0, 1],
        'default_value' => 0,
      ]),

      // Логин
      new Fields\StringField('username', [
        'size'     => 50,
        'nullable' => true,
        'fetch_data_modification' => $clean,
      ]),

      // Пароль (хранится в открытом виде)
      new Fields\StringField('password', [
        'size'     => 50,
        'nullable' => true,
        'fetch_data_modification' => $clean,
      ]),

      new Fields\StringField('email', [
        'size'     => 50,
        'nullable' => true,
        'fetch_data_modification' => $clean,
      ]),

      new Fields\StringField('phone', [
        'size'     => 15,
        'nullable' => true,
        'fetch_data_modification' => $clean,
      ]),
    ];
  }
}