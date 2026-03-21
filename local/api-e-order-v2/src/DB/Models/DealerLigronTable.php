<?php

declare(strict_types=1);

namespace OrderApiV2\DB\Models;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;
use OrderApiV2\Helpers\ModelFieldHelper as F;

class DealerLigronTable extends DataManager
{
  public static function getConnectionName(): string
  {
    return 'webcalc';
  }

  public static function getTableName(): string
  {
    return 'combination_dealer_ligron';
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

      new Fields\StringField('inn_dealer',[
        'required' => true,
        'size'     => 20,
        'fetch_data_modification' => F::cleanString(),
      ]),

      new Fields\StringField('user_code',[
        'required' => true,
        'size'     => 10,
        'fetch_data_modification' => F::cleanString(),
      ]),

      new Fields\BooleanField('active', [
        'values'        => [0, 1],
        'default_value' => 1,
      ]),

      // Сразу получить данные дилера по связи
      new Fields\Relations\Reference(
        'dealer',
        DealerTable::class,['=this.inn_dealer' => 'ref.inn_dealer']
      ),

      // Сразу получить данные менеджера по связи
      new Fields\Relations\Reference(
        'manager',
        LigronUserTable::class,
        ['=this.user_code' => 'ref.user_code']
      ),
    ];

  }
}