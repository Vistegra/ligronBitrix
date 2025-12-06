<?php

declare(strict_types=1);

namespace OrderApi\DB\Models;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use OrderApi\Helpers\ModelFieldHelper as F;

class OrderStatusTable extends DataManager
{
  public static function getTableName(): string
  {
    return 'vs_e_order_status';
  }

  /**
   * @throws \Bitrix\Main\SystemException
   */
  public static function getMap(): array
  {
    return [
      new Fields\IntegerField('id', [
        'primary' => true,
        'autocomplete' => true,
        'fetch_data_modification' => F::toInt(),
      ]),

      new Fields\IntegerField('sort', [
        'default_value' => 500,
        'fetch_data_modification' => F::toInt(),
      ]),

      new Fields\StringField('code', [
        'required' => true,
        'size' => 10,
      ]),

      new Fields\StringField('name', [
        'required' => true,
        'size' => 20,
      ]),

      //Hex
      new Fields\StringField('color', [
        'size' => 7,
        'nullable' => true,
      ]),

    ];
  }

}