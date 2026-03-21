<?php

declare(strict_types=1);

namespace OrderApiV2\Services\Migration;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use OrderApiV2\DB\Models\OldDealerTable;
use OrderApiV2\DB\Models\OldDealerUserTable;
use OrderApiV2\DB\Models\DealerTable as NewDealerTable;
use OrderApiV2\DB\Models\SalonTable as NewSalonTable;
use OrderApiV2\DB\Models\DealerSalonTable as NewDealerSalonTable;
use OrderApiV2\DB\Models\DealerUserTable as NewDealerUserTable;

class MigrationAnalyzerService
{
  /**
   * @throws ObjectPropertyException
   * @throws ArgumentException
   * @throws SystemException
   */
  public function analyze(): array
  {
    $report = [
      'stats' => [
        'old_dealers' => 0, 'old_salons' => 0, 'old_users' => 0,
        'new_dealers' => 0, 'new_salons' => 0, 'new_users' => 0,
      ],
      'errors' => [
        'old_db_bad_data' => [],
        'missing_dealers' => [],
        'missing_salons' => [],
        'missing_users' => [],
        'missing_dealer_salon_link' => [],
      ]
    ];

    // 1. Загружаем новые данные (MS SQL)
    $newDealers = array_column(NewDealerTable::getList(['select' => ['inn_dealer', 'name']])->fetchAll(), 'name', 'inn_dealer');
    $newSalons = array_column(NewSalonTable::getList(['select' => ['salon_code', 'name']])->fetchAll(), 'name', 'salon_code');
    $newUsers = array_column(NewDealerUserTable::getList(['select' => ['username', 'id']])->fetchAll(), 'id', 'username');

    $newLinksRaw = NewDealerSalonTable::getList(['select' => ['inn_dealer', 'salon_code']])->fetchAll();
    $newLinks = [];
    foreach ($newLinksRaw as $link) {
      $newLinks[$link['inn_dealer'] . '_' . $link['salon_code']] = true;
    }

    $report['stats']['new_dealers'] = count($newDealers);
    $report['stats']['new_salons'] = count($newSalons);
    $report['stats']['new_users'] = count($newUsers);

    // 2. Выбираем старых дилеров (MySQL)
    $oldDealers = OldDealerTable::getList([
      'select' => ['id', 'name', 'cms_param', 'settings'],
      'filter' => ['=activity' => 1]
    ])->fetchAll();

    foreach ($oldDealers as $oldDealer) {
      $prefix = $oldDealer['cms_param']['prefix'] ?? null;
      $inn = trim((string)($oldDealer['settings']['prop_tin'] ?? ''));
      $dealerName = trim((string)$oldDealer['name']);

      if (!$prefix) continue;
      $report['stats']['old_dealers']++;

      if (empty($inn)) {
        $report['errors']['old_db_bad_data'][] = "Дилер ID {$oldDealer['id']} '{$dealerName}' не имеет ИНН (prop_tin).";
        continue;
      }

      if (!isset($newDealers[$inn])) {
        $report['errors']['missing_dealers'][] = "Дилер ИНН: {$inn} ({$dealerName}) отсутствует в новой БД.";
      }

      // Разбираем салоны
      $rawSalons = $oldDealer['settings']['prop_dealercode'] ?? [];
      $salonsMap = [];

      if (is_array($rawSalons)) {
        foreach ($rawSalons as $item) {
          $salonData = $this->extractSalonData($item);
          if ($salonData) {
            $report['stats']['old_salons']++;
            $sName = $salonData['name'];
            $sCode = $salonData['code'];
            $salonsMap[mb_strtolower($sName)] = $sCode;

            if (!isset($newSalons[$sCode])) {
              $report['errors']['missing_salons'][] = "Салон Код: {$sCode} ({$sName}) отсутствует в новой БД (ИНН: {$inn}).";
            }

            if (isset($newDealers[$inn]) && isset($newSalons[$sCode]) && !isset($newLinks[$inn . '_' . $sCode])) {
              $report['errors']['missing_dealer_salon_link'][] = "Связь ИНН {$inn} <-> Салон {$sCode} отсутствует.";
            }
          }
        }
      } else {
        $report['errors']['old_db_bad_data'][] = "Дилер ИНН {$inn} не имеет корректного списка салонов.";
      }

      // Разбираем пользователей (Динамические таблицы)
      try {
        $userClass = OldDealerUserTable::getEntityClassByPrefix($prefix);
        $oldUsers = $userClass::getList([
          'select' => ['id', 'login', 'name', 'contacts', 'activity'],
          'filter' => ['=activity' => 1]
        ])->fetchAll();

        foreach ($oldUsers as $oldUser) {
          $report['stats']['old_users']++;
          $login = trim((string)$oldUser['login']);
          $userSalonName = trim((string)($oldUser['contacts']['code'] ?? ''));

          if (!isset($newUsers[$login])) {
            $report['errors']['missing_users'][] = "Пользователь Логин: {$login} ({$oldUser['name']}) отсутствует в новой БД.";
          }

          if (empty($userSalonName)) {
            $report['errors']['old_db_bad_data'][] = "У пользователя {$login} (Дилер {$inn}) не указано имя салона в contacts.code.";
          } else {
            $userSalonCode = $salonsMap[mb_strtolower($userSalonName)] ?? null;
            if (!$userSalonCode) {
              $report['errors']['old_db_bad_data'][] = "Имя салона '{$userSalonName}' у пользователя {$login} не найдено в настройках дилера {$inn}.";
            }
          }
        }
      } catch (\Throwable $e) {
        // Игнорируем ошибку, если таблицы {prefix}users просто не существует
        $report['errors']['old_db_bad_data'][] = "Не удалось прочитать таблицу {$prefix}users: " . $e->getMessage();
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

    if ($name === '' || $code === '') return null;
    return ['name' => $name, 'code' => $code];
  }
}