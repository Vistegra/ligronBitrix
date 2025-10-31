<?php

declare(strict_types=1);

namespace App\Api\Orm;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SystemException;
use RuntimeException;

class DealerUserTable extends DataManager
{
  private static string $prefix = '';

  public static function setPrefix(string $prefix): void
  {
    if ($prefix === '') {
      throw new \InvalidArgumentException('Префикс не может быть пустым');
    }
    self::$prefix = $prefix;
  }

  public static function getTableName(): string
  {
    if (self::$prefix === '') {
      throw new RuntimeException('Префикс не установлен перед использованием.');
    }

    return self::$prefix . '_users';
  }

  /**
   * @throws SystemException
   */
  public static function getMap(): array
  {
    return [
      new Fields\IntegerField('ID', ['primary' => true]),
      new Fields\StringField('login', ['required' => true]),
      new Fields\StringField('password', ['required' => true]),
      new Fields\StringField('name'),
      new Fields\IntegerField('activity', ['default_value' => 1]),
    ];
  }


  public static function resetPrefix(): void
  {
    self::$prefix = '';
  }
}