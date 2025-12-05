<div class="api-doc-container">
    <h1>Webhook: Обновление статуса заказа из 1С</h1>
    <p>Метод предназначен для обновления статуса заказа во внешней системе.</p>

    <div class="api-endpoint">
        <span class="method post">POST</span>
        <span class="url">/local/api-e-order/webhook/1c/orders</span>
    </div>

    <div class="note">
        <strong>Примечание:</strong> Эндпоинт публичный, не требует Bearer токена авторизации пользователя, так как
        вызывается сервисом 1С.
    </div>

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
                Символьный код нового статуса (например, <code>101</code>, <code>104</code>, <code>91</code>).<br>
                <a href="statuses">📄 Посмотреть справочник статусов</a>
            </td>
        </tr>
        <tr>
            <td><code>status_date</code></td>
            <td>string</td>
            <td><span class="optional">Нет</span></td>
            <td>Дата установки статуса. Если не передана, используется текущее время сервера.</td>
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
    "status_code": "104",
    "status_date": "04.12.2025 11:05:30"
}'
</pre>
    </details>

    <h2>Варианты ответов</h2>

    <!-- Успешный ответ -->
    <h3>1. Успешное обновление</h3>
    <div class="status-badge status-success">HTTP 200 OK</div>
    <p>Возвращается, когда параметры <code>action</code> и <code>type</code> верны, заказ найден и обновление прошло
        успешно.</p>

    <details>
        <summary>Пример успешного ответа (JSON)</summary>
        <pre class="response-content">
{
    "status": "success",
    "message": "Статус заказа успешно обновлен",
    "data": {
        "received_at": "2025-12-04T11:55:41+03:00",
        "method": "post",
        "query": [],
        "body": {
            "action": "UPDATE",
            "type": "STATUS",
            "ligron_number": "72525161",
            "status_code": "104",
            "status_date": "04.12.2025 11:05:30"
        },
        "order": {
            "id": 65,
            "number": "72525161",
            "name": "Test 14",
            "status_id": 4,
            "parent_id": null,
            "status_history": [
                {
                    "id": 4,
                    "code": "104",
                    "date": "04.12.2025 11:05:30"
                },
                {
                    "id": 1,
                    "code": "101",
                    "date": "03.12.2025 17:09:06"
                }
            ],
            "updated_at": 1764838541,
            "status_code": "104",
            "status_name": "Оплачен",
            "status_color": "#9ACD32"
        }
    }
}
</pre>
    </details>

    <!-- Не распознано -->
    <h3>2. Действие не распознано</h3>
    <div class="status-badge status-success">HTTP 200 OK</div>
    <p>Возвращается, если <code>action</code> или <code>type</code> не соответствуют ожидаемым значениям. Система
        принимает данные, но не выполняет обновление.</p>
    <details>
        <summary>Пример ответа</summary>
        <pre class="response-content">
{
    "status": "success",
    "message": "Данные получены, но действие не распознано или не требует обработки",
    "data": {
        "received_at": "2025-12-04T12:00:00+03:00",
        "method": "post",
        "query": [],
        "body": {
            "action": "UNKNOWN",
            "type": "SOMETHING",
            "ligron_number": "72525161"
        }
    }
}
</pre>
    </details>

    <!-- Ошибки -->
    <h3>3. Ошибки обработки</h3>
    <div class="status-badge status-error">HTTP 400 / 500</div>
    <p>В случае логических ошибок структура ответа будет содержать <code>status: "error"</code>.</p>

    <h4>Ошибка: Не передан номер заказа</h4>
    <details>
        <summary>Пример ответа</summary>
        <pre class="response-content">
{
  "status": "error",
  "message": "Ошибка обработки вебхука: Не передан номер заказа (ligron_number)!",
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