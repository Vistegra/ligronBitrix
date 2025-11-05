<?php

declare(strict_types=1);

namespace OrderApi\DB\Models;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;


class OrderTable extends DataManager
{
  public const int CREATED_BY_DEALER = 1;
  public const int CREATED_BY_MANAGER = 2;

  public static function getTableName(): string
  {
    return 'vs_e_order';
  }


  /**
   * @throws SystemException
   * @throws ArgumentException
   */
  public static function getMap(): array
  {
    return [

      new Fields\IntegerField('id', [
        'primary' => true,
        'autocomplete' => true,
      ]),

      new Fields\StringField('number', [
        'unique' => true,
        'nullable' => true,
        'size' => 50,
      ]),

      new Fields\StringField('name', [
        'required' => true,
        'size' => 255,
      ]),

      // Связь со статусом
      new Fields\Relations\Reference(
        'status',
        OrderStatusTable::class,
        ['=this.status_id' => 'ref.id']
      ),
      new Fields\IntegerField('status_id', [
        'nullable' => true,
      ]),

      // Связь с родителем
      new Fields\Relations\Reference(
        'parent',
        __CLASS__,
        ['=this.parent_id' => 'ref.id']
      ),
      new Fields\IntegerField('parent_id', [
        'nullable' => true,
      ]),

      new Fields\IntegerField('created_by', [
        'nullable' => true,
        'validation' => function () {
          return [
            new \Bitrix\Main\ORM\Fields\Validators\RangeValidator(
              1, 2, 'created_by должен быть 1 (dealer) или 2 (manager)'
            )
          ];
        }
      ]),

      new Fields\IntegerField('created_by_id', [
        'required' => true,
      ]),

      // Привязки
      new Fields\StringField('dealer_prefix', [
        'size' => 10,
        'nullable' => true,
      ]),
      new Fields\IntegerField('dealer_user_id', [
        'nullable' => true,
      ]),
      new Fields\IntegerField('manager_id', [
        'nullable' => true,
      ]),


      new Fields\IntegerField('fabrication', [
        'nullable' => true,
        'comment' => 'дней на производство',
      ]),

      new Fields\DateField('ready_date', [
        'nullable' => true,
      ]),

      new Fields\TextField('comment', [
        'nullable' => true,
      ]),

      new Fields\IntegerField('children_count', [
        'default_value' => 0,
      ]),

      // История статусов (JSON)
      new Fields\TextField('status_history', [
        'nullable' => true,
        'default_value' => '[]',
        'save_data_modification' => function () {
          return [
            function ($value) {
              if (is_array($value)) {
                return json_encode($value, JSON_UNESCAPED_UNICODE);
              }
              return $value;
            }
          ];
        },
        'fetch_data_modification' => function () {
          return [
            function ($value) {
              if ($value === null || $value === '') {
                return [];
              }
              $decoded = json_decode($value, true);
              return is_array($decoded) ? $decoded : [];
            }
          ];
        }
      ]),

      // Системные
      new Fields\DatetimeField('created_at', [
        'default_value' => function () {
          return new DateTime();
        },
      ]),

      new Fields\DatetimeField('updated_at', [
        'default_value' => function () {
          return new DateTime();
        },
      ]),

    ];
  }

  //ToDo
 /* public static function onBeforeAdd(Event $event): void
  {
    $fields = $event->getParameter('fields');
    $fields['UPDATED_AT'] = new DateTime();
    $event->addResult(new EventResult(['fields' => $fields]));
  }

  public static function onBeforeUpdate(Event $event): void
  {
    $fields = $event->getParameter('fields');
    $fields['UPDATED_AT'] = new DateTime();
    $event->addResult(new EventResult(['fields' => $fields]));
  }*/

}