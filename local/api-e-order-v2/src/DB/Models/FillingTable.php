<?php

declare(strict_types=1);

namespace OrderApiV2\DB\Models;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;
use OrderApiV2\Helpers\ModelFieldHelper as F;

class FillingTable extends DataManager
{
  public static function getConnectionName(): string
  {
    return 'webcalc';
  }

  public static function getTableName(): string
  {
    return 'filling';
  }

  /**
   * @throws SystemException
   */
  public static function getMap(): array
  {
    return [
      new Fields\IntegerField('id',[
        'primary'      => true,
        'autocomplete' => true,
        'fetch_data_modification' => F::toInt(),
      ]),

      new Fields\StringField('code_user',[
        'required' => true,
        'size'     => 10,
        'fetch_data_modification' => F::cleanString(),
      ]),

      new Fields\DateField('date_from',[
        'required' => true,
      ]),

      new Fields\DateField('date_to',[
        'required' => true,
      ]),

      new Fields\StringField('code_user_filling',[
        'required' => true,
        'size'     => 10,
        'fetch_data_modification' => F::cleanString(),
      ]),

    ];
  }
}