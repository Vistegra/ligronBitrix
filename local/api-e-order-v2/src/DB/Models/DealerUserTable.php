<?php

declare(strict_types=1);

namespace OrderApiV2\DB\Models;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;
use OrderApiV2\Helpers\ModelFieldHelper as F;

class DealerUserTable extends DataManager
{
  public static function getConnectionName(): string
  {
    return 'webcalc';
  }

  public static function getTableName(): string
  {
    return 'dealer_users';
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

      new Fields\StringField('username',[
        'required' => true,
        'size'     => 100,
        'fetch_data_modification' => F::cleanString(),
      ]),

      new Fields\StringField('password',[
        'required' => true,
        'size'     => 255,
        'fetch_data_modification' => F::cleanString(),
      ]),

      new Fields\StringField('salon_code',[
        'required' => true,
        'size'     => 20,
        'fetch_data_modification' => F::cleanString(),
      ]),

      new Fields\StringField('role_code',[
        'required' => true,
        'size'     => 20,
        'fetch_data_modification' => F::cleanString(),
      ]),

      new Fields\StringField('name',[
        'nullable' => true,
        'size'     => 255,
        'fetch_data_modification' => F::cleanString(),
      ]),

      new Fields\StringField('phone',[
        'nullable' => true,
        'size'     => 50,
        'fetch_data_modification' => F::cleanString(),
      ]),

      new Fields\StringField('email',[
        'nullable' => true,
        'size'     => 100,
        'fetch_data_modification' => F::cleanString(),
      ]),

      new Fields\BooleanField('active', [
        'values'        => [0, 1],
        'default_value' => 1,
      ]),

      // Связь с салоном
      new Fields\Relations\Reference(
        'salon',
        SalonTable::class,['=this.salon_code' => 'ref.salon_code']
      ),

      // Связь с ролью
      new Fields\Relations\Reference(
        'role',
        DealerRoleTable::class,['=this.role_code' => 'ref.role_code']
      ),

    ];
  }
}