<div class="api-doc-container">
    <h1>Управление кэшем (Cache)</h1>

    <div class="note" style="font-size: 1.05em; line-height: 1.7;">
        <strong>Как работает система:</strong><br>
        Чтобы личный кабинет работал молниеносно и не перегружал базу данных, система <b>запоминает
            (кэширует)</b> редко меняющиеся данные. Например: структуру дилеров, привязку салонов к менеджерам, названия
        и цвета статусов.<br><br>
        <strong>Зачем сбрасывать кэш?</strong><br>
        Если администратор в панели управления сайтом добавил нового дилера, создал новый салон или изменил цвет
        статуса, эти изменения не появятся в Личном кабинете мгновенно. Система "вспомнит" о них только когда время кэширования истечет.<br><br>
        Чтобы применить изменения <b>прямо сейчас</b>, достаточно сбросить кэш по специальной ссылке.
    </div>

    <div class="api-endpoint">
        <span class="method get">GET</span>
        <span class="url"><?= $appPath ?>/tools/cache/clear?type=...</span>
    </div>

    <h2>Как использовать</h2>
    <p>Cкопируйте нужную ссылку, вставьте её в адресную строку браузера и нажмите Enter. Вы увидите сообщение:
        <code>{"status":"success","message":"Кэш успешно очищен"}</code>.</p>

    <h2>Варианты сброса (Опции)</h2>

    <table class="param-table">
        <thead>
        <tr>
            <th width="20%">Тип сброса (type)</th>
            <th width="45%">Когда применять?</th>
            <th width="35%">Готовая ссылка</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><code>hierarchy</code></td>
            <td>
                <b>Обновление структуры доступов:</b><br>
                - Добавили нового дилера или салон.<br>
                - Привязали менеджера Лигрон к новому дилеру.<br>
                - Изменили ИНН у дилера.<br>
                - Менеджер ушел в отпуск (сработала подмена).
            </td>
            <td>
                <a href="<?= $appPath ?>/tools/cache/clear?type=hierarchy" target="_blank">
                    Сбросить иерархию &rarr;
                </a>
            </td>
        </tr>
        <tr>
            <td><code>statuses</code></td>
            <td>
                <b>Обновление статусов заказов:</b><br>
                - Добавили новый статус в базу.<br>
                - Изменили название статуса (например, с "Оплачен" на "Ждет оплаты").<br>
                - Поменяли цветовое обозначение (hex) статуса.
            </td>
            <td>
                <a href="<?= $appPath ?>/tools/cache/clear?type=statuses" target="_blank">
                    Сбросить статусы &rarr;
                </a>
            </td>
        </tr>
        <tr>
            <td><code>all</code> <br><span style="font-size: 0.8em; color: #888;">(по умолчанию)</span></td>
            <td>
                <b>Полный сброс:</b><br>
                Очищает абсолютно все сохраненные настройки API V2. Используйте, если сомневаетесь, какой именно кэш
                нужно сбросить.
            </td>
            <td>
                <a href="<?= $appPath ?>/tools/cache/clear?type=all" target="_blank">
                    Сбросить ВСЁ &rarr;
                </a>
            </td>
        </tr>
        </tbody>
    </table>

    <div class="security-note" style="background: #e2e3e5; border-color: #d6d8db; color: #383d41; margin-top: 20px;">
        <strong>Что произойдет у пользователей?</strong><br>
        Сброс кэша происходит "бесшовно". Никого не выбросит из личного кабинета. При следующем клике или обновлении
        страницы пользователи просто увидят самые свежие данные из базы.
    </div>

    <!-- ТЕХНИЧЕСКАЯ ИНФОРМАЦИЯ -->
    <h2>Техническая информация (TTL)</h2>
    <p>Текущие системные настройки времени жизни кэша (Time To Live), по истечении которого система обновит данные автоматически без ручного сброса.</p>

    <div style="background-color: #f8f9fa; border: 1px solid #e9ecef; border-left: 4px solid #17a2b8; padding: 15px; border-radius: 4px;">
        <ul style="margin: 0; padding-left: 20px; line-height: 1.8;">
            <li>Для прав доступа и иерархии (<code>hierarchy</code>):
                <strong style="color: #17a2b8; font-family: monospace; font-size: 1.1em; background: #fff; padding: 2px 6px; border: 1px solid #ccc; border-radius: 4px;">
                    <?= $ttlHierarchy ?> сек.
                </strong>
                <span style="color: #666; font-size: 0.9em;">(≈ <?= round($ttlHierarchy / 3600, 1) ?> час.)</span>
            </li>
            <li>Для статусов (<code>statuses</code>):
                <strong style="color: #17a2b8; font-family: monospace; font-size: 1.1em; background: #fff; padding: 2px 6px; border: 1px solid #ccc; border-radius: 4px;">
                    <?= $ttlStatuses ?> сек.
                </strong>
                <span style="color: #666; font-size: 0.9em;">(≈ <?= round($ttlStatuses / 3600, 1) ?> час.)</span>
            </li>
        </ul>
    </div>

</div>