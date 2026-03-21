<?php

declare(strict_types=1);

namespace OrderApiV2\Services\Migration;

use OrderApiV2\DB\Models\OrderTable;
use OrderApiV2\DB\Models\OldDealerTable;
use OrderApiV2\DB\Models\OldDealerUserTable;
use OrderApiV2\DB\Models\DealerTable as NewDealerTable;
use OrderApiV2\DB\Models\SalonTable as NewSalonTable;
use OrderApiV2\DB\Models\DealerUserTable as NewDealerUserTable;
use OrderApiV2\DB\Models\DealerSalonTable as NewDealerSalonTable;

class OrderMigrationAnalyzerService
{
  public function analyze(): array
  {
    $results = [];

    // 1. ЗАГРУЖАЕМ НОВЫЕ ДАННЫЕ (V2 - MS SQL) для проверки существования
    $newDealers = array_column(NewDealerTable::getList(['select' => ['inn_dealer']])->fetchAll(), 'inn_dealer', 'inn_dealer');
    $newSalons = array_column(NewSalonTable::getList(['select' => ['salon_code']])->fetchAll(), 'salon_code', 'salon_code');
    $newUsers = array_column(NewDealerUserTable::getList(['select' => ['username', 'id']])->fetchAll(), 'id', 'username');

    // Кэшируем связи ИНН-Салон в V2
    $newLinksRaw = NewDealerSalonTable::getList(['select' => ['inn_dealer', 'salon_code']])->fetchAll();
    $newLinks = [];
    foreach ($newLinksRaw as $link) {
      $newLinks[$link['inn_dealer'] . '_' . $link['salon_code']] = true;
    }

    // 2. КЭШИРУЕМ СТАРЫХ ДИЛЕРОВ (V1 - MySQL)
    $oldDealersCache = [];
    $oldDealersQuery = OldDealerTable::getList(['select' => ['id', 'name', 'cms_param', 'settings']])->fetchAll();
    foreach ($oldDealersQuery as $od) {
      $prefix = $od['cms_param']['prefix'] ?? null;
      if (!$prefix) continue;

      $inn = trim((string)($od['settings']['prop_tin'] ?? ''));

      // Маппинг имен салонов в коды из настроек V1
      $salonsMap = [];
      $rawSalons = $od['settings']['prop_dealercode'] ?? [];
      if (is_array($rawSalons)) {
        foreach ($rawSalons as $item) {
          $sData = $this->extractSalonData($item);
          if ($sData) {
            $salonsMap[mb_strtolower($sData['name'])] = $sData['code'];
          }
        }
      }

      $oldDealersCache[$prefix] = [
        'inn' => $inn,
        'name' => $od['name'],
        'salons' => $salonsMap
      ];
    }

    // 3. ПОЛУЧАЕМ ЗАКАЗЫ ДЛЯ АНАЛИЗА
    $orders = OrderTable::getList([
      'filter' => ['!=dealer_prefix' => null],
      'select' => ['id', 'number', 'name', 'dealer_prefix', 'dealer_user_id', 'inn_dealer', 'salon_code']
    ])->fetchAll();

    $oldUsersCache = [];

    foreach ($orders as $order) {
      $orderId = (int)$order['id'];
      $prefix = trim((string)$order['dealer_prefix']);
      $oldUserId = (int)$order['dealer_user_id'];

      $analysis = [
        'order_id' => $orderId,
        'order_number' => $order['number'] ?: 'Draft',
        'v1_prefix' => $prefix,
        'v1_user_id' => $oldUserId,

        // Результаты поиска в V1
        'old_inn' => null,
        'old_salon_code' => null,
        'old_user_login' => null,

        // Флаги статуса
        'found_in_v1' => false,
        'exists_in_v2' => false,
        'link_valid_v2' => false,
        'error' => null
      ];

      try {
        // А) Ищем дилера и ИНН
        if (!isset($oldDealersCache[$prefix])) {
          throw new \Exception("Дилер V1 не найден по префиксу");
        }
        $dealerData = $oldDealersCache[$prefix];
        $analysis['old_inn'] = $dealerData['inn'];

        // Б) Ищем пользователя и его салон в V1
        $uCacheKey = $prefix . '_' . $oldUserId;
        if (!isset($oldUsersCache[$uCacheKey])) {
          $userClass = OldDealerUserTable::getEntityClassByPrefix($prefix);
          $oldUser = $userClass::getByPrimary($oldUserId, ['select' => ['login', 'contacts']])->fetch();
          $oldUsersCache[$uCacheKey] = $oldUser ?: false;
        }

        $userData = $oldUsersCache[$uCacheKey];
        if (!$userData) {
          throw new \Exception("Пользователь V1 не найден в таблице {$prefix}users");
        }

        $analysis['old_user_login'] = $userData['login'];
        $salonNameV1 = trim((string)($userData['contacts']['code'] ?? ''));

        if (!$salonNameV1) {
          throw new \Exception("У пользователя V1 не указан салон в профиле");
        }

        $resolvedSalonCode = $dealerData['salons'][mb_strtolower($salonNameV1)] ?? null;
        if (!$resolvedSalonCode) {
          throw new \Exception("Имя салона '{$salonNameV1}' не найдено в справочнике дилера");
        }
        $analysis['old_salon_code'] = $resolvedSalonCode;
        $analysis['found_in_v1'] = true;

        // В) ПРОВЕРКА В V2 (MS SQL)
        $innExists = isset($newDealers[$analysis['old_inn']]);
        $salonExists = isset($newSalons[$resolvedSalonCode]);

        if ($innExists && $salonExists) {
          $analysis['exists_in_v2'] = true;

          // Проверяем наличие связи в combination_dealer_salons
          $linkKey = $analysis['old_inn'] . '_' . $resolvedSalonCode;
          if (isset($newLinks[$linkKey])) {
            $analysis['link_valid_v2'] = true;
          } else {
            $analysis['error'] = "В V2 нет связи между ИНН и Салоном";
          }
        } else {
          $analysis['error'] = (!$innExists ? "ИНН отсутствует в V2. " : "") . (!$salonExists ? "Салон отсутствует в V2." : "");
        }

      } catch (\Throwable $e) {
        $analysis['error'] = $e->getMessage();
      }

      $results[] = $analysis;
    }

    return $results;
  }

