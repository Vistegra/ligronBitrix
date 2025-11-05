<?php

declare(strict_types=1);

namespace OrderApi\DB\Events;

use Bitrix\Main\Event;
use Bitrix\Main\EventManager;

/**
 * События для заказов
 *
 * Использование:
 *   OrderEvent::onCreated($orderId, $orderData);
 *   OrderEvent::onStatusChanged($orderId, $oldStatusCode, $newStatusCode, $orderData);
 */
final class OrderEvent
{
  // === Имена событий ===
  private const string EVENT_ORDER_CREATED       = 'OnOrderApiCreated';
  private const string EVENT_ORDER_STATUS_CHANGED = 'OnOrderApiStatusChanged';
  private const string MODULE_ID = 'order.api';

  /**
   * Событие: заказ создан
   */
  public static function onCreated(int $orderId, array $orderData): void
  {
    $event = new Event(
      self::MODULE_ID,
      self::EVENT_ORDER_CREATED,
      [
        'ORDER_ID' => $orderId,
        'ORDER'    => $orderData,
      ]
    );
    $event->send();
  }

  /**
   * Событие: статус заказа изменён
   */
  public static function onStatusChanged(
    int $orderId,
    string $oldStatusCode,
    string $newStatusCode,
    array $orderData
  ): void {
    $event = new Event(
      self::MODULE_ID,
      self::EVENT_ORDER_STATUS_CHANGED,
      [
        'ORDER_ID'        => $orderId,
        'OLD_STATUS_CODE' => $oldStatusCode,
        'NEW_STATUS_CODE' => $newStatusCode,
        'ORDER'           => $orderData,
      ]
    );
    $event->send();
  }

  /**
   * Подписаться на событие создания заказа
   */
  public static function addOnCreatedHandler(callable $handler, ?string $module = null): void
  {
    EventManager::getInstance()->addEventHandler(
      self::MODULE_ID,
      self::EVENT_ORDER_CREATED,
      $handler,
      false,
      $module
    );
  }

  /**
   * Подписаться на смену статуса
   */
  public static function addOnStatusChangedHandler(callable $handler, ?string $module = null): void
  {
    EventManager::getInstance()->addEventHandler(
      self::MODULE_ID,
      self::EVENT_ORDER_STATUS_CHANGED,
      $handler,
      false,
      $module
    );
  }

}