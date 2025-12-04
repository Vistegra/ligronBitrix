<?php

declare(strict_types=1);

namespace OrderApi\DB\Repositories;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use OrderApi\Constants\UserRole;
use OrderApi\DB\Models\DealerUserTable;
use OrderApi\DB\Models\WebManagerDealerTable;
use OrderApi\DB\Models\WebUserTable;
use PhpParser\Error;

class WebUserRepository
{
  public static function findUserByToken(string $token): ?array
  {
    try {
      $result = WebUserTable::getList([
        'select' => [
          'id',
          'login' => 'username',
          'code' => 'code_user',
          'name',
          'email',
          'phone',
          'manager',
        ],
        'filter' => [
          '=token' => $token,
          '=active' => 1,
        ],
        'limit' => 1,
      ]);

      return $result->fetch() ?: null;
    } catch (\Throwable $e) {
      return null;
    }
  }
  public static function findUserByLogin(string $login): ?array
  {
    try {
      $result = WebUserTable::getList([
        'select' => [
          'id',
          'login' => 'username',
          'code' => 'code_user',
          'password',
          'name',
          'email',
          'phone',
          'active',
          'manager',
        ],
        'filter' => [
          '=username' => $login,
          '=active' => 1,
        ],
        'limit' => 1,
      ]);

      return $result->fetch() ?: null;
    } catch (\Throwable $e) {

      return null;
    }
  }

  /**
   * Получает детальную информацию для менеджера/офис-менеджера.
   * Возвращает список своих дилеров + список дилеров, которых он замещает.
   *
   * @param int $userId ID пользователя в таблице WebUser.
   * @return array
   * @throws ArgumentException
   * @throws ObjectPropertyException
   * @throws SystemException
   */
  public static function fetchDetailedByUserId(int $userId): array
  {
    // Получаем данные текущего пользователя
    $user = WebUserTable::getList([
      'select' => ['code_user', 'manager'],
      'filter' => [
        '=id' => $userId,
        '=active' => 1,
      ],
      'limit' => 1,
    ])->fetch();

    if (!$user) {
      return [];
    }

    $currentUserCode = $user['code_user'];

    // Определяем колонку для фильтрации (менеджер или офис-менеджер)
    // Если manager=true (менеджер), то ищем по code_user; иначе по code_user_manager
    $targetColumn = $user['manager'] ? 'code_user_manager' : 'code_user';
    $filterKey = '=' . $targetColumn;

    // Получаем своих дилеров ---
    $ownInns = [];
    $resOwn = WebManagerDealerTable::getList([
      'select' => ['inn_dealer'],
      'filter' => [
        $filterKey => $currentUserCode,
        '=active' => 1,
      ],
    ]);
    while ($row = $resOwn->fetch()) {
      $ownInns[] = $row['inn_dealer'];
    }

    if (empty($ownInns)) {
      // Нет своих дилеров — можно сразу перейти к замещаемым
      $ownDealers = [];
    } else {
      // Обогащаем своих дилеров
      $ownDealers = self::enrichDealersData($ownInns, false);
    }

    // Получаем ЗАМЕЩАЕМЫХ дилеров
    $subDealers = [];
    $absentManagers = WebFillingRepository::getManagersOnVacation($currentUserCode);
    $absentCodes = array_column($absentManagers, 'code_user');

    if (!empty($absentCodes)) {
      $subInns = [];
      $resSub = WebManagerDealerTable::getList([
        'select' => ['inn_dealer'],
        'filter' => [
          '=' . $targetColumn => $absentCodes,
          '=active' => 1,
        ],
      ]);
      while ($row = $resSub->fetch()) {
        $subInns[] = $row['inn_dealer'];
      }

      if (!empty($subInns)) {
        // Обогащаем чужих дилеров
        $subDealers = self::enrichDealersData($subInns, true);
      }
    }

    // Объединяем списки
    $allDealers = array_merge($ownDealers, $subDealers);

    return [
      'managed_dealers' => $allDealers,
      'substituting_managers' => $absentManagers
    ];
  }

  /**
   * Вспомогательный метод: превращает массив ИНН в массив детальных данных о дилерах.
   *
   * @param array $innList Список ИНН (string[])
   * @param bool $isSubstituted Флаг: true, если это дилеры замещаемого коллеги
   * @return array
   */
  private static function enrichDealersData(array $innList, bool $isSubstituted): array
  {
    if (empty($innList)) {
      return [];
    }

    // Получаем карту дилеров из кэша
    $innToDealerMap = DealerUserRepository::getInnToDealerCacheMap();

    $result = [];

    foreach ($innList as $inn) {
      if (!isset($innToDealerMap[$inn])) {
        continue;
      }

      $dealerData = $innToDealerMap[$inn];
      $prefix = $dealerData['prefix'];

      // Получаем пользователей этого дилера
      $users = [];
      try {
        $dataClass = DealerUserTable::getEntityClassByPrefix($prefix);
        // Выбираем только активных пользователей, чтобы не тянуть мусор
        $users = $dataClass::getList([
          'select' => ['id', 'name'],
          'filter' => ['=activity' => 1]
        ])->fetchAll();
      } catch (\Throwable $e) {
        // Если таблица дилера не существует или ошибка — список юзеров пуст
        $users = [];
        // TODO: e.g. LogService::error($e);
      }

      $result[] = [
        'inn' => $inn,
        'dealer_id' => $dealerData['id'],
        'name' => $dealerData['name'],
        'dealer_prefix' => $prefix,
        'users' => $users,
        'is_substituted' => $isSubstituted,
      ];
    }

    return $result;
  }

  /**
   * Получает данные менеджера и офис-менеджера по ИНН дилера.
   * Если сотрудник в отпуске, добавляет его заместителя с соответствующим флагом.
   *
   * @param string $innDealer ИНН дилера.
   * @return array Возвращает массив с данными менеджеров.
   */
  public static function getManagerDetailsByInn(string $innDealer): array
  {
    $innDealer = trim($innDealer);
    if ($innDealer === '') {
      return [];
    }

    $managerCodes = WebManagerDealerTable::getList([
      'select' => ['code_user', 'code_user_manager'],
      'filter' => [
        '=inn_dealer' => $innDealer,
      ],
      'limit' => 1, // Дилер связан с одним менеджером и одним офис-менеджером через одну запись
    ])->fetch();

    if (!$managerCodes) {
      return [];
    }

    $result = [];

    // Перебираем коды (менеджера и офис-менеджера)
    foreach ($managerCodes as $managerCode) {

      if (empty($managerCode) || !is_string($managerCode)) {
        continue;
      }

      $manager = WebUserTable::getList([
        'select' => ['name', 'email', 'phone', 'code_user', 'manager'],
        'filter' => [
          '=code_user' => $managerCode,
          '=active' => 1,
        ],
        'limit' => 1,
      ])->fetch();

      if (!$manager) {
        continue;
      }

      $role = $manager['manager'] ? UserRole::MANAGER : UserRole::OFFICE_MANAGER;

      // Базовая структура данных сотрудника
      $managerData = [
        'code_user' => $managerCode,
        'name' => $manager['name'],
        'email' => $manager['email'],
        'phone' => $manager['phone'],
        'role' => $role,
        'is_on_vacation' => false,
        'is_substitute' => false,
      ];

      // Проверяем, не в отпуске ли сотрудник (ищем заместителя)
      $substitute = WebFillingRepository::getSubstituteManager($managerCode);

      if ($substitute) {
        // Помечаем текущего менеджера как "в отпуске"
        $managerData['is_on_vacation'] = true;

        // Формируем данные заместителя
        $subRole = $substitute['manager'] ? UserRole::MANAGER : UserRole::OFFICE_MANAGER;

        $result[] = [
          'code_user' => $substitute['code_user'],
          'name' => $substitute['name'],
          'email' => $substitute['email'],
          'phone' => $substitute['phone'],
          'role' => $subRole,
          'is_on_vacation' => false,
          'is_substitute' => true,
          'substituting_for' => $managerCode, // Ссылка, кого замещает
        ];
      }

      // Добавляем основного сотрудника в список
      $result[] = $managerData;
    }

    return $result;
  }

}