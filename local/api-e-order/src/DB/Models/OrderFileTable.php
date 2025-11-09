<?php

declare(strict_types=1);

namespace OrderApi\DB\Models;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;

class OrderFileTable extends DataManager
{

  public static function getTableName(): string
  {
    return 'vs_e_order_file';
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

      // Связь с заказом
      new Fields\Relations\Reference(
        'order',
        OrderTable::class,
        ['=this.order_id' => 'ref.id']
      ),
      new Fields\IntegerField('order_id', [
        'required' => true,
      ]),


      new Fields\StringField('name', [
        'required' => true,
        'size' => 255,
      ]),

      new Fields\StringField('path', [
        'required' => true,
        'size' => 512,
      ]),

      new Fields\IntegerField('size', [
        'nullable' => true,
      ]),

      new Fields\StringField('mime', [
        'nullable' => true,
        'size' => 100,
      ]),

      // Кто загрузил
      new Fields\StringField('uploaded_by', [
        'required' => true,
        'size' => 10,
      ]),

      new Fields\IntegerField('uploaded_by_id', [
        'required' => true,
      ]),

      //
      new Fields\DatetimeField('created_at', [
        'default_value' => function () {
          return new \Bitrix\Main\Type\DateTime();
        },
      ]),

    ];
  }
}