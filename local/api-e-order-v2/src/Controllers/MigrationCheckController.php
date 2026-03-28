<?php

declare(strict_types=1);

namespace OrderApiV2\Controllers;

use OrderApiV2\Config\ApiConfig;
use OrderApiV2\Services\Migration\MigrationAnalyzerService;
use OrderApiV2\Services\Migration\OrderMigrationAnalyzerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class MigrationCheckController extends AbstractController
{
  public function dictionaries(ServerRequestInterface $request): ResponseInterface
  {
    $analyzer = new MigrationAnalyzerService();
    $report = $analyzer->analyze();

    $html = $this->renderHtmlHead('Анализ Справочников (Calc -> WebCalc)');
    $s = $report['stats'];
    $html .= '<div class="card"><h2>Статистика по активным записям</h2>
            <div class="stats">
                <div class="stat-box">Дилеры<br><strong>V1: ' . $s['old_dealers'] . ' | V2: ' . $s['new_dealers'] . '</strong></div>
                <div class="stat-box">Салоны<br><strong>V1: ' . $s['old_salons'] . ' | V2: ' . $s['new_salons'] . '</strong></div>
                <div class="stat-box">Пользователи<br><strong>V1: ' . $s['old_users'] . ' | V2: ' . $s['new_users'] . '</strong></div>
            </div>
        </div>';

    $e = $report['errors'];
    $html .= $this->renderTable('1. Отсутствуют Дилеры в V2 (по ИНН)', $e['missing_dealers']);
    $html .= $this->renderTable('2. Отсутствуют Салоны в V2 (по Коду)', $e['missing_salons']);
    $html .= $this->renderTable('3. Отсутствуют Пользователи в V2 (по Логину)', $e['missing_users']);
    $html .= $this->renderTable('4. Нет связи Дилер <-> Салон в V2', $e['missing_dealer_salon_link']);
    $html .= $this->renderTable('5. Кривые данные в V1', $e['old_db_bad_data']);

    $html .= '</body></html>';
    return $this->htmlResponse($html);
  }

  /**
   * Отчет по Заказам: Анализ сопоставления и выполнение миграции
   */
  /**
   * Отчет по Заказам: Анализ и Миграция
   */
  public function orders(ServerRequestInterface $request): ResponseInterface
  {
    $queryParams = $request->getQueryParams();
    $isUpdateAction = ($queryParams['is_update_salons_and_dealers'] ?? '0') === '1';

    $analyzer = new OrderMigrationAnalyzerService();
    $migrationReport = $isUpdateAction ? $analyzer->migrate() : null;

    $results = $analyzer->analyze();

    // Расчет статистики
    $total = count($results);
    $alreadyInBase = 0;
    $readyToMigrate = 0;
    $errors = 0;

    foreach ($results as $res) {
      if ($res['is_already_filled']) {
        $alreadyInBase++;
      } elseif ($res['found_in_v1'] && $res['exists_in_v2']) {
        $readyToMigrate++;
      } else {
        $errors++;
      }
    }

    $html = $this->renderHtmlHead('Миграция Заказов: V1 (Префиксы) -> V2 (ИНН)');

    if ($migrationReport) {
      $html .= '<div class="card" style="border-left: 5px solid #27ae60; background: #f0fff4;">
                <h3>Результат выполнения миграции:</h3>
                <p>Всего в очереди: <b>' . $migrationReport['total'] . '</b> | 
                   Успешно обновлено: <b style="color:green">' . $migrationReport['updated'] . '</b> | 
                   Уже было заполнено: <b>' . $migrationReport['already_filled'] . '</b> | 
                   Пропущено (ошибки): <b style="color:red">' . $migrationReport['skipped'] . '</b></p>
                </div>';
    }

    $html .= '<div class="card">
            <div class="stats">
                <div class="stat-box">Всего заказов<br><strong>' . $total . '</strong></div>
                <div class="stat-box" style="border-color:#3498db">Уже в базе V2<br><strong style="color:#3498db">' . $alreadyInBase . '</strong></div>
                <div class="stat-box" style="border-color:#27ae60">Готовы к миграции<br><strong style="color:#27ae60">' . $readyToMigrate . '</strong></div>
                <div class="stat-box" style="border-color:#e74c3c">Кривые данные V1<br><strong style="color:#e74c3c">' . $errors . '</strong></div>
                
                <div class="stat-box" style="background:#fdf2e9; border: 1px dashed #e67e22;">
                    <form method="GET">
                        <input type="hidden" name="is_update_salons_and_dealers" value="1">
                        <button type="submit" style="width:100%; padding:10px; background:#e67e22; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">
                            ЗАПУСТИТЬ МИГРАЦИЮ ПУСТЫХ ПОЛЕЙ
                        </button>
                    </form>
                </div>
            </div>
        </div>';

    $html .= '<div class="card">
            <h2>Реестр сопоставления</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Номер</th>
                        <th>V1 Префикс</th>
                        <th>Текущие V2 (ИНН / Салон)</th>
                        <th>Целевые V2 (ИНН / Салон)</th>
                        <th>Статус</th>
                        <th>Комментарий</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($results as $r) {
      $rowClass = $r['is_already_filled'] ? 'status-ok' : ($r['found_in_v1'] && $r['exists_in_v2'] ? 'status-warn' : 'status-err');
      $statusLabel = $r['is_already_filled'] ? '✅ В БАЗЕ' : ($r['found_in_v1'] && $r['exists_in_v2'] ? '⏳ ОЖИДАЕТ' : '❌ ОШИБКА');

      $html .= "<tr class='{$rowClass}'>
                <td>{$r['order_id']}</td>
                <td><b>{$r['order_number']}</b></td>
                <td><code>{$r['v1_prefix']}</code></td>
                <td style='font-size:11px; color:#666'>
                    " . ($r['current_inn'] ?: '—') . " / " . ($r['current_salon'] ?: '—') . "
                </td>
                <td>
                    " . ($r['old_inn'] ?: '<span style="color:red">?</span>') . " / 
                    " . ($r['old_salon_code'] ?: '<span style="color:red">?</span>') . "
                </td>
                <td style='font-weight:bold; font-size:11px;'>{$statusLabel}</td>
                <td style='font-size:11px'>" . ($r['error'] ?: '<span style="color:green">OK</span>') . "</td>
            </tr>";
    }

    $html .= '</tbody></table></div></body></html>';
    return $this->htmlResponse($html);
  }

  private function renderHtmlHead(string $title): string
  {
    $base = ApiConfig::APP_PATH;
    $html = '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><title>' . $title . '</title>';
    $html .= '<style>
            body { font-family: system-ui, sans-serif; padding: 20px; background: #f4f7f6; color: #333; font-size: 13px; }
            .nav { margin-bottom: 20px; display: flex; gap: 10px; }
            .nav a { padding: 8px 16px; background: #2c3e50; color: white; text-decoration: none; border-radius: 4px; }
            .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
            .stats { display: flex; gap: 15px; }
            .stat-box { padding: 15px; border-radius: 6px; background: #fff; border: 1px solid #ddd; flex: 1; text-align: center; }
            .stat-box strong { display: block; font-size: 1.8em; }
            table { width: 100%; border-collapse: collapse; background: white; }
            th { background: #f8f9fa; position: sticky; top: 0; padding: 10px; border-bottom: 2px solid #dee2e6; text-align: left; }
            td { padding: 8px 10px; border-bottom: 1px solid #eee; }
            .status-ok { background-color: #effff4; }
            .status-warn { background-color: #fffdec; }
            .status-err { background-color: #fff5f5; }
            .error-row { color: #d63031; font-weight: bold; }
        </style></head><body>';

    $html .= '<div class="nav">
            <a href="' . $base . '/tools/migration/dictionaries">Справочники</a>
            <a href="' . $base . '/tools/migration/orders">Заказы (Детально)</a>
        </div>';
    $html .= "<h1>{$title}</h1>";
    return $html;
  }

  private function renderTable(string $title, array $errors): string
  {
    $out = "<div class='card'><h3>{$title} (" . count($errors) . ")</h3>";
    if (empty($errors)) {
      $out .= "<p style='color:green'><b>✓ Конфликтов не обнаружено.</b></p>";
    } else {
      $out .= "<table><tbody>";
      foreach ($errors as $error) {
        $out .= "<tr><td class='error-row'>{$error}</td></tr>";
      }
      $out .= "</tbody></table>";
    }
    $out .= "</div>";
    return $out;
  }

  private function htmlResponse(string $html): ResponseInterface
  {
    $response = new Response();
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
  }

  public function hierarchy(ServerRequestInterface $request): ResponseInterface
  {
    $service = new \OrderApiV2\Services\Migration\HierarchyCheckService();

    if (($request->getQueryParams()['refresh'] ?? null) === '1') {
      $service->clearCache('full_hierarchy_audit_v2', '/order_api_v2/audit');
    }

    $html = $this->renderHtmlHead('Аудит иерархии: Дилеры -> Салоны -> Пользователи');

    // Стили для удобного чтения
    $html .= '<style>
        .audit-container { font-size: 14px; color: #1a202c; line-height: 1.5; }
        .dealer-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .dealer-summary { padding: 12px; background: #f7fafc; cursor: pointer; font-weight: 600; border-radius: 8px; display: flex; align-items: center; }
        .dealer-summary:hover { background: #edf2f7; }
        
        .salon-wrapper { padding: 10px 10px 10px 30px; border-left: 2px dashed #cbd5e0; margin-left: 20px; }
        .salon-card { border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 8px; }
        .salon-card.warning { border-color: #feb2b2; background: #fff5f5; }
        .salon-summary { padding: 8px; cursor: pointer; display: flex; align-items: center; gap: 10px; font-size: 0.95em; }
        
        .badge { font-size: 11px; padding: 2px 8px; border-radius: 12px; margin-left: 10px; }
        .badge.inn { background: #ebf8ff; color: #2b6cb0; }
        .badge.count { background: #e2e8f0; color: #4a5568; }
        
        .user-table { width: 100%; border-collapse: collapse; background: #fff; font-size: 12px; margin-top: 5px; }
        .user-table th { background: #f8fafc; text-align: left; padding: 8px; border-bottom: 2px solid #edf2f7; color: #718096; text-transform: uppercase; letter-spacing: 0.05em; }
        .user-table td { padding: 8px; border-bottom: 1px solid #edf2f7; }
        
        .role-tag { background: #f0fff4; color: #276749; padding: 1px 6px; border-radius: 4px; font-weight: bold; border: 1px solid #c6f6d5; }
        .empty-alert { color: #c53030; padding: 10px; font-weight: 500; font-size: 12px; }
        .code { color: #a0aec0; font-family: monospace; }
        
        /* Скрываем стандартный маркер */
        summary { list-style: none; }
        summary::-webkit-details-marker { display: none; }
    </style>';

    $html .= '<div style="margin-bottom: 20px; background:#fff; padding:15px; border-radius:8px; border: 1px solid #e2e8f0;">
                <strong>💡 Памятка:</strong> Один и тот же салон может отображаться под разными дилерами. 
                Пользователи отображаются во всех салонах, к коду которых они привязаны.
                <a href="?refresh=1" style="float:right; color:#3182ce;">🔄 Обновить данные</a>
              </div>';

    $html .= $service->renderHtml();
    $html .= '</body></html>';

    return $this->htmlResponse($html);
  }

  public function hierarchyCheck(ServerRequestInterface $request): ResponseInterface
  {
    $service = new \OrderApiV2\Services\Migration\SecurityAuditService();
    $tree = $service->runFullAudit();

    $html = $this->renderHtmlHead('Аудит иерархии и Безопасности заказов');

    $html .= '<style>
        .dealer-box { margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .dealer-header { background: #f8fafc; padding: 10px 15px; border-bottom: 1px solid #e2e8f0; cursor: pointer; }
        .salon-box { margin: 10px 10px 10px 30px; border: 1px solid #edf2f7; border-radius: 6px; }
        .user-row { display: flex; align-items: center; justify-content: space-between; padding: 8px 15px; border-bottom: 1px solid #f7fafc; font-size: 13px; }
        .user-row:last-child { border-bottom: none; }
        .status-badge { padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .status-secure { background: #c6f6d5; color: #22543d; }
        .status-danger { background: #fed7d7; color: #822727; animation: blink 1s infinite; }
        .audit-info { font-family: monospace; font-size: 11px; color: #718096; }
        @keyframes blink { 50% { opacity: 0.5; } }
    </style>';

    foreach ($tree as $dealer) {
      $html .= "<div class='dealer-box'><details>";
      $html .= "<summary class='dealer-header'>🏢 <strong>{$dealer['name']}</strong> <small>ИНН: {$dealer['inn']}</small></summary>";

      foreach ($dealer['salons'] as $salon) {
        $html .= "<div class='salon-box'><details>";
        $html .= "<summary style='padding:8px; cursor:pointer;'>🏠 {$salon['name']} ({$salon['code']})</summary>";

        foreach ($salon['users'] as $u) {
          $st = $u['audit'];
          $class = $st['status'] === 'secure' ? 'status-secure' : 'status-danger';

          $html .= "<div class='user-row'>";
          $html .= "<span>👤 <strong>{$u['name']}</strong> <small>@{$u['username']}</small> <span class='status-badge {$class}'>{$st['status']}</span></span>";
          $html .= "<span class='audit-info'>Заказов: {$st['visible_orders']} | Утечек: {$st['leaks']} | Облако: {$st['allowed_inns_count']} ИНН / {$st['allowed_salons_count']} Сал.</span>";
          $html .= "</div>";
        }
        $html .= "</details></div>";
      }
      $html .= "</details></div>";
    }

    return $this->htmlResponse($html);
  }
}