<?php

namespace Tests\Cases;

use Tests\Core\TestCase;
use OrderApi\Services\Order\Webhook1cOrderService;
use OrderApi\DB\Repositories\OrderRepository;

class WebhookServiceTest extends TestCase
{
  // =========================================================================
  // ТЕСТОВЫЕ ДАННЫЕ
  // =========================================================================

  // Существующие данные в БД (для маппинга)
  const string TEST_INN = '4003029781';      // ИНН дилера
  const string TEST_SALON_CODE = '050788108';  // Код салона

  // Статусы (коды из vs_e_order_status)
  const string STATUS_NEW = '101';             // Код для "Новый"
  const string STATUS_WORK = '102';            // Код для "Отправлен просчет"

  // =========================================================================
  // ТЕСТЫ
  // =========================================================================

  /**
   * Тест 1: Успешное создание заказа
   * Проверяет полный цикл: от JSON до записи в БД
   * @throws \Exception
   */
  public function testCreateOrderFrom1C_Success(): void
  {
    $service = new Webhook1cOrderService();

    // Генерируем уникальный номер для этого запуска
    $testNumber = 'TEST_CREATE_' . time();

    $inputData = [
      'ligron_number' => $testNumber,
      'client' => self::TEST_INN,
      'salon' => self::TEST_SALON_CODE,
      'name' => 'Test Order via PHPUnit',
      'origin_type' => 1, // 1C
      'status_code' => self::STATUS_NEW,
      'status_date' => '01.01.2025 10:00:00',
      'production_date' => '15.01.2025',
      'production_time' => 14,
      'percent_payment' => 50,
      'date' => '01.01.2025 09:00:00', // Техническое поле даты заказа
      'comment' => 'Created by automated test'
    ];

    // 1. Выполняем создание
    $order = $service->createOrderFrom1C($inputData);

    // 2. Проверяем результат (объект в памяти)
    $this->assertIsArray($order, "Метод должен вернуть массив заказа");
    $this->assertEquals($testNumber, $order['number'], "Номер заказа не совпадает");
    $this->assertEquals(1, $order['origin_type'], "Тип источника должен быть 1 (1С)");

    // 3. Проверяем, что заказ реально есть в БД (читаем заново)
    $dbOrder = OrderRepository::getById((int)$order['id']);
    $this->assertNotNull($dbOrder, "Заказ не найден в БД после создания");
    $this->assertEquals(50, $dbOrder['percent_payment'], "Процент оплаты не сохранился");
    $this->assertEquals(14, $dbOrder['production_time'], "Срок изготовления не сохранился");
    $this->assertEquals('15.01.2025', $dbOrder['ready_date'], "Дата готовности не совпадает");

    // 4. Проверяем статус и историю
    // Внимание: status_code в репозитории может возвращаться как 'code' из join-а
    $this->assertEquals(self::STATUS_NEW, $dbOrder['status_code'], "Код статуса не совпадает");
    $this->assertIsArray($dbOrder['status_history'], "История статусов должна быть массивом");
    $this->assertEquals(1, count($dbOrder['status_history']), "В истории должна быть 1 запись");
  }

  /**
   * Тест 2: Попытка создания дубликата (должна быть ошибка 409)
   * @throws \Exception
   */
  public function testCreateOrder_DuplicateThrowsException(): void
  {
    $service = new Webhook1cOrderService();
    $testNumber = 'TEST_DUP_' . time();

    $inputData = [
      'ligron_number' => $testNumber,
      'client' => self::TEST_INN,
      'salon' => self::TEST_SALON_CODE,
      'name' => 'Duplicate Test',
      'origin_type' => 1,
      'status_code' => self::STATUS_NEW,
      'status_date' => '01.01.2025',
      'date' => '01.01.2025'
    ];

    // 1. Создаем первый раз (успешно)
    $service->createOrderFrom1C($inputData);

    // 2. Создаем второй раз (ожидаем ошибку)
    try {
      $service->createOrderFrom1C($inputData);
      $this->assertTrue(false, "Метод должен был выбросить исключение 409, но не выбросил");
    } catch (\RuntimeException $e) {
      $this->assertEquals(409, $e->getCode(), "Ожидался код ошибки 409 (Conflict)");
    }
  }

  /**
   * Тест 3: Обновление статуса и полей заказа
   * @throws \Exception
   */
  public function testUpdateOrderFrom1C_Success(): void
  {
    $service = new Webhook1cOrderService();
    $testNumber = 'TEST_UPD_' . time();

    // 1. Создаем исходный заказ
    $service->createOrderFrom1C([
      'ligron_number' => $testNumber,
      'client' => self::TEST_INN,
      'salon' => self::TEST_SALON_CODE,
      'name' => 'Update Test',
      'origin_type' => 1,
      'status_code' => self::STATUS_NEW, // Исходный статус
      'status_date' => '01.01.2025',
      'date' => '01.01.2025',
      'percent_payment' => 0
    ]);

    // 2. Готовим данные для обновления (Смена статуса + Оплата)
    $updateData = [
      'ligron_number' => $testNumber,
      'status_code' => self::STATUS_WORK, // Новый статус
      'status_date' => '02.01.2025 12:00:00',
      'percent_payment' => 100,
      'production_time' => 20
    ];

    // 3. Выполняем обновление
    $updatedOrder = $service->updateOrderFrom1C($updateData);

    // 4. Проверки
    $this->assertEquals(self::STATUS_WORK, $updatedOrder['status_code'], "Статус не обновился");
    $this->assertEquals(100, $updatedOrder['percent_payment'], "Оплата не обновилась");
    $this->assertEquals(20, $updatedOrder['production_time'], "Срок не обновился");

    // Проверяем историю (должно стать 2 записи)
    $this->assertEquals(2, count($updatedOrder['status_history']), "В истории должно быть 2 записи");
    // Новая запись должна быть первой
    $this->assertEquals(self::STATUS_WORK, $updatedOrder['status_history'][0]['code']);
  }

  /**
   * Тест 4: Обновление несуществующего заказа (Ошибка)
   * @throws \Exception
   */
  public function testUpdateOrder_NotFound(): void
  {
    $service = new Webhook1cOrderService();

    $updateData = [
      'ligron_number' => 'NON_EXISTENT_' . time(),
      'status_code' => self::STATUS_NEW
    ];

    try {
      $service->updateOrderFrom1C($updateData);
      $this->assertTrue(false, "Должна быть ошибка, если заказ не найден");
    } catch (\RuntimeException $e) {
      // Ожидаем просто ошибку (код может быть 0 или 400, главное что упало)
      $this->assertNotNull($e->getMessage());
    }
  }
}