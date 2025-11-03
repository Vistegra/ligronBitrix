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
      new Fields\IntegerField('ID', [
        'primary' => true,
        'autocomplete' => true,
        'unsigned' => true,
      ]),

      new Fields\StringField('cms_param', [
        'size' => 255,
        'default_value' => '{}',
        'character_set' => 'utf8',
        'save_data_modification' => function () {
          return [
            function ($value) {
              return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
            }
          ];
        },
        'fetch_data_modification' => function () {
          return [
            function ($value) {
              $decoded = json_decode($value, true);
              return is_array($decoded) ? $decoded : [];
            }
          ];
        }
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
      ]),

      new Fields\DatetimeField('register_date', [
        'default_value' => function () {
          return new DateTime();
        },
      ]),

      new Fields\DatetimeField('last_edit_date', [
        'default_value' => function () {
          return new DateTime();
        },
      ]),

      new Fields\IntegerField('activity', [
        'default_value' => 1,
        'size' => 1,
      ]),

      new Fields\TextField('settings', [
        'data_type' => 'text',
        'save_data_modification' => function () {
          return [
            function ($value) {
              if ($value === null || $value === '') {
                $value = [];
              }
              if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
              }
              return gzcompress($value, 9);
            }
          ];
        },
        'fetch_data_modification' => function () {
          return [
            function ($value) {
              if ($value === null || $value === '') {
                return [];
              }
              $uncompressed = @gzuncompress($value);
              if ($uncompressed === false) {
                return $value;
              }
              return json_decode($uncompressed);
            }
          ];
        }
      ]),

    ];
  }

}