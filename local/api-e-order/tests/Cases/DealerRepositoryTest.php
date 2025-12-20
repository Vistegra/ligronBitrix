<?php

namespace Tests\Cases;

use Tests\Core\TestCase;
use OrderApi\DB\Repositories\DealerUserRepository;

class DealerRepositoryTest extends TestCase
{

  // ТЕСТОВЫЕ ДАННЫЕ

  const string TEST_INN = '000000000001';
  const string EXPECTED_PREFIX = 'pro_';
  const int EXPECTED_DEALER_ID = 1;

  const string TEST_SALON_CODE = '017587916';
  const int EXPECTED_USER_ID = 12;

  const string TEST_LOGIN = '9250826792';

  // ТЕСТЫ

  /**
   * Тест 1: Поиск дилера по ИНН (Проверка кэша и маппинга)
   * @throws \Exception
   */
  public function testGetDealerByInn_Success(): void
  {
    $dealer = DealerUserRepository::getDealerByInn(self::TEST_INN);

    $this->assertIsArray($dealer, "Дилер должен вернуться как массив");
    $this->assertEquals(self::EXPECTED_PREFIX, $dealer['prefix'], "Префикс дилера не совпадает");
    $this->assertEquals(self::EXPECTED_DEALER_ID, $dealer['id'], "ID дилера не совпадает");
    $this->assertNotNull($dealer['name'], "Имя дилера не должно быть пустым");
  }

  /**
   * Тест 2: Поиск дилера по несуществующему ИНН
   * @throws \Exception
   */
  public function testGetDealerByInn_NotFound(): void
  {
    $dealer = DealerUserRepository::getDealerByInn('999999999999_FAKE');
    $this->assertNull($dealer, "Должен вернуться NULL для несуществующего ИНН");
  }

  /**
   * Тест 3: Поиск ID пользователя по Коду Салона (Сценарий 1С)
   * Логика: ID Дилера -> Settings (Код->Имя) -> Таблица Юзеров (contacts.code) -> ID Юзера
   * @throws \Exception
   */
  public function testFindUserIdBySalonCode_Success(): void
  {
    $userId = DealerUserRepository::findUserIdBySalonCode(
      self::EXPECTED_DEALER_ID,
      self::EXPECTED_PREFIX,
      self::TEST_SALON_CODE
    );

    $this->assertNotNull($userId, "Пользователь не найден по коду салона " . self::TEST_SALON_CODE);
    $this->assertEquals(self::EXPECTED_USER_ID, $userId, "Найден неверный ID пользователя");
  }

  /**
   * Тест 4: Поиск по несуществующему коду салона
   * @throws \Exception
   */
  public function testFindUserIdBySalonCode_InvalidCode(): void
  {
    $userId = DealerUserRepository::findUserIdBySalonCode(
      self::EXPECTED_DEALER_ID,
      self::EXPECTED_PREFIX,
      'INVALID_CODE_123'
    );

    $this->assertNull($userId, "Должен вернуться NULL, если код салона не существует в настройках дилера");
  }

  /**
   * Тест 5: Поиск пользователя для авторизации (по логину)
   * @throws \Exception
   */
  public function testFindUserByLogin_Success(): void
  {
    $user = DealerUserRepository::findUserByLogin(self::TEST_LOGIN);

    $this->assertIsArray($user, "Пользователь должен вернуться как массив");
    $this->assertEquals(self::TEST_LOGIN, $user['login'], "Логин найденного пользователя не совпадает");

    // Проверяем, что метод "обогатил" пользователя данными о дилере
    $this->assertNotNull($user['dealer_id'], "В ответе должен быть dealer_id");
    $this->assertNotNull($user['dealer_prefix'], "В ответе должен быть dealer_prefix");

    // Дополнительная проверка целостности
    $this->assertEquals(self::EXPECTED_DEALER_ID, $user['dealer_id'], "ID дилера у пользователя определен неверно");
  }

  /**
   * Тест 6: Получение детальных данных (для профиля фронтенда)
   * Логика: ID Юзера -> Имя Салона -> Settings (Имя->Код) -> Код Салона
   * @throws \Exception
   */
  public function testFindDetailedUserByIds(): void
  {
    $details = DealerUserRepository::findDetailedUserByIds(
      self::EXPECTED_USER_ID,
      self::EXPECTED_DEALER_ID
    );

    $this->assertIsArray($details, "Детальные данные должны быть массивом");

    // Проверяем наличие ключевых полей
    $this->assertNotNull($details['dealer_name'], "Имя дилера обязательно");
    $this->assertNotNull($details['salon_name'], "Имя салона обязательно");

    // Проверяем, что код салона определился верно (обратная задача)
    $this->assertEquals(self::TEST_SALON_CODE, $details['salon_code'], "Код салона в профиле (обратный поиск) определен неверно");

    // Проверяем, что подтянулся ИНН
    $this->assertEquals(self::TEST_INN, $details['inn'], "ИНН в деталях профиля не совпадает");
  }
}