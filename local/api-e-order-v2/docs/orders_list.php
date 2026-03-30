<div class="api-doc-container">
    <h1>Получение списка заказов</h1>
    <p>Метод возвращает список заказов с поддержкой пагинации и сложной фильтрации.</p>

    <div class="api-endpoint">
        <span class="method get">GET</span>
        <span class="url"><?= $appPath ?>/orders</span>
    </div>

    <div class="security-note">
        🔒 <strong>Требуется авторизация.</strong><br>
        Необходимо передать заголовок <code>X-Auth-Token</code>.<br>
        Система автоматически применяет политики безопасности (пользователь видит только те заказы, к которым у него
        есть доступ).<br>
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
        <tr>
            <td><code>search</code></td>
            <td>string</td>
            <td>Поиск по названию заказа или его номеру (Ligron number).</td>
        </tr>
        <tr>
            <td><code>sort</code></td>
            <td>string</td>
            <td>Поле и направление сортировки. Формат: <code>поле:направление</code> (например,
                <code>updated_at:desc</code>).
            </td>
        </tr>
        </tbody>
    </table>

    <div class="header-block">
        <h3>🔍 Синтаксис фильтрации</h3>
        <p>Параметр <code>filter</code> принимает строку в специальном формате:</p>
        <ul>
            <li>Пары <code>ключ=значение</code> разделяются точкой с запятой <code>;</code>.</li>
            <li>Множественные значения перечисляются через запятую <code>,</code> (работает как логическое ИЛИ).</li>
            <li>Поддерживаются диапазоны дат через суффиксы <code>_from</code> и <code>_to</code> (например, <code>created_at_from=2026-03-01</code>).
            </li>
        </ul>
        <p><strong>Пример строки:</strong> <code>status_id=1,7;salon_code=017587980;origin_type=2</code></p>
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
            <td><code>status_id=7</code><br><code>status_id=1,7</code></td>
        </tr>
        <tr>
            <td><code>inn_dealer</code></td>
            <td>
                ИНН дилера. Позволяет отфильтровать заказы конкретной организации.<br>
                <em>Примечание: пользователи видят заказы только из своего разрешенного списка.</em>
            </td>
            <td><code>inn_dealer=000000000001</code></td>
        </tr>
        <tr>
            <td><code>salon_code</code></td>
            <td>
                Код салона (магазина). Можно передать несколько через запятую.
            </td>
            <td><code>salon_code=017587980</code></td>
        </tr>
        <tr>
            <td><code>origin_type</code></td>
            <td>
                Источник создания заказа:<br>
                <code>0</code> — Сайт / Приложение<br>
                <code>1</code> — 1С<br>
                <code>2</code> — Калькулятор
            </td>
            <td><code>origin_type=1,2</code></td>
        </tr>
        </tbody>
    </table>

    <h2>Пример запроса (cURL)</h2>
    <details>
        <summary>Показать пример</summary>
        <pre class="response-content">
curl --location 'https://ligron.ru<?= $appPath ?>/orders?limit=2&filter=status_id=1,7;inn_dealer=000000000001' \
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
    "message": "Список заказов",
    "data": {
        "orders":[
            {
                "id": 634,
                "number": "82604775",
                "name": "000000000001017587980264525-1 (19.03.2026 13:47:30",
                "status_id": 7,
                "parent_id": null,
                "created_by": 0,
                "production_time": 0,
                "ready_date": null,
                "comment": "",
                "children_count": 0,
                "status_history":[
                    {
                        "id": 7,
                        "code": "100",
                        "date": "19.03.2026 13:47:37"
                    }
                ],
                "percent_payment": 0,
                "origin_type": 2,
                "due_payment": "17366.58",
                "inn_dealer": "000000000001",
                "salon_code": "017587980",
                "author_id": null,
                "created_at": 1773917257,
                "updated_at": 1774705236,
                "created_by_id": 0,
                "manager_id": null,
                "status_code": "100",
                "status_name": "Получен",
                "status_color": "#DAA520",
                "parent_order_number": null,
                "parent_order_id": null
            },
            {
                "id": 638,
                "number": "82604783",
                "name": "230126-1 (19.03.2026 14:47:13)",
                "status_id": 1,
                "parent_id": null,
                "created_by": 0,
                "production_time": 0,
                "ready_date": null,
                "comment": "",
                "children_count": 0,
                "status_history":[
                    {
                        "id": 1,
                        "code": "101",
                        "date": "19.03.2026 14:49:20"
                    },
                    {
                        "id": 7,
                        "code": "100",
                        "date": "19.03.2026 14:47:21"
                    }
                ],
                "percent_payment": 0,
                "origin_type": 2,
                "due_payment": "47534.90",
                "inn_dealer": "000000000001",
                "salon_code": "017587980",
                "author_id": null,
                "created_at": 1773920841,
                "updated_at": 1774705236,
                "created_by_id": 0,
                "manager_id": null,
                "status_code": "101",
                "status_name": "Оформляется",
                "status_color": "#FFD700",
                "parent_order_number": null,
                "parent_order_id": null
            }

        ],
        "pagination": {
            "limit": 2,
            "offset": 0,
            "total": "15"
        }
    }
}
</pre>
    </details>

</div>