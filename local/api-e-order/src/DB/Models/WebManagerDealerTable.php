<?php

declare(strict_types=1);

namespace OrderApi\DB\Models;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;

/**
 * Таблица combination_manager_dealer в SQL Server (база WebCalcNew)
 * Связывает менеджера (code_user_manager) с дилером (inn_dealer)
 * Соединение: webcalc
 */
class WebManagerDealerTable extends DataManager
{
  /** @return string */
  public static function getConnectionName(): string
  {
    return 'webcalc';
  }

  /** @return string */
  public static function getTableName(): string
  {
    return 'combination_manager_dealer';
  }

  /**
   * @return array
   * @throws SystemException
   */
  public static function getMap(): array
  {
    // Очистка строк от мусора SQL Server
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

      // Код подчинённого пользователя (из user.code_user)
      new Fields\StringField('code_user', [
        'size'     => 10,
        'nullable' => true,
        'fetch_data_modification' => $clean,
      ]),

      // Код менеджера (из user.code_user)
      new Fields\StringField('code_user_manager', [
        'size'     => 10,
        'nullable' => true,
        'fetch_data_modification' => $clean,
      ]),

      // ИНН дилера (из dealer.inn_dealer)
      new Fields\StringField('inn_dealer', [
        'size'     => 20,
        'nullable' => true,
        'fetch_data_modification' => $clean,
      ]),
    ];
  }
}