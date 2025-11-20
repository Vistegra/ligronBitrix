<?php

declare(strict_types=1);

namespace OrderApi\DB\Models;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;
use OrderApi\DB\Helpers\ModelFieldHelper as F;

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

      // Код подчинённого пользователя (из user.code_user)
      new Fields\StringField('code_user', [
        'size'     => 10,
        'nullable' => true,
        'fetch_data_modification' => F::cleanString(),
      ]),

      // Код менеджера (из user.code_user)
      new Fields\StringField('code_user_manager', [
        'size'     => 10,
        'nullable' => true,
        'fetch_data_modification' => F::cleanString(),
      ]),

      // ИНН дилера
      new Fields\StringField('inn_dealer', [
        'size'     => 20,
        'nullable' => true,
        'fetch_data_modification' => F::cleanString(),
      ]),
    ];
  }
}