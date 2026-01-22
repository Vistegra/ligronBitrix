<div class="api-doc-container">
    <h1>Webhook: Обновление заказа из 1С</h1>
    <p>Метод предназначен для обновления статуса, внутреннего названия и параметров заказа (дата готовности, оплата, сроки) во внешней системе.</p>

    <div class="api-endpoint">
        <span class="method post">POST</span>
        <span class="url">/local/api-e-order/webhook/1c/orders</span>
    </div>

    <div class="note">
        <strong>Примечание:</strong> Эндпоинт публичный, не требует Bearer токена авторизации пользователя, так как
        вызывается сервисом 1С.
    </div>

    <h2>Логика обработки</h2>
    <p>Система обрабатывает входящие данные по следующему алгоритму:</p>
    <ul class="list-disc pl-5 space-y-2">
        <li><strong>Смена статуса:</strong> Если переданный <code>status_code</code> отличается от текущего статуса заказа, добавляется новая запись в историю и обновляется статус.</li>
        <li><strong>Обновление имени:</strong> Если передано поле <code>name</code> и оно отличается от текущего, обновляется название заказа.</li>
        <li><strong>Обновление полей:</strong> Поля <code>ready_date</code>, <code>production_time</code> и <code>percent_payment</code> обновляются всегда, если они переданы.</li>
        <li><strong>Нет изменений:</strong> Если статус и имя совпадают, а дополнительные поля не переданы (или пусты), система возвращает ошибку 400.</li>
    </ul>

    <h2>Параметры запроса (Body JSON)</h2>

    <table class="param-table">
        <thead>
        <tr>
            <th>Параметр</th>
            <th>Тип</th>
            <th>Обязательность</th>
            <th>Описание</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><code>action</code></td>
            <td>string</td>
            <td><span class="required">Да</span></td>
            <td>Действие. Строго <code>"UPDATE"</code>.</td>
        </tr>
        <tr>
            <td><code>type</code></td>
            <td>string</td>
            <td><span class="required">Да</span></td>
            <td>Тип объекта: <br>
                <code>"ORDER"</code> — основной тип.<br>
                <code>"STATUS"</code> — <span style="color: #dc3545; font-weight: bold; font-size: 0.9em;">DEPRECATED</span> (поддерживается для совместимости).
            </td>
        </tr>
        <tr>
            <td><code>ligron_number</code></td>
            <td>string</td>
            <td><span class="required">Да</span></td>
            <td>Номер заказа в системе Ligron (например, <code>"72525161"</code>).</td>
        </tr>
        <tr>
            <td><code>status_code</code></td>
            <td>string</td>
            <td><span class="required">Да</span></td>
            <td>
                Символьный код нового статуса (например, <code>101</code>, <code>104</code>, <code>201</code>).<br>
                <a href="statuses">📄 Посмотреть справочник статусов</a>
            </td>
        </tr>
        <tr>
            <td><code>name</code></td>
            <td>string</td>
            <td><span class="optional">Нет</span></td>
            <td>Новое название заказа.</td>
        </tr>
        <tr>
            <td><code>status_date</code></td>
            <td>string</td>
            <td><span class="optional">Нет</span></td>
            <td>Дата установки статуса. Если не передана, используется текущее время сервера.</td>
        </tr>
        <tr>
            <td><code>production_date</code></td>
            <td>string</td>
            <td><span class="optional">Нет</span></td>
            <td>Дата готовности заказа (например, <code>"15.12.2025"</code>).</td>
        </tr>
        <tr>
            <td><code>production_time</code></td>
            <td>integer</td>
            <td><span class="optional">Нет</span></td>
            <td>Срок изготовления (в днях).</td>
        </tr>
        <tr>
            <td><code>percent_payment</code></td>
            <td>integer</td>
            <td><span class="optional">Нет</span></td>
            <td>Процент оплаты (число от 0 до 100).</td>
        </tr>
        </tbody>
    </table>

    <h2>Пример запроса (cURL)</h2>
    <pre class="response-content">
curl --location 'https://ligron.ru/local/api-e-order/webhook/1c/orders' \
--header 'Content-Type: application/json' \
--data '{
    "action": "UPDATE",
    "type": "ORDER",
    "ligron_number": "72525161",
    "name": "Столешница (Иванов) - Изм.",
    "status_code": "201",
    "status_date": "05.12.2025 12:18:17",
    "production_date": "15.12.2025",
    "production_time": 6,
    "percent_payment": 100
}'
</pre>

    <h2>Варианты ответов</h2>

    <!-- Успешный ответ -->
    <h3>1. Успешное обновление (200 OK)</h3>
    <div class="status-badge status-success">HTTP 200 OK</div>
    <p>Возвращается при успешном обновлении любых данных заказа.</p>

    <pre class="response-content">
{
    "status": "success",
    "message": "Данные заказа успешно обновлены",
    "data": {
        "received_at": "2025-12-05T12:18:20+03:00",
        "method": "post",
        "query": [],
        "body": {
            "action": "UPDATE",
            "type": "ORDER",
            "ligron_number": "72525161",
            "name": "Столешница (Иванов) - Изм.",
            "status_code": "201",
            ...
        },
        "order": {
             "id": 65,
             "number": "72525161",
             "name": "Столешница (Иванов) - Изм.",
             "status_code": "201",
             "status_name": "В производстве",
             "updated_at": 1764838541,
             "status_history": [...]
        }
    }
}
</pre>

    <!-- Ошибки -->
    <h3>2. Ошибки обработки (400 Bad Request)</h3>
    <div class="status-badge status-error">HTTP 400 Bad Request</div>

    <h4>Ошибка: Данные не изменились</h4>
    <p>Возникает, если переданные данные (статус, имя, даты) полностью совпадают с текущими данными заказа.</p>
    <pre class="response-content">
{
    "status": "error",
    "message": "Для заказа №72525161 данные не изменились (статус, имя и параметры совпадают).",
    "type": null
}
</pre>

    <h4>Ошибка: Заказ не найден</h4>
    <pre class="response-content">
{
    "status": "error",
    "message": "Заказ с номером 99999999 не найден в системе!",
    "type": null
}
</pre>

    <h4>Ошибка: Статус не найден</h4>
    <pre class="response-content">
{
    "status": "error",
    "message": "Статус с кодом ERROR_CODE не найден в системе!",
    "type": null
}
</pre>
</div>