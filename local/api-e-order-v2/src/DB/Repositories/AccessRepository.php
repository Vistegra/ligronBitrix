<?php

declare(strict_types=1);

namespace OrderApiV2\DB\Repositories;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Date;
use OrderApiV2\Config\CacheConfig;
use OrderApiV2\DB\Models\DealerSalonTable;
use OrderApiV2\DB\Models\DealerTable;
use OrderApiV2\DB\Models\FillingTable;
use OrderApiV2\DB\Models\DealerLigronTable;
use OrderApiV2\DB\Models\LigronUserTable;
use OrderApiV2\DB\Models\SalonTable;
use OrderApiV2\Traits\CacheableTrait;

class AccessRepository
{
  use CacheableTrait;

  private const string CACHE_DIR = '/order_api_v2/hierarchy';

  public static function getDealerHierarchy(string $startSalonCode): array
  {
    return self::cache(
      cacheId: CacheConfig::getDealerHierarchyKey($startSalonCode),
      ttl: CacheConfig::TTL_HIERARCHY,
      callback: fn() => self::buildHierarchyInMemory($startSalonCode),
      cacheDir: CacheConfig::DIR_HIERARCHY,
    );
  }

  public static function getLigronHierarchy(string $userCode): array
  {
    return self::cache(
      cacheId: CacheConfig::getLigronHierarchyKey($userCode),
      ttl: CacheConfig::TTL_HIERARCHY,
      callback: fn() => self::buildLigronHierarchyData($userCode),
      cacheDir: CacheConfig::DIR_HIERARCHY,
    );
  }

  /**
   * @throws ObjectPropertyException
   * @throws SystemException
   * @throws ArgumentException
   */
  private static function buildHierarchyInMemory(string $startSalonCode): array
  {
    $allLinks = DealerSalonTable::getList(['select' => ['inn_dealer', 'salon_code']])->fetchAll();
    $salonToDealers = [];
    $dealerToSalons = [];

    foreach ($allLinks as $link) {
      $inn = trim((string)$link['inn_dealer']);
      $code = trim((string)$link['salon_code']);
      $salonToDealers[$code][] = $inn;
      $dealerToSalons[$inn][] = $code;
    }

    $processedSalons = [];
    $processedDealers = [];
    $queue = [$startSalonCode];

    while (!empty($queue)) {
      $currSalon = array_shift($queue);
      if (isset($processedSalons[$currSalon])) continue;
      $processedSalons[$currSalon] = true;

      foreach (($salonToDealers[$currSalon] ?? []) as $inn) {
        if (!isset($processedDealers[$inn])) {
          $processedDealers[$inn] = true;
          foreach (($dealerToSalons[$inn] ?? []) as $s) {
            if (!isset($processedSalons[$s])) $queue[] = $s;
          }
        }
      }
    }

    $innsWithSubFlags = array_fill_keys(array_keys($processedDealers), false);

    return self::hydrateHierarchyTree($innsWithSubFlags, array_keys($processedSalons), $dealerToSalons);
  }

  /**
   * @throws ObjectPropertyException
   * @throws ArgumentException
   * @throws SystemException
   */
  private static function buildLigronHierarchyData(string $userCode): array
  {
    $today = new Date();

    $substitutions = FillingTable::getList([
      'select' => ['code_user'],
      'filter' => ['=code_user_filling' => $userCode, '<=date_from' => $today, '>=date_to' => $today]
    ])->fetchAll();

    $subCodes = array_column($substitutions, 'code_user');
    $allCodes = array_merge([$userCode], $subCodes);

    $dealerLinks = DealerLigronTable::getList([
      'select' => ['inn_dealer', 'user_code'],
      'filter' => ['=user_code' => $allCodes, '=active' => 1]
    ])->fetchAll();

    $innsWithSubFlags = [];
    foreach ($dealerLinks as $link) {
      $inn = trim((string)$link['inn_dealer']);
      $isSub = in_array($link['user_code'], $subCodes);
      if (!isset($innsWithSubFlags[$inn]) || $innsWithSubFlags[$inn] === true) {
        $innsWithSubFlags[$inn] = $isSub;
      }
    }



    $finalInns = array_map('strval', array_keys($innsWithSubFlags));

    $salonLinks = DealerSalonTable::getList([
      'select' => ['inn_dealer', 'salon_code'],
      'filter' => ['=inn_dealer' => $finalInns]
    ])->fetchAll();


    $finalSalons = array_unique(array_column($salonLinks, 'salon_code'));

    $dealerToSalonsMap = [];
    foreach ($salonLinks as $link) {
      $dealerToSalonsMap[trim((string)$link['inn_dealer'])][] = trim((string)$link['salon_code']);
    }

    $result = self::hydrateHierarchyTree($innsWithSubFlags, $finalSalons, $dealerToSalonsMap);
    $result['substituting_codes'] = $subCodes;

    return $result;
  }

