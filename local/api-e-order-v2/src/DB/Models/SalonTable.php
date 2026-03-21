<?php

declare(strict_types=1);

namespace OrderApiV2\DB\Models;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;
use OrderApiV2\Helpers\ModelFieldHelper as F;

class SalonTable extends DataManager
{
  public static function getConnectionName(): string
  {
    return 'webcalc';
  }

  public static function getTableName(): string
  {
    return 'salons';
  }

  /**
   * @throws SystemException
   */
  public static function getMap(): array
  {
    return[
      new Fields\IntegerField('id',[
        'primary'      => true,
        'autocomplete' => true,
        'fetch_data_modification' => F::toInt(),
      ]),

      new Fields\StringField('salon_code',[
        'required' => true,
        'size'     => 50,
        'fetch_data_modification' => F::cleanString(),
      ]),

      new Fields\StringField('name',[
        'required' => true,
        'size'     => 255,
        'fetch_data_modification' => F::cleanString(),
      ]),

      new Fields\BooleanField('active', [
        'values'        => [0, 1],
        'default_value' => 1,
      ]),

    ];
  }
}