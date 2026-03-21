<?php

declare(strict_types=1);

namespace OrderApiV2\DB\Models;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;
use OrderApiV2\Helpers\ModelFieldHelper as F;

class DealerSalonTable extends DataManager
{
  public static function getConnectionName(): string
  {
    return 'webcalc';
  }

  public static function getTableName(): string
  {
    return 'combination_dealer_salons';
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

      new Fields\StringField('inn_dealer',[
        'required' => true,
        'size'     => 20,
        'fetch_data_modification' => F::cleanString(),
      ]),

      new Fields\StringField('salon_code',[
        'required' => true,
        'size'     => 50,
        'fetch_data_modification' => F::cleanString(),
      ]),

      // Связь с таблицей дилеров
      new Fields\Relations\Reference(
        'dealer',
        DealerTable::class,['=this.inn_dealer' => 'ref.inn_dealer']
      ),

      // Связь с таблицей салонов
      new Fields\Relations\Reference(
        'salon',
        SalonTable::class,
        ['=this.salon_code' => 'ref.salon_code']
      ),
    ];
  }
}