  /**
   * @throws ArgumentException
   * @throws ObjectPropertyException
   * @throws SystemException
   */
  private static function hydrateHierarchyTree(array $innsWithSubFlags, array $salonCodes, array $dealerToSalonsMap): array
  {
    $inns = array_values(array_map('strval', array_keys($innsWithSubFlags)));
    $salonCodes = array_values(array_map('strval', $salonCodes));

    if (empty($inns)) {
      return [
        'managed_dealers' => [],
        'available_inns' => [],
        'available_salons' => []
      ];
    }

    $dealersData = DealerTable::getList([
      'select' => ['inn_dealer', 'name'],
      'filter' => ['=inn_dealer' => $inns, '=active' => 1]
    ])->fetchAll();

    $salonsData = SalonTable::getList([
      'select' => ['salon_code', 'name'],
      'filter' => ['=salon_code' => $salonCodes, '=active' => 1]
    ])->fetchAll();

    $salonNamesMap = [];
    foreach ($salonsData as $s) {
      $salonNamesMap[trim((string)$s['salon_code'])] = trim((string)$s['name']);
    }

    $managedDealers = [];
    foreach ($dealersData as $d) {
      $inn = trim((string)$d['inn_dealer']);
      $dealerSalons = [];

      foreach (($dealerToSalonsMap[$inn] ?? []) as $sCode) {
        $sCodeStr = trim((string)$sCode);
        if (isset($salonNamesMap[$sCodeStr])) {
          $dealerSalons[] = [
            'salon_code' => $sCodeStr,
            'name' => $salonNamesMap[$sCodeStr]
          ];
        }
      }

      $managedDealers[] = [
        'inn' => $inn,
        'name' => trim((string)$d['name']),
        'is_substituted' => $innsWithSubFlags[$inn] ?? false,
        'salons' => $dealerSalons
      ];
    }

    return [
      'managed_dealers' => $managedDealers,
      'available_salons' => $salonCodes,
      'available_inns' => $inns
    ];
  }

  /**
   * @throws ObjectPropertyException
   * @throws ArgumentException
   * @throws SystemException
   */
  public static function getLigronManagersForInns(array $inns): array
  {
    if (empty($inns)) return [];

    $links = DealerLigronTable::getList([
      'select' => ['user_code'],
      'filter' => ['=inn_dealer' => $inns, '=active' => 1]
    ])->fetchAll();

    $baseUserCodes = array_unique(array_column($links, 'user_code'));
    if (empty($baseUserCodes)) return [];

    $today = new Date();
    $resultManagers = [];

    foreach ($baseUserCodes as $code) {
      $sub = FillingTable::getList([
        'select' => ['code_user_filling'],
        'filter' => ['=code_user' => $code, '<=date_from' => $today, '>=date_to' => $today],
        'limit' => 1
      ])->fetch();

      $activeCode = $sub ? trim((string)$sub['code_user_filling']) : trim((string)$code);

      $managerData = LigronUserTable::getList([
        'select' => ['user_code', 'name', 'email', 'phone', 'role_code'],
        'filter' => ['=user_code' => $activeCode, '=active' => 1],
        'limit' => 1
      ])->fetch();

      if ($managerData) {
        $resultManagers[] = [
          'code_user' => trim((string)$managerData['user_code']),
          'name' => trim((string)$managerData['name']),
          'email' => trim((string)$managerData['email']),
          'phone' => trim((string)$managerData['phone']),
          'role' => trim((string)$managerData['role_code']),
          'is_substitute' => (bool)$sub,
        ];
      }
    }
    return $resultManagers;
  }

}