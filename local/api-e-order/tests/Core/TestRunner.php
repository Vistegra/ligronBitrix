<?php

namespace Tests\Core;

use Bitrix\Main\Application;

class TestRunner
{
  private array $tests = [];
  // Список БД для открытия транзакций
  private array $connections = ['default', 'calc'];

  /**
   * @param bool $useTransactions Если true - изменения откатятся. Если false - запишутся в БД.
   */
  public function __construct(
    private bool $useTransactions = true
  )
  {
  }

  public function addTest(string $className): void
  {
    $this->tests[] = $className;
  }

  public function run(): void
  {
    $passed = 0;
    $failed = 0;
    $activeTransactions = [];

    try {
      // 1. Управление транзакциями
      if ($this->useTransactions) {
        // Безопасный режим
        foreach ($this->connections as $name) {
          Application::getConnection($name)->startTransaction();
          $activeTransactions[] = $name;
        }

        echo "<div class='mb-6 p-4 bg-blue-950 border border-blue-800 rounded-lg flex items-center gap-3'>
                        <div class='text-2xl'>🛡️</div>
                        <div>
                            <div class='font-bold text-blue-200'>Безопасный режим активен</div>
                            <div class='text-xs text-blue-400'>Транзакции открыты для: " . implode(', ', $activeTransactions) . ". Изменения будут отменены.</div>
                        </div>
                      </div>";
      } else {
        // Опасный режим
        echo "<div class='mb-6 p-4 bg-red-950/50 border border-red-600 rounded-lg flex items-center gap-3 animate-pulse'>
                        <div class='text-2xl'>⚠️</div>
                        <div>
                            <div class='font-bold text-red-200'>РЕЖИМ ЗАПИСИ (REAL DATA)</div>
                            <div class='text-xs text-red-300'>Транзакции ОТКЛЮЧЕНЫ. Все созданные данные <b>ОСТАНУТСЯ</b> в базе данных.</div>
                        </div>
                      </div>";
      }

      // 2. Запуск тестов
      echo "<div class='space-y-4'>";
      foreach ($this->tests as $class) {
        $this->runClass($class, $passed, $failed);
      }
      echo "</div>";

    } catch (\Throwable $e) {
      echo "<div class='p-4 bg-red-900 text-white font-bold'>RUNNER ERROR: " . $e->getMessage() . "</div>";
    } finally {
      // 3. Откат (Только если транзакции были открыты)
      if (!empty($activeTransactions)) {
        foreach (array_reverse($activeTransactions) as $name) {
          try {
            Application::getConnection($name)->rollbackTransaction();
          } catch (\Throwable $e) {
          }
        }
        echo "<div class='mt-8 text-center text-xs text-slate-500 uppercase tracking-widest'>Изменения откачены</div>";
      } elseif (!$this->useTransactions) {
        echo "<div class='mt-8 text-center text-xs text-red-500 uppercase tracking-widest font-bold'>Изменения, ВНЕСЕННЫЕ в базу данных</div>";
      }
    }

    $color = $failed === 0 ? 'text-green-400' : 'text-red-400';
    echo "<div class='mt-6 pt-4 border-t border-slate-700 text-xl font-bold $color'>
                Итог: $passed успешно, $failed ошибок
              </div>";
  }

  /**
   * @throws \ReflectionException
   */
  private function runClass(string $className, int &$passed, int &$failed): void
  {
    $shortName = (new \ReflectionClass($className))->getShortName();
    echo "<div class='bg-slate-800 rounded border border-slate-700 overflow-hidden'>";
    echo "<div class='bg-slate-900/50 px-4 py-2 border-b border-slate-700 font-bold text-yellow-500'>$shortName</div>";
    echo "<div class='p-4 space-y-2'>";

    try {
      if (!class_exists($className)) throw new \Exception("Class not found");
      $obj = new $className();

      foreach (get_class_methods($obj) as $method) {
        if (!str_starts_with($method, 'test')) continue;

        try {
          $obj->setUp();
          $obj->$method();
          $obj->tearDown();
          echo "<div class='flex items-center gap-2 text-sm'><span class='text-green-500'>✔</span> <span class='text-slate-300'>$method</span></div>";
          $passed++;
        } catch (\Throwable $e) {
          echo "<div class='bg-red-900/20 p-2 rounded text-sm'>
                            <div class='font-bold text-red-400'>✖ $method</div>
                            <div class='text-xs text-red-300 pl-4 mt-1'>" . $e->getMessage() . "</div>
                            <div class='text-xs text-slate-500 pl-4'>line: " . $e->getLine() . "</div>
                          </div>";
          $failed++;
        }
      }
    } catch (\Throwable $e) {
      echo "<div class='text-red-500'>Class Init Error: " . $e->getMessage() . "</div>";
    }
    echo "</div></div>";
  }
}