<?php

declare(strict_types=1);

namespace OrderApi\DB\Models;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;
use OrderApi\DB\Helpers\ModelFieldHelper as F;

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
      new Fields\IntegerField('ID', [
        'primary' => true,
        'autocomplete' => true,
        'unsigned' => true,
        'fetch_data_modification' => F::toInt(),
      ]),

      new Fields\StringField('cms_param', [
        'size' => 255,
        'default_value' => '{}',
        'character_set' => 'utf8',
        'save_data_modification' => F::toJsonEncode(),
        'fetch_data_modification' => F::toJsonDecode(),
      ]),

      new Fields\StringField('name', [
        'nullable' => true,
        'size' => 255,
        'character_set' => 'utf8',
      ]),

      new Fields\StringField('contacts', [
        'default_value' => '{}',
        'size' => 255,
        'character_set' => 'utf8',
        'save_data_modification' => F::toJsonEncode(),
        'fetch_data_modification' => F::toJsonDecode(),
      ]),

      new Fields\DatetimeField('register_date', [
        'default_value' => F::now(),
      ]),

      new Fields\DatetimeField('last_edit_date', [
        'default_value' => F::now(),
      ]),

      new Fields\IntegerField('activity', [
        'default_value' => 1,
        'size' => 1,
      ]),

      new Fields\TextField('settings', [
        'data_type' => 'text',
        'save_data_modification' => F::compressJson(),
        'fetch_data_modification' => F::decompressJson(),
      ]),

    ];
  }

}