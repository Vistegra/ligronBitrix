<div class="api-doc-container">
    <h1>Получение списка заказов</h1>
    <p>Метод возвращает список заказов с поддержкой пагинации и сложной фильтрации.</p>

    <div class="api-endpoint">
        <span class="method get">GET</span>
        <span class="url">/local/api-e-order/orders</span>
    </div>

    <div class="security-note">
        🔒 <strong>Требуется авторизация.</strong><br>
        Необходимо передать заголовок <code>X-Auth-Token</code>.<br>
        <a href="auth">Подробнее об авторизации &rarr;</a>
    </div>

    <h2>Параметры запроса (Query Params)</h2>

    <table class="param-table">
        <thead>
        <tr>
            <th>Параметр</th>
            <th>Тип</th>
            <th>Описание</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><code>limit</code></td>
            <td>int</td>
            <td>Количество записей на странице (по умолчанию 20).</td>
        </tr>
        <tr>
            <td><code>offset</code></td>
            <td>int</td>
            <td>Смещение для пагинации (по умолчанию 0).</td>
        </tr>
        <tr>
            <td><code>is_draft</code></td>
            <td>0 | 1</td>
            <td>
                <code>1</code> — получить только черновики.<br>
                <code>0</code> — получить только отправленные заказы (по умолчанию).
            </td>
        </tr>
        <tr>
            <td><code>filter</code></td>
            <td>string</td>
            <td>Строка фильтрации (см. синтаксис ниже).</td>
        </tr>
        </tbody>
    </table>

    <div class="header-block">
        <h3>🔍 Синтаксис фильтрации</h3>
        <p>Параметр <code>filter</code> принимает строку в специальном формате:</p>
        <ul>
            <li>Пары <code>ключ=значение</code> разделяются точкой с запятой <code>;</code>.</li>
            <li>Множественные значения перечисляются через запятую <code>,</code> (работает как оператор OR).</li>
        </ul>
        <p><strong>Пример строки:</strong> <code>status_id=1,2;dealer_user_id=5</code></p>
    </div>

    <h3>Доступные поля для фильтрации</h3>
    <table class="param-table">
        <thead>
        <tr>
            <th>Ключ фильтра</th>
            <th>Описание</th>
            <th>Пример</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><code>status_id</code></td>
            <td>ID статуса (или список ID через запятую).<br><a href="statuses">См. справочник статусов</a></td>
            <td><code>status_id=4</code><br><code>status_id=1,2</code></td>
        </tr>
        <tr>
            <td><code>dealer_user_id</code></td>
            <td>ID сотрудника дилера. Позволяет найти заказы конкретного сотрудника.</td>
            <td><code>dealer_user_id=15</code></td>
        </tr>
        <tr>
            <td><code>dealer_prefix</code></td>
            <td>
                <strong>Только для менеджеров Ligron.</strong><br>
                Фильтрация по префиксу дилера (например, <code>pro_</code>).
                <br><em>Дилеры видят только свой префикс автоматически.</em>
            </td>
            <td><code>dealer_prefix=dea_</code></td>
        </tr>
        </tbody>
    </table>

    <h2>Пример запроса (cURL)</h2>
    <details>
        <summary>Показать пример</summary>
        <pre class="response-content">
curl --location 'https://ligron.ru/local/api-e-order/orders?limit=10&filter=status_id=4,5;dealer_user_id=3' \
--header 'X-Auth-Token: ВАШ_ТОКЕН'
</pre>
    </details>

    <h2>Варианты ответов</h2>

    <h3>1. Успешный запрос (200 OK)</h3>
    <div class="status-badge status-success">HTTP 200 OK</div>
    <p>Возвращает массив заказов и информацию о пагинации.</p>

    <details>
        <summary>Пример ответа</summary>
        <pre class="response-content">
{
    "status": "success",
    "message": "Orders list",
    "data": {
        "orders": [
            {
                "id": 65,
                "number": "72525161",
                "name": "Заказ 1",
                "status_id": 4,
                "status_code": "104",
                "status_name": "Оплачен",
                "status_color": "#9ACD32",
                "dealer_prefix": "pro_",
                "created_at": 1764673858,
                "updated_at": 1764838541
            },
            {
                "id": 64,
                "number": "72525160",
                "name": "Заказ 2",
                "status_id": 1,
                "status_code": "101",
                "status_name": "Оформляется",
                "status_color": "#FFD700",
                "created_at": 1764670000,
                "updated_at": 1764670000
            }
        ],
        "pagination": {
            "limit": 10,
            "offset": 0,
            "total": 45
        }
    }
}
</pre>
    </details>

</div>