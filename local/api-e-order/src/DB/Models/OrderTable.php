<?php

declare(strict_types=1);

namespace OrderApi\DB\Models;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;
use OrderApi\Helpers\ModelFieldHelper as F;

class OrderTable extends DataManager
{
  // Типы создателей
  public const int CREATED_BY_DEALER = 1;
  public const int CREATED_BY_MANAGER = 2;

  // Типы заказов
  public const int ORIGIN_TYPE_APP = 0;
  public const int ORIGIN_TYPE_1C = 1;
  public const int ORIGIN_TYPE_CALC = 2;

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
        'fetch_data_modification' => F::toInt(),
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
        'fetch_data_modification' => F::toInt(),
      ]),

      // Связь с родителем
      new Fields\Relations\Reference(
        'parent',
        __CLASS__,
        ['=this.parent_id' => 'ref.id']
      ),
      new Fields\IntegerField('parent_id', [
        'nullable' => true,
        'fetch_data_modification' => F::toInt(),
      ]),

      //Роль создателя
      new Fields\IntegerField('created_by', [
        'nullable' => true,
        'fetch_data_modification' => F::toInt(),
      ]),

      //ИД пользователя
      new Fields\IntegerField('created_by_id', [
        'required' => true,
        'fetch_data_modification' => F::toInt(),
      ]),

      new Fields\StringField('dealer_prefix', [
        'size' => 10,
        'nullable' => true,
      ]),

      new Fields\IntegerField('dealer_user_id', [
        'nullable' => true,
        'fetch_data_modification' => F::toInt(),
      ]),

      new Fields\IntegerField('manager_id', [
        'nullable' => true,
        'fetch_data_modification' => F::toInt(),
      ]),

      new Fields\IntegerField('production_time', [
        'nullable' => true,
        'comment' => 'дней на производство',
        'fetch_data_modification' => F::toInt(),
      ]),
      new Fields\DateField('ready_date', [
        'nullable' => true,
        'fetch_data_modification' => F::dateToString(),
      ]),

      new Fields\TextField('comment', [
        'nullable' => true,
      ]),

      new Fields\IntegerField('children_count', [
        'default_value' => 0,
        'fetch_data_modification' => F::toInt(),
      ]),

      // История статусов (JSON)
      new Fields\TextField('status_history', [
        'nullable' => true,
        'default_value' => '[]',
        'save_data_modification' => F::toJsonEncode(),
        'fetch_data_modification' => F::toJsonDecode(),
      ]),



      new Fields\IntegerField('percent_payment', [
        'nullable' => true,
        'default_value' => 0,
        'fetch_data_modification' => F::toInt(),
      ]),

      // Тип происхождения заказа (0=APP, 1=1C, 2=CALC)
      new Fields\IntegerField('origin_type', [
        'nullable' => true,
        'default_value' => 0,
        'fetch_data_modification' => F::toInt(),
      ]),

      // Остаток оплаты
      new Fields\FloatField('due_payment', [
        'nullable' => true,
        'default_value' => null,
      ]),

      // Системные
      new Fields\DatetimeField('created_at', [
        'default_value' => F::now(),
        'fetch_data_modification' => F::toTimestamp(),
      ]),

      new Fields\DatetimeField('updated_at', [
        'default_value' => F::now(),
        'fetch_data_modification' => F::toTimestamp(),
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