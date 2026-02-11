<?php

declare(strict_types=1);

namespace OrderApi\Controllers;

use OrderApi\Config\ApiConfig;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

final class LogViewerController extends AbstractController
{
  private const string LOG_FILE = 'webhook_1c.log'; // Имя файла лога
  private const int LINES_LIMIT = 1000; // Максимум строк для отображения (чтобы не нагружать)

  public function index(ServerRequestInterface $request): ResponseInterface
  {
    $query = $request->getQueryParams();

    // Фильтры из GET-параметров
    $level = $query['level'] ?? 'all'; // all, INFO, ERROR
    $search = trim($query['search'] ?? '');
    $fromDate = $query['from'] ?? '';
    $toDate = $query['to'] ?? '';

    // Полный путь к логу
    $logPath = __DIR__ . '/../..' . ApiConfig::APP_STORAGE_PATH . self::LOG_FILE;

    if (!file_exists($logPath)) {
      return $this->error('Лог-файл не найден: ' . self::LOG_FILE, 404);
    }

    // Чтение последних строк (эффективно для больших файлов)
    $lines = $this->readLastLines($logPath, self::LINES_LIMIT);

    // Фильтрация
    $filteredLines = [];
    foreach ($lines as $line) {
      if (empty($line)) continue;

      // Парсинг строки (пример: [2026-02-04T10:42:33.671642+03:00] webhook_1c.INFO: ...)
      if (!preg_match('/^\[([^\]]+)\] webhook_1c\.(\w+): (.*)/s', $line, $matches)) continue;

      $timestamp = $matches[1];
      $logLevel = $matches[2];
      $message = $matches[3];

      // Фильтр по уровню
      if ($level !== 'all' && $logLevel !== $level) continue;

      // Фильтр по поиску (игнор регистра)
      if ($search && stripos($message, $search) === false) continue;

      // Фильтр по дате (timestamp в ISO, преобразуем в unix)
      $logTime = strtotime($timestamp);
      if ($fromDate && $logTime < strtotime($fromDate . ' 00:00:00')) continue;
      if ($toDate && $logTime > strtotime($toDate . ' 23:59:59')) continue;

      $filteredLines[] = [
        'timestamp' => $timestamp,
        'level' => $logLevel,
        'message' => $message,
      ];
    }

    // HTML с панелью фильтров и логами
    $html = $this->renderHtml($filteredLines, $level, $search, $fromDate, $toDate);

    $response = new Response(200);
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
  }

  private function renderHtml(array $lines, string $level, string $search, string $from, string $to): string
  {
    $html = '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><title>Просмотр логов webhook_1c</title>';
    $html .= '<style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; color: #333; }
            h1 { color: #007bff; }
            form { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
            label { margin-right: 10px; font-weight: bold; }
            input, select { padding: 8px; margin-right: 10px; border: 1px solid #ddd; border-radius: 4px; }
            button { padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
            button:hover { background: #0056b3; }
            table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background: #007bff; color: white; }
            .info { color: green; }
            .error { color: red; font-weight: bold; }
            .message { white-space: pre-wrap; word-wrap: break-word; }
            .no-logs { text-align: center; padding: 20px; color: #888; }
        </style></head><body>';

    $html .= '<h1>Просмотр логов webhook_1c</h1>';

    // Панель фильтрации
    $html .= '<form method="GET">
            <label>Уровень:</label>
            <select name="level">
                <option value="all"' . ($level === 'all' ? ' selected' : '') . '>Все</option>
                <option value="INFO"' . ($level === 'INFO' ? ' selected' : '') . '>INFO</option>
                <option value="ERROR"' . ($level === 'ERROR' ? ' selected' : '') . '>ERROR</option>
            </select>
            <label>Поиск:</label>
            <input type="text" name="search" value="' . htmlspecialchars($search, ENT_QUOTES, 'UTF-8') . '" placeholder="Ключевые слова...">
            <label>С даты:</label>
            <input type="date" name="from" value="' . htmlspecialchars($from, ENT_QUOTES, 'UTF-8') . '">
            <label>По дату:</label>
            <input type="date" name="to" value="' . htmlspecialchars($to, ENT_QUOTES, 'UTF-8') . '">
            <button type="submit">Фильтровать</button>
        </form>';

    // Таблица логов
    if (empty($lines)) {
      $html .= '<p class="no-logs">Нет логов по заданным фильтрам</p>';
    } else {
      $html .= '<table>
                <thead><tr><th>Время</th><th>Уровень</th><th>Сообщение</th></tr></thead>
                <tbody>';
      foreach ($lines as $log) {
        $class = strtolower($log['level']);
        $html .= '<tr>
                    <td>' . htmlspecialchars($log['timestamp'], ENT_QUOTES, 'UTF-8') . '</td>
                    <td class="' . $class . '">' . htmlspecialchars($log['level'], ENT_QUOTES, 'UTF-8') . '</td>
                    <td class="message">' . htmlspecialchars($log['message'], ENT_QUOTES, 'UTF-8') . '</td>
                </tr>';
      }
      $html .= '</tbody></table>';
    }

    $html .= '</body></html>';

    return $html;
  }

  /**
   * Чтение последних N строк файла (эффективно)
   */
  private function readLastLines(string $file, int $lines): array
  {
    $result = [];
    $handle = fopen($file, 'r');
    if (!$handle) return [];

    fseek($handle, -1, SEEK_END);
    $pos = ftell($handle);
    $line = '';
    $lineCount = 0;

    while ($pos > 0 && $lineCount < $lines) {
      $char = fgetc($handle);
      if ($char === false) break;

      if ($char === "\n") {
        if ($line !== '') {
          $result[] = strrev($line);
          $line = '';
          $lineCount++;
        }
      } else {
        $line .= $char;
      }

      fseek($handle, --$pos);
    }

    if ($line !== '') {
      $result[] = strrev($line);
    }

    fclose($handle);
    return array_reverse($result); // Последние внизу
  }
}