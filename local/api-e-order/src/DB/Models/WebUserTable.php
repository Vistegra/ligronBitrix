<?php

declare(strict_types=1);

namespace OrderApi\DB\Models;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;
use OrderApi\DB\Helpers\ModelFieldHelper as F;

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

    return [
      new Fields\IntegerField('id', [
        'primary'      => true,
        'autocomplete' => true,
        'unsigned'     => true,
        'fetch_data_modification' => F::toInt(),
      ]),

      new Fields\BooleanField('active', [
        'values'        => [0, 1],
        'default_value' => 1,
      ]),

      new Fields\StringField('code_user', [
        'size'     => 10,
        'nullable' => true,
        'fetch_data_modification' => F::cleanString(),
      ]),

      // ФИО
      new Fields\StringField('name', [
        'size'     => 50,
        'nullable' => true,
        'fetch_data_modification' => F::cleanString(),
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
        'fetch_data_modification' => F::cleanString(),
      ]),

      // Пароль (хранится в открытом виде)
      new Fields\StringField('password', [
        'size'     => 50,
        'nullable' => true,
        'fetch_data_modification' => F::cleanString(),
      ]),

      new Fields\StringField('email', [
        'size'     => 50,
        'nullable' => true,
        'fetch_data_modification' => F::cleanString(),
      ]),

      new Fields\StringField('phone', [
        'size'     => 15,
        'nullable' => true,
        'fetch_data_modification' => F::cleanString(),
      ]),

      new Fields\StringField('token', [
        'size'     => 30,
        'nullable' => true,
        'fetch_data_modification' => F::cleanString(),
      ]),
    ];
  }
}