  private function extractSalonData(mixed $item): ?array
  {
    if (!is_array($item)) return null;
    $name = ''; $code = '';
    if (isset($item['name'], $item['code'])) {
      $name = trim((string)$item['name']);
      $code = trim((string)$item['code']);
    } elseif (count($item) >= 2) {
      $name = trim((string)($item[0] ?? ''));
      $code = trim((string)($item[1] ?? ''));
    }
    return ($name === '' || $code === '') ? null : ['name' => $name, 'code' => $code];
  }

  public function migrate(): array
  {
    $report = ['total' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

    // Получаем результаты анализа
    $analysisResults = $this->analyze();

    foreach ($analysisResults as $res) {
      $report['total']++;

      // Условия для миграции: данные в V1 найдены и объекты в V2 созданы
      // Мы не требуем link_valid_v2 (наличие связи в combination),
      // так как данные в заказ можно прописать заранее.
      if ($res['found_in_v1'] && $res['old_inn'] && $res['old_salon_code']) {
        try {
          OrderTable::update($res['order_id'], [
            'INN_DEALER' => $res['old_inn'],
            'SALON_CODE' => $res['old_salon_code'],
            // Логин тоже полезно прописать для статистики и истории
           // 'DEALER_USERNAME' => $res['old_user_login']
          ]);
          $report['updated']++;
        } catch (\Throwable $e) {
          $report['errors'][] = "Order #{$res['order_id']}: " . $e->getMessage();
        }
      } else {
        $report['skipped']++;
      }
    }

    return $report;
  }
}