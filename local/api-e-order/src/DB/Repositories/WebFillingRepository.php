<?php

declare(strict_types=1);

namespace OrderApi\DB\Repositories;

use Bitrix\Main\Type\Date;
use OrderApi\DB\Models\WebFillingTable;
use OrderApi\DB\Models\WebUserTable;

class WebFillingRepository
{
  /**
   * Найти менеджеров, которые сейчас в отпуске и которых замещает текущий сотрудник.
   * Возвращает массив данных пользователей.
   *
   * @param string $managerCode Код текущего менеджера (code_user), который работает (заместитель)
   * @return array<int, array> Массив данных менеджеров из WebUserTable
   */
  public static function getManagersOnVacation(string $managerCode): array
  {
    $today = new Date();

    $rows = WebFillingTable::getList([
      'select' => ['code_user'],
      'filter' => [
        '=code_user_filling' => $managerCode,
        '<=date_from' => $today,
        '>=date_to' => $today,
      ]
    ])->fetchAll();

    $absentUserCodes = array_column($rows, 'code_user');

    if (empty($absentUserCodes)) {
      return [];
    }

    // Получаем детальные данные тех, кто в отпуске
    return WebUserTable::getList([
      'select' => ['code_user', 'name', 'email', 'phone', 'manager'],
      'filter' => [
        '=code_user' => $absentUserCodes,
        '=active' => 1
      ]
    ])->fetchAll();
  }

  /**
   * Найти менеджера, который замещает сотрудника $managerCode.
   * (Если $managerCode сейчас в отпуске).
   *
   * @param string $managerCode Код сотрудника, который (возможно) в отпуске
   * @return array|null Данные замещающего или null
   */
  public static function getSubstituteManager(string $managerCode): ?array
  {
    $today = new Date();

    $row = WebFillingTable::getList([
      'select' => ['code_user_filling'],
      'filter' => [
        '=code_user' => $managerCode,
        '<=date_from' => $today,
        '>=date_to' => $today,
      ],
      'limit' => 1
    ])->fetch();

    if (!$row) {
      return null;
    }

    $substituteCode = $row['code_user_filling'];

    // Получаем данные заместителя
    $user = WebUserTable::getList([
      'select' => ['code_user', 'name', 'email', 'phone', 'manager'],
      'filter' => [
        '=code_user' => $substituteCode,
        '=active' => 1
      ],
      'limit' => 1
    ])->fetch();

    return $user ?: null;
  }
}