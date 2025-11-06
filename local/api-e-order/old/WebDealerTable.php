<?php

declare(strict_types=1);

namespace OrderApi\DB\Models;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;

/**
 * Таблица dealer в SQL Server (база WebCalcNew)
 * Соединение: webcalc
 */
class WebDealerTable extends DataManager
{
  /** @return string */
  public static function getConnectionName(): string
  {
    return 'webcalc';
  }

  /** @return string */
  public static function getTableName(): string
  {
    return 'dealer';
  }

  /**
   * @return array
   * @throws SystemException
   */
  public static function getMap(): array
  {
    // Универсальная функция очистки
    $clean = function () {
      return [
        function ($value) {
          if (!is_string($value)) return $value;
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
        'values' => [0, 1],
        'default_value' => 1,
      ]),

      new Fields\StringField('inn_dealer', [
        'size'     => 20,
        'nullable' => true,
        'fetch_data_modification' => $clean
      ]),

      new Fields\StringField('name', [
        'size'     => 20,
        'nullable' => true,
        'fetch_data_modification' => $clean
      ]),

      new Fields\StringField('salon', [
        'size'     => 10,
        'nullable' => true,
        'fetch_data_modification' => $clean
      ]),
    ];
  }
}