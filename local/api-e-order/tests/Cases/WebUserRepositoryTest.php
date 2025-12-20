<?php

namespace Tests\Cases;

use Tests\Core\TestCase;
use OrderApi\DB\Repositories\WebUserRepository;

class WebUserRepositoryTest extends TestCase
{
  // =========================================================================
  // ТЕСТОВЫЕ ДАННЫЕ
  // =========================================================================

  // 1. МЕНЕДЖЕР (Вход по логину)
  const int TEST_MANAGER_ID = 2;           // Залога Дмитрий
  const string TEST_MANAGER_LOGIN = '9250826792';

  // 2. ОФИС-МЕНЕДЖЕР (Вход по токену)
  const int TEST_OFFICE_MANAGER_ID = 8;    // Оськина Наталья
  const string TEST_OFFICE_TOKEN = 'YPi4AGITL09hUYpoY3AvqQ'; // Токен

  // 3. СВЯЗИ
  // ИНН дилера, привязанного к менеджеру (ID 2) и офис-менеджеру (ID 5 - Обдаленкова)
  const string TEST_INN = '4003029781';

  // =========================================================================
  // ТЕСТЫ
  // =========================================================================

  /**
   * Тест 1: Поиск ОФИС-МЕНЕДЖЕРА по Токену
   * Используется при входе по ссылке (loginByToken)
   * @throws \Exception
   */
  public function testFindUserByToken_Success(): void
  {
    $user = WebUserRepository::findUserByToken(self::TEST_OFFICE_TOKEN);

    $this->assertIsArray($user, "Пользователь должен вернуться как массив");
    $this->assertEquals(self::TEST_OFFICE_MANAGER_ID, $user['id'], "ID офис-менеджера не совпадает");
    $this->assertNotNull($user['code'], "У пользователя должен быть код (code_user)");
  }

  /**
   * Тест 2: Поиск пользователя по несуществующему токену
   * @throws \Exception
   */
  public function testFindUserByToken_NotFound(): void
  {
    $user = WebUserRepository::findUserByToken('INVALID_TOKEN_999');
    $this->assertNull($user, "Должен вернуться NULL для неверного токена");
  }

  /**
   * Тест 3: Поиск МЕНЕДЖЕРА по Логину
   * Используется при обычной авторизации
   * @throws \Exception
   */
  public function testFindUserByLogin_Success(): void
  {
    $user = WebUserRepository::findUserByLogin(self::TEST_MANAGER_LOGIN);

    $this->assertIsArray($user, "Пользователь должен вернуться как массив");
    $this->assertEquals(self::TEST_MANAGER_ID, $user['id'], "ID менеджера не совпадает");

    // Проверка пароля (в этой базе он хранится в открытом виде и совпадает с логином)
    $this->assertEquals(self::TEST_MANAGER_LOGIN, $user['password'], "Пароль должен совпадать с логином (для этой базы)");
  }

  /**
   * Тест 4: Получение детальных данных МЕНЕДЖЕРА (Dashboard)
   * Проверяет, что менеджер видит своих дилеров.
   * @throws \Exception
   */
  public function testFetchDetailedForManager(): void
  {
    $details = WebUserRepository::fetchDetailedByUserId(self::TEST_MANAGER_ID);

    $this->assertIsArray($details, "Результат должен быть массивом");

    $this->assertNotNull($details['managed_dealers'], "Ключ managed_dealers должен существовать");
    $this->assertIsArray($details['managed_dealers'], "managed_dealers должен быть массивом");

    // Проверяем, что есть хотя бы один дилер (согласно дампу links, у Залоги их много)
    $this->assertTrue(count($details['managed_dealers']) > 0, "У менеджера должны быть привязанные дилеры");

    $firstDealer = $details['managed_dealers'][0];
    $this->assertNotNull($firstDealer['inn'], "У дилера должен быть ИНН");
  }

  /**
   * Тест 5: Получение детальных данных ОФИС-МЕНЕДЖЕРА
   * Проверяет, что офис-менеджер ТОЖЕ видит дилеров (через свою связь в links)
   * @throws \Exception
   */
  public function testFetchDetailedForOfficeManager(): void
  {

    $details = WebUserRepository::fetchDetailedByUserId(self::TEST_OFFICE_MANAGER_ID);

    $this->assertIsArray($details, "Результат должен быть массивом");
    $this->assertNotNull($details['managed_dealers'], "Ключ managed_dealers должен существовать");

    // У Оськиной (ID 6) есть связь с 890203495598
    $this->assertTrue(count($details['managed_dealers']) > 0, "У офис-менеджера должны быть привязанные дилеры");
  }

  /**
   * Тест 6: Получение менеджеров, закрепленных за Дилером (по ИНН)
   * @throws \Exception
   */
  public function testGetManagerDetailsByInn(): void
  {
    $managers = WebUserRepository::getManagerDetailsByInn(self::TEST_INN);

    $this->assertIsArray($managers, "Результат должен быть массивом");

    // Для ИНН 4003029781 в links:
    // code_user (Офис): CB0002657 (Обдаленкова)
    // code_user_manager (Менеджер): 000000051 (Залога)

    $this->assertTrue(count($managers) >= 1, "Должен быть найден хотя бы один менеджер");

    $foundManager = false;
    foreach ($managers as $manager) {
      if ($manager['code_user'] === '000000051') { // Код Залоги
        $foundManager = true;
        $this->assertEquals('manager', $manager['role']);
        break;
      }
    }
    $this->assertTrue($foundManager, "Менеджер Залога должен быть найден для этого ИНН");
  }
}