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

    // 1. ЗАГРУЖАЕМ НОВЫЕ ДАННЫЕ (V2 - MS SQL)
    $newDealers = array_column(NewDealerTable::getList(['select' => ['inn_dealer']])->fetchAll(), 'inn_dealer', 'inn_dealer');
    $newSalons = array_column(NewSalonTable::getList(['select' => ['salon_code']])->fetchAll(), 'salon_code', 'salon_code');

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

      $salonsMap = [];
      $rawSalons = $od['settings']['prop_dealercode'] ?? [];
      if (is_array($rawSalons)) {
        foreach ($rawSalons as $item) {
          $sData = $this->extractSalonData($item);
          if ($sData) $salonsMap[mb_strtolower($sData['name'])] = $sData['code'];
        }
      }
      $oldDealersCache[$prefix] = [
        'inn' => trim((string)($od['settings']['prop_tin'] ?? '')),
        'salons' => $salonsMap
      ];
    }

    // 3. ПОЛУЧАЕМ ЗАКАЗЫ
    $orders = OrderTable::getList([
      'filter' => ['!=dealer_prefix' => null],
      'select' => ['id', 'number', 'name', 'dealer_prefix', 'dealer_user_id', 'inn_dealer', 'salon_code']
    ])->fetchAll();

    $oldUsersCache = [];

    foreach ($orders as $order) {
      $prefix = trim((string)$order['dealer_prefix']);
      $oldUserId = (int)$order['dealer_user_id'];

      $analysis = [
        'order_id' => (int)$order['id'],
        'order_number' => $order['number'] ?: 'Draft',
        'v1_prefix' => $prefix,

        // ТЕКУЩЕЕ СОСТОЯНИЕ (V2)
        'current_inn' => $order['inn_dealer'],
        'current_salon' => $order['salon_code'],
        'is_already_filled' => !empty($order['inn_dealer']) && !empty($order['salon_code']),

        // ЦЕЛЕВЫЕ ДАННЫЕ (из V1)
        'old_inn' => null,
        'old_salon_code' => null,
        'old_user_login' => null,

        'found_in_v1' => false,
        'exists_in_v2' => false,
        'link_valid_v2' => false,
        'error' => null
      ];

      try {
        if (!isset($oldDealersCache[$prefix])) throw new \Exception("Дилер V1 не найден");
        $dealerV1 = $oldDealersCache[$prefix];
        $analysis['old_inn'] = $dealerV1['inn'];

        $uKey = $prefix . '_' . $oldUserId;
        if (!isset($oldUsersCache[$uKey])) {
          $userClass = OldDealerUserTable::getEntityClassByPrefix($prefix);
          $oldUsersCache[$uKey] = $userClass::getByPrimary($oldUserId, ['select' => ['login', 'contacts']])->fetch();
        }
        $userV1 = $oldUsersCache[$uKey];
        if (!$userV1) throw new \Exception("Юзер V1 не найден");

        $analysis['old_user_login'] = $userV1['login'];
        $salonNameV1 = trim((string)($userV1['contacts']['code'] ?? ''));
        $resolvedCode = $dealerV1['salons'][mb_strtolower($salonNameV1)] ?? null;

        if (!$resolvedCode) throw new \Exception("Салон '{$salonNameV1}' не найден в маппинге");

        $analysis['old_salon_code'] = $resolvedCode;
        $analysis['found_in_v1'] = true;

        // Проверка существования в V2
        $innExists = isset($newDealers[$analysis['old_inn']]);
        $salonExists = isset($newSalons[$resolvedCode]);

        if ($innExists && $salonExists) {
          $analysis['exists_in_v2'] = true;
          if (isset($newLinks[$analysis['old_inn'] . '_' . $resolvedCode])) {
            $analysis['link_valid_v2'] = true;
          } else {
            $analysis['error'] = "В V2 нет связи ИНН-Салон";
          }
        } else {
          $analysis['error'] = (!$innExists ? "ИНН нет в V2. " : "") . (!$salonExists ? "Салона нет в V2." : "");
        }
      } catch (\Throwable $e) {
        $analysis['error'] = $e->getMessage();
      }

      $results[] = $analysis;
    }

    return $results;
  }

  public function migrate(): array
  {
    $report = ['total' => 0, 'updated' => 0, 'skipped' => 0, 'already_filled' => 0, 'errors' => []];
    $results = $this->analyze();

    foreach ($results as $res) {
      $report['total']++;

      // Если поля уже заполнены (миграция не нужна)
      if ($res['is_already_filled']) {
        $report['already_filled']++;
        continue;
      }

      // Обновляем только если данные найдены и валидны
      if ($res['found_in_v1'] && $res['old_inn'] && $res['old_salon_code']) {
        try {
          OrderTable::update($res['order_id'], [
            'inn_dealer' => $res['old_inn'],
            'salon_code' => $res['old_salon_code'],
          ]);
          $report['updated']++;
        } catch (\Throwable $e) {
          $report['errors'][] = "ID {$res['order_id']}: " . $e->getMessage();
        }
      } else {
        $report['skipped']++;
      }
    }
    return $report;
  }

  private function extractSalonData(mixed $item): ?array
  {
    if (!is_array($item)) return null;
    $name = '';
    $code = '';
    if (isset($item['name'], $item['code'])) {
      $name = trim((string)$item['name']);
      $code = trim((string)$item['code']);
    } elseif (count($item) >= 2) {
      $name = trim((string)($item[0] ?? ''));
      $code = trim((string)($item[1] ?? ''));
    }
    return ($name === '' || $code === '') ? null : ['name' => $name, 'code' => $code];
  }
}