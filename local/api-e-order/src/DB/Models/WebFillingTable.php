<?php

declare(strict_types=1);

namespace OrderApi\DB\Models;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;
use OrderApi\Helpers\ModelFieldHelper as F;

/**
 * Таблица [filling] в SQL Server (база WebCalcNew)
 * Период отсутствия офис-менеджера (code_user)
 * и кто его замещает (code_user_filling)
 * Соединение: webcalc
 */
class WebFillingTable extends DataManager
{
  /** @return string */
  public static function getConnectionName(): string
  {
    return 'webcalc';
  }

  /** @return string */
  public static function getTableName(): string
  {
    return 'filling'; // в квадратных скобках не нужно
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
        'fetch_data_modification' => F::toInt(),
      ]),

      // Кто уходит в отпуск / больничный
      new Fields\StringField('code_user', [
        'size'     => 10,
        'nullable' => true,
        'fetch_data_modification' => F::cleanString(),
      ]),

      // С какой даты
      new Fields\DateField('date_from', [
        'nullable' => true,
      ]),

      // По какую дату
      new Fields\DateField('date_to', [
        'nullable' => true,
      ]),

      // Кто подменяет
      new Fields\StringField('code_user_filling', [
        'size'     => 10,
        'nullable' => true,
        'fetch_data_modification' => F::cleanString(),
      ]),
    ];
  }
}