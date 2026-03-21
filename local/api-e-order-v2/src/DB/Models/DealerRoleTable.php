<?php
declare(strict_types=1);

namespace OrderApiV2\DB\Models;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;
use OrderApiV2\Helpers\ModelFieldHelper as F;

class DealerRoleTable extends DataManager
{
  public static function getConnectionName(): string
  {
    return 'webcalc';
  }

  public static function getTableName(): string
  {
    return 'dealer_roles';
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

      new Fields\StringField('role_code',[
        'required' => true,
        'size'     => 20,
        'fetch_data_modification' => F::cleanString(),
      ]),

      new Fields\StringField('name',[
        'required' => true,
        'size'     => 100,
        'fetch_data_modification' => F::cleanString(),
      ]),
    ];

  }
}