<?php

namespace Tests\Core;

abstract class TestCase
{
  public function setUp(): void {}
  public function tearDown(): void {}

  /**
   * @throws \Exception
   */
  protected function assertTrue(mixed $condition, string $message = ''): void {
    if ($condition !== true) $this->fail($message ?: "Failed asserting that value is true.");
  }

  /**
   * @throws \Exception
   */
  protected function assertFalse(mixed $condition, string $message = ''): void {
    if ($condition !== false) $this->fail($message ?: "Failed asserting that value is false.");
  }

  /**
   * @throws \Exception
   */
  protected function assertEquals(mixed $expected, mixed $actual, string $message = ''): void {
    if ($expected != $actual) {
      $expStr = is_array($expected) ? 'Array(...)' : (string)$expected;
      $actStr = is_array($actual) ? 'Array(...)' : (string)$actual;
      $this->fail($message ?: "Expected: $expStr, Got: $actStr");
    }
  }

  /**
   * @throws \Exception
   */
  protected function assertNull(mixed $actual, string $message = ''): void {
    if ($actual !== null) {
      $type = gettype($actual);
      $this->fail($message ?: "Failed asserting that value is null. Got: $type");
    }
  }

  /**
   * @throws \Exception
   */
  protected function assertNotNull(mixed $actual, string $message = ''): void {
    if ($actual === null) $this->fail($message ?: "Failed asserting that value is not null.");
  }

  /**
   * @throws \Exception
   */
  protected function assertIsArray(mixed $actual, string $message = ''): void {
    if (!is_array($actual)) $this->fail($message ?: "Failed asserting that value is an array.");
  }

  // Эмуляция проверки исключений
  protected function expectException(string $exceptionClass): void {
    // В простой реализации мы не можем это проверить декларативно,
    // поэтому в тестах используйте try-catch блок.
  }

  protected function fail(string $message): void {
    throw new \Exception($message);
  }
}