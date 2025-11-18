<?php

declare(strict_types=1);

namespace OrderApi\DB\Repositories;

use OrderApi\DB\Models\DealerTable;

final class DealerRepository //ToDo перенести в DealerUserRepo
{
  /**
   * Возвращает готовую карту: [INN => PREFIX]
   * Только активные дилеры с заполненным INN и prefix
   *
   * @return array<string, string>  ['7701234567' => 'dea_', ...]
   */
  public static function getInnToPrefixMap(): array
  {
    $result = DealerTable::getList([
      'select' => [
        'cms_param',
        'settings',
      ],
      'filter' => ['=activity' => 1],
      'cache'  => ['ttl' => 60], // кэшируем сам запрос на 1 минуту
    ]);

    $map = [];

    while ($dealer = $result->fetch()) {
      $prefix = $dealer['cms_param']['prefix'] ?? null;
      $inn    = $dealer['settings']['prop_tin'] ?? null;

      if (
        is_string($prefix) && $prefix !== '' &&
        is_string($inn) && $inn !== ''
      ) {
        $map[trim($inn)] = trim($prefix);
      }
    }

    return $map;
  }

}