<?php
try {
    $statuses = \OrderApi\DB\Repositories\OrderStatusRepository::getAll();
} catch (\Throwable $e) {
    $statuses = [];
}
?>

<div class="api-doc-container">
    <h1>Справочник статусов</h1>
    <p>Список всех возможных статусов заказа с их кодами и цветовым обозначением (актуальные данные из БД).</p>

    <div class="api-endpoint">
        <span class="method get">GET</span>
        <span class="url">/local/api-e-order/statuses</span>
    </div>

    <!-- Уведомление об авторизации -->
    <div class="security-note">
        🔒 <strong>Требуется авторизация.</strong><br>
        Необходимо передать заголовок <code>X-Auth-Token</code>.<br>
        <a href="auth">Подробнее об авторизации &rarr;</a>
    </div>

    <table class="status-table">
        <thead>
        <tr>
            <th width="15%">Код (Code)</th>
            <th width="50%">Название</th>
            <th width="35%">Цвет</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($statuses)): ?>
            <?php foreach ($statuses as $status): ?>
                <tr>
                    <td>
                        <span class="code-pill"><?= htmlspecialchars((string)$status['code']) ?></span>
                    </td>
                    <td><?= htmlspecialchars($status['name']) ?></td>
                    <td>
                        <div class="color-cell">
                            <div class="color-swatch" style="background-color: <?= htmlspecialchars($status['color']) ?>;"></div>
                            <span class="color-hex"><?= htmlspecialchars($status['color']) ?></span>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="3" style="text-align: center; color: #999; padding: 20px;">
                    Нет данных или ошибка подключения к БД
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <details>
        <summary>Пример живого ответа (JSON)</summary>
        <pre><?= json_encode([
                    "status" => "success",
                    "message" => "Order statuses",
                    "data" => $statuses
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
    </details>

</div>