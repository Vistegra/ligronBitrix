<?php
// Файл: /local/api-e-order/run_tests.php

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/tests/autoload.php';

use Tests\Core\TestRunner;

global $USER;
if (!$USER->IsAdmin()) {
    header('HTTP/1.0 403 Forbidden');
    die('Access Denied: Administrators only.');
}

$suites = [
        /*'all' => [
                'title' => '🚀 Запустить всё',
                'classes' => [
                        \Tests\Cases\DealerRepositoryTest::class,
                        \Tests\Cases\WebhookServiceTest::class,
                ]
        ],*/
        'repo' => [
                'title' => '📦 Репозитории',
                'classes' => [
                        \Tests\Cases\DealerRepositoryTest::class,
                        \Tests\Cases\WebUserRepositoryTest::class,
                ]
        ],
        'service' => [
                'title' => '⚙️ Сервисы',
                'classes' => [
                        \Tests\Cases\WebhookServiceTest::class,
                ]
        ],
];

$currentSuiteKey = $_GET['suite'] ?? null;
$output = '';

if ($currentSuiteKey && isset($suites[$currentSuiteKey])) {
    ob_start();
    try {
        $runner = new TestRunner();
        foreach ($suites[$currentSuiteKey]['classes'] as $class) {
            $runner->addTest($class);
        }
        $runner->run();
    } catch (Throwable $e) {
        echo "<div class='p-4 bg-red-900/50 border border-red-500 text-red-100 rounded-lg font-mono'>
                <strong>CRITICAL ERROR:</strong> " . $e->getMessage() .
                "</div>";
    }
    $output = ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="ru" class="dark">
<head>
    <meta charset="UTF-8">
    <title>System Tests</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { background: #0f172a; color: #e2e8f0; } ::-webkit-scrollbar { width: 8px; background: #1e293b; } ::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }</style>
</head>
<body class="flex h-screen overflow-hidden font-sans">
<!-- Меню -->
<aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col flex-shrink-0">
    <div class="p-6 border-b border-slate-800">
        <h1 class="font-bold text-xl text-white tracking-tight">🧪 Test Runner</h1>
        <div class="text-xs text-slate-500 mt-1">Safe Production Mode</div>
    </div>

    <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
        <a href="?" class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors <?= !$currentSuiteKey ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/20' : 'text-slate-400 hover:bg-slate-800 hover:text-white' ?>">
            <span>🏠</span>
            <span class="font-medium">Главная</span>
        </a>

        <div class="pt-6 pb-2 px-3 text-xs font-bold text-slate-600 uppercase tracking-wider">Наборы тестов</div>

        <?php foreach ($suites as $key => $suite): ?>
            <?php $isActive = $currentSuiteKey === $key; ?>
            <a href="?suite=<?= $key ?>"
               class="block px-3 py-2 rounded-md mb-1 transition-colors <?= $isActive ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/20' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                <div class="font-medium"><?= $suite['title'] ?></div>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>

<!-- Контент -->
<main class="flex-1 flex flex-col min-w-0 bg-slate-950">
    <div class="flex-1 overflow-auto p-8">
        <div class="max-w-5xl mx-auto">
            <?php if (!$output): ?>
                <!-- Пустое состояние -->
                <div class="flex flex-col items-center justify-center p-12 border-2 border-dashed border-slate-800 rounded-xl bg-slate-900/50 text-center">
                    <div class="text-6xl mb-6">👋</div>
                    <h2 class="text-2xl font-bold text-white mb-2">Готов к запуску</h2>
                    <p class="text-slate-400 max-w-sm mx-auto">Выберите группу тестов в меню слева для безопасного запуска в транзакции.</p>

                    <div class="mt-8 flex items-center gap-2 px-4 py-2 bg-emerald-900/30 text-emerald-400 text-sm font-medium rounded-full border border-emerald-900/50">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Safety Transaction Mode Enabled
                    </div>
                </div>
            <?php else: ?>
                <!-- Результаты -->
                <div class="flex items-center justify-between mb-6 pb-6 border-b border-slate-800">
                    <div>
                        <h2 class="text-2xl font-bold text-white tracking-tight"><?= $suites[$currentSuiteKey]['title'] ?></h2>
                        <div class="text-sm text-slate-400 mt-1 flex items-center gap-2">
                            <span>Результаты запуска</span>
                            <span class="text-slate-600">•</span>
                            <span class="font-mono text-xs opacity-70"><?= date('H:i:s') ?></span>
                        </div>
                    </div>

                    <a href="?suite=<?= $currentSuiteKey ?>&t=<?= time() ?>"
                       class="flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-sm font-medium rounded-lg border border-slate-700 transition hover:border-slate-600 active:scale-95">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Перезапустить
                    </a>
                </div>

                <div class="animate-in fade-in duration-300 slide-in-from-bottom-2">
                    <?= $output ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>