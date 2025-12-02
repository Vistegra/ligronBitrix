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
   * Возвращает массив данных пользователей
   *
   * @param string $managerCode Код текущего менеджера (code_user), который работает
   * @return array<int, array> Массив данных менеджеров из WebUserTable
   */
  public static function getManagersInHolidayDetailed(string $managerCode): array
  {
    $today = new Date();

    // Список кодов тех, кто в отпуске
    $rows = WebFillingTable::getList([
      'select' => ['code_user'],
      'filter' => [
        '=code_user_filling' => $managerCode, // Кто замещает
        '<=date_from' => $today,
        '>=date_to' => $today,
      ]
    ])->fetchAll();

    $absentCodes = array_column($rows, 'code_user');

    if (empty($absentCodes)) {
      return [];
    }

    // Данные этих менеджеров
    return WebUserTable::getList([
      'select' => ['code_user', 'name', 'email', 'phone', 'manager'],
      'filter' => [
        '=code_user' => $absentCodes,
        '=active' => 1
      ]
    ])->fetchAll();
  }

  /**
   * Найти менеджера, который замещает сотрудника $managerCode.
   * Возвращает массив данных пользователя или null.
   *
   * @param string $managerCode Код сотрудника, который в отпуске
   * @return array|null Данные замещающего или null
   */
  public static function getSubstituteManagerDetailed(string $managerCode): ?array
  {
    $today = new Date();

    // Код заместителя
    $row = WebFillingTable::getList([
      'select' => ['code_user_filling'],
      'filter' => [
        '=code_user' => $managerCode, // Я в отпуске
        '<=date_from' => $today,
        '>=date_to' => $today,
      ],
      'limit' => 1
    ])->fetch();

    $substituteCode = $row['code_user_filling'] ?? null;

    if (!$substituteCode) {
      return null;
    }

    // Данные заместителя
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