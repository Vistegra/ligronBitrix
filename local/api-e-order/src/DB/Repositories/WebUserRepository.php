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

class WebUserRepository
{
  public static function findUserByToken(string $token): ?array
  {
    try {
      $result = WebUserTable::getList([
        'select' => [
          'id',
          'login' => 'username',
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
   * @throws ArgumentException
   * @throws ObjectPropertyException
   * @throws SystemException
   */
  public static function fetchDetailedByUserId(int $userId): array
  {
     $user = WebUserTable::getList([
      'filter' => [
        '=id' => $userId,
        '=active' => 1,
      ],
      'limit' => 1,
    ])->fetch();

    //Колонка manager указывает на принадлежность к менеджеру
    // false для офис-менеджера, true - менеджера
    //'code_user_manager' - офис менеджер,  'code_user' - менеджер
    $codeUserRole = $user['manager'] === true ? 'code_user' : 'code_user_manager';

    $dealersInn = WebManagerDealerTable::getList([
      'select' => ['inn_dealer'],
      'filter' => [
        $codeUserRole => $user['code_user'],
        '=active'            => 1,
      ],
      'cache' => ['ttl' => 300], // 5 минут
    ])->fetchAll();


    $innToDealerMap = DealerUserRepository::getInnToDealerCacheMap();

    $dealers = [];

    foreach ($dealersInn as $dealer) {
      $inn = $dealer['inn_dealer'];
      $dealer = $innToDealerMap[$inn];

      if (!$dealer) continue; //ToDo log

      $dataClass = DealerUserTable::getEntityClassByPrefix($dealer['prefix']);

      $users = $dataClass::getList([
        'select' => ['id','name']
      ])->fetchAll();

      $dealers[] = [
        'inn' => $inn,
        'dealer_id' => $dealer['id'],
        'name' => $dealer['name'],
        'dealer_prefix' => $dealer['prefix'],
        'users' => $users,
      ];

    }

    return [
      'managed_dealers' => $dealers,
    ];
  }

  /**
   * Получает данные менеджера и офис-менеджера по ИНН дилера.
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

    // 1. Ищем связки manager-dealer по ИНН дилера
    $managerCodes = WebManagerDealerTable::getList([
      'select' => ['code_user', 'code_user_manager'],
      'filter' => [
        '=inn_dealer' => $innDealer,
      ],
      'limit' => 1, // Дилер связан с одним менеджером и одним офис-менеджером через одну запись
    ])->fetch();

    //ToDo нати замещающего
    $result = [];

    foreach ($managerCodes as $managerCode) {
      $manager = WebUserTable::getList([
        'select' => ['name', 'email', 'phone', 'code_user', 'manager'],
        'filter' => [
          '=code_user' => $managerCode,
          '=active' => 1,
        ],
        'limit' => 1,
      ])->fetch();

      if ($manager) {
        $result[] = [
          'code_user' => $managerCode,
          'name' => $manager['name'],
          'email' => $manager['email'],
          'phone' => $manager['phone'],
          'role' => $manager['manager'] ? UserRole::MANAGER : UserRole::OFFICE_MANAGER,
        ];
      }

    }

    return $result;
  }

}