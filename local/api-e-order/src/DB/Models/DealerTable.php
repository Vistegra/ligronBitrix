<?php

declare(strict_types=1);

namespace OrderApi\DB\Models;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;
use \Bitrix\Main\Type\DateTime;
use RuntimeException;

class DealerTable extends DataManager
{

  public static function getConnectionName(): string
  {
    return 'calc';
  }
  public static function getTableName(): string
  {
    return 'dealers';
  }

  /**
   * @throws SystemException
   */
  public static function getMap(): array
  {
    return [
      new Fields\IntegerField('ID', ['primary' => true, 'autocomplete' => true]),
      new Fields\StringField('cms_param', [
        'default_value' => '{}',
        'save_data_modification' => function () {
          return [
            function ($value) {
              return is_array($value) ? json_encode($value) : $value;
            }
          ];
        },
        'fetch_data_modification' => function () {
          return [
            function ($value) {
              return json_decode($value, true) ?: [];
            }
          ];
        }
      ]),
      new Fields\StringField('name'),
      new Fields\StringField('contacts', ['default_value' => '{}']),
      new Fields\DatetimeField('register_date', ['default_value' => new DateTime()]),
      new Fields\DatetimeField('last_edit_date', ['default_value' => new DateTime()]),
      new Fields\IntegerField('activity', ['default_value' => 1]),
      new Fields\TextField('settings'),
    ];
  }

}