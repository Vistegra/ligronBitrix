<?php

declare(strict_types=1);

namespace OrderApi\DB\Models;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;
use OrderApi\Helpers\ModelFieldHelper as F;

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

    return [
      new Fields\IntegerField('id', [
        'primary'      => true,
        'autocomplete' => true,
        'unsigned'     => true,
        'fetch_data_modification' => F::toInt(),
      ]),

      new Fields\BooleanField('active', [
        'values' => [0, 1],
        'default_value' => 1,
      ]),

      new Fields\StringField('inn_dealer', [
        'size'     => 20,
        'nullable' => true,
        'fetch_data_modification' => F::cleanString(),
      ]),

      new Fields\StringField('name', [
        'size'     => 20,
        'nullable' => true,
        'fetch_data_modification' => F::cleanString(),
      ]),

      new Fields\StringField('salon', [
        'size'     => 10,
        'nullable' => true,
        'fetch_data_modification' => F::cleanString(),
      ]),
    ];
  }
}