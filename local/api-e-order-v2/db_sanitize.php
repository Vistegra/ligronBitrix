<?php

declare(strict_types=1);

/**
 * Скрипт санитизации данных (WebCalcNew)
 *
 * Очищает таблицы от скрытых символов (\r, \n, \t) используя модификаторы моделей.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require_once __DIR__ . '/vendor/autoload.php';

//require_once __DIR__ . '/src/DB/MssqlConnectionTrust.php';

use OrderApiV2\DB\Models\{
  DealerTable,
  SalonTable,
  DealerUserTable,
  LigronUserTable,
  DealerRoleTable,
  LigronRoleTable,
  DealerSalonTable,
  DealerLigronTable,
  FillingTable
};

// Настройки выполнения
@set_time_limit(0);
@ignore_user_abort(true);
\Bitrix\Main\Application::getConnection()->query("SET wait_timeout=3600");
/**
 * Список моделей для очистки.
 */
$modelsToProcess = [
//  'Дилеры (dealers)'                          => DealerTable::class,
//  'Салоны (salons)'                           => SalonTable::class,
//  'Роли дилеров (dealer_roles)'               => DealerRoleTable::class,
//  'Роли Лигрон (ligron_roles)'                => LigronRoleTable::class,
//  'Пользователи дилеров (dealer_users)'       => DealerUserTable::class,
//  'Пользователи Лигрон (ligron_users)'        => LigronUserTable::class,
//  'Связи Дилер-Салон (combination_dealer_salons)' => DealerSalonTable::class,
//  'Связи Дилер-Лигрон (combination_dealer_ligron)' => DealerLigronTable::class,
 // 'Замещения (filling)'                       => FillingTable::class,
];


echo "<style>
    body { font-family: 'Segoe UI', monospace; background: #121212; color: #00ff41; padding: 30px; }
    .card { background: #1e1e1e; border: 1px solid #333; padding: 20px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.5); }
    h1 { color: #fff; font-size: 20px; margin-top: 0; border-bottom: 1px solid #00ff41; padding-bottom: 10px; }
    .row { margin: 5px 0; border-bottom: 1px solid #222; padding: 5px 0; }
    .success { color: #00ff41; font-weight: bold; }
    .error { color: #ff4b4b; font-weight: bold; }
    .stat { color: #55aaff; }
</style>";

echo "<div class='card'>";
echo "<h1>System Data Sanitization Module</h1>";

foreach ($modelsToProcess as $label => $className) {
  echo "<div class='row'>Processing: <strong>$label</strong> ... ";

  try {
    $res = $className::getList(['select' => ['*']]);

    $updatedCount = 0;
    $totalCount = 0;

    while ($row = $res->fetch()) {
      $totalCount++;

      $primaryId = $row['ID'] ?? $row['id'] ?? null;

      if (!$primaryId) continue;

      $fieldsToUpdate = [];
      foreach ($row as $key => $val) {
        // Не отправляем ID в метод update
        if (strtoupper($key) === 'ID' || str_contains($key, 'RUNTIME')) {
          continue;
        }
        $fieldsToUpdate[$key] = $val;
      }

      $updateResult = $className::update($primaryId, $fieldsToUpdate);

      if ($updateResult->isSuccess()) {
        $updatedCount++;
      } else {
        $err = implode(' | ', $updateResult->getErrorMessages());
        echo "<br><span class='error'>[Error ID $primaryId]: $err</span>";
      }
    }

    echo "<span class='success'>COMPLETED</span> <span class='stat'>(Rows: $totalCount, Cleaned: $updatedCount)</span></div>";

  } catch (\Throwable $e) {
    echo "<span class='error'>FAIL: " . $e->getMessage() . "</span></div>";
  }

  // Плавная нагрузка
  usleep(20000);
}

echo "<br><h1 style='border-color: #55aaff;'>All tables are cleaned and synchronized.</h1>";
echo "</div>";