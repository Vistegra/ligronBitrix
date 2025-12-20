<?php
namespace Tests\Cases;
use Tests\Core\TestCase;
use OrderApi\Services\Order\Webhook1cOrderService;

class WebhookServiceTest extends TestCase {
  public function testCreateOrder() {
    $service = new Webhook1cOrderService();
    $num = 'TEST_AUTO_' . time();

    // Создаем заказ (он запишется в транзакцию)
    $order = $service->createOrderFrom1C([
      'ligron_number' => $num,
      'client' => '7701234567', // Реальный ИНН
      'salon' => '017587980',   // Реальный Код салона
      'status_code' => 'NEW'
    ]);

    $this->assertNotNull($order['id']);
    $this->assertEquals($num, $order['number']);

    // Тут можно проверить, что он реально есть в базе
    // $dbOrder = ...Repository::getById($order['id']);
    // $this->assertEquals($num, $dbOrder['number']);
  }

  public function testDuplicateThrowsError() {
    $service = new Webhook1cOrderService();
    $data = ['ligron_number' => 'DUP_' . time(), 'client' => '7701234567'];

    $service->createOrderFrom1C($data);

    try {
      $service->createOrderFrom1C($data);
      $this->assertTrue(false, "Должно быть исключение 409");
    } catch (\RuntimeException $e) {
      $this->assertEquals(409, $e->getCode());
    }
  }
}