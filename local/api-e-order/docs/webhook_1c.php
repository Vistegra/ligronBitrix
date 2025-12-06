<div class="api-doc-container">
    <h1>Webhook: Обновление статуса и параметров заказа из 1С</h1>
    <p>Метод предназначен для обновления статуса заказа, а также дополнительных полей (дата готовности, оплата, сроки) во внешней системе.</p>

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
        <li>
            <strong>Смена статуса:</strong> Если переданный <code>status_code</code> отличается от текущего статуса заказа:
            <ul class="list-circle pl-5 mt-1 text-sm text-gray-600">
                <li>Обновляется текущий <code>status_id</code>.</li>
                <li>Добавляется новая запись в <code>status_history</code>.</li>
                <li>Если переданы доп. поля (<code>percent_payment</code> и др.), они также обновляются.</li>
            </ul>
        </li>
        <li>
            <strong>Обновление полей (без смены статуса):</strong> Если <code>status_code</code> совпадает с текущим, но переданы дополнительные поля:
            <ul class="list-circle pl-5 mt-1 text-sm text-gray-600">
                <li>Статус и история статусов <strong>не меняются</strong>.</li>
                <li>Обновляются только переданные поля (<code>ready_date</code>, <code>production_time</code>, <code>percent_payment</code>).</li>
                <li>Возвращается успешный ответ.</li>
            </ul>
        </li>
        <li>
            <strong>Нет изменений:</strong> Если <code>status_code</code> совпадает с текущим и дополнительные поля не переданы (или пусты):
            <ul class="list-circle pl-5 mt-1 text-sm text-gray-600">
                <li>Система возвращает ошибку, так как обновлять нечего.</li>
            </ul>
        </li>
    </ul>

    <h2>Параметры запроса (Body)</h2>
    <p>Данные передаются в формате <code>JSON</code>.</p>

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
            <td>Действие. Для обновления статуса должно быть строго <code>"UPDATE"</code>.</td>
        </tr>
        <tr>
            <td><code>type</code></td>
            <td>string</td>
            <td><span class="required">Да</span></td>
            <td>Тип объекта. Для обновления статуса должно быть строго <code>"STATUS"</code>.</td>
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
            <td><code>status_date</code></td>
            <td>string</td>
            <td><span class="optional">Нет</span></td>
            <td>Дата установки статуса. Если не передана, используется текущее время сервера.</td>
        </tr>
        <tr>
            <td><code>production_date</code></td>
            <td>string</td>
            <td><span class="optional">Нет</span></td>
            <td>Дата готовности заказа (например, <code>"15.12.2025 0:00:00"</code>). Записывается в поле <code>ready_date</code>.</td>
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
    <details>
        <summary>Показать пример</summary>
        <pre class="response-content">
curl --location 'https://ligron.ru/local/api-e-order/webhook/1c/orders' \
--header 'Content-Type: application/json' \
--data '{
    "action": "UPDATE",
    "type": "STATUS",
    "ligron_number": "72525161",
    "status_code": "201",
    "status_date": "05.12.2025 12:18:17",
    "production_date": "15.12.2025 0:00:00",
    "production_time": 6,
    "percent_payment": 100
}'
</pre>
    </details>

    <h2>Варианты ответов</h2>

    <!-- Успешный ответ -->
    <h3>1. Успешное обновление</h3>
    <div class="status-badge status-success">HTTP 200 OK</div>
    <p>Возвращается при успешном обновлении статуса или дополнительных полей.</p>

    <details>
        <summary>Пример успешного ответа (JSON)</summary>
        <pre class="response-content">
{
    "status": "success",
    "message": "Статус заказа успешно обновлен",
    "data": {
        "received_at": "2025-12-05T12:18:20+03:00",
        "method": "post",
        "query": [],
        "body": {
            "action": "UPDATE",
            "type": "STATUS",
            "ligron_number": "72525161",
            "status_code": "201",
            "status_date": "05.12.2025 12:18:17",
            "production_date": "15.12.2025 0:00:00",
            "production_time": 6,
            "percent_payment": 100
        },
        "order": {
            "id": 65,
            "number": "72525161",
            "name": "Test 14",
            "status_id": 5,
            "parent_id": null,
            "status_history": [
                {
                    "id": 5,
                    "code": "201",
                    "date": "05.12.2025 12:18:17"
                },
                {
                    "id": 4,
                    "code": "104",
                    "date": "04.12.2025 11:05:30"
                }
            ],
            "updated_at": 1764838541,
            "status_code": "201",
            "status_name": "В производстве",
            "status_color": "#F59E0B",
            "production_time": 6,
            "percent_payment": 100,
            "ready_date": "15.12.2025"
        }
    }
}
</pre>
    </details>

    <!-- Ошибки -->
    <h3>2. Ошибки обработки</h3>
    <div class="status-badge status-error">HTTP 400 / 500</div>
    <p>В случае логических ошибок структура ответа будет содержать <code>status: "error"</code>.</p>

    <h4>Ошибка: Данные не изменились</h4>
    <p>Возникает, если переданный статус совпадает с текущим, и нет других полей для обновления.</p>
    <details>
        <summary>Пример ответа</summary>
        <pre class="response-content">
{
  "status": "error",
  "message": "Ошибка обработки вебхука: Для заказа №72525161 статус '201' уже установлен, и нет дополнительных данных для обновления."
}
</pre>
    </details>

    <h4>Ошибка: Заказ не найден</h4>
    <details>
        <summary>Пример ответа</summary>
        <pre class="response-content">
{
  "status": "error",
  "message": "Ошибка обработки вебхука: Заказ с номером 99999999 не найден в системе!",
}
</pre>
    </details>

    <h4>Ошибка: Статус не найден</h4>
    <details>
        <summary>Пример ответа</summary>
        <pre class="response-content">
{
  "status": "error",
  "message": "Ошибка обработки вебхука: Статус с кодом ERROR_CODE не найден в системе!",
}
</pre>
    </details>
</div>