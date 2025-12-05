<div class="api-doc-container">
    <h1>Получение заказа по номеру</h1>
    <p>Метод позволяет получить детальную информацию о заказе, используя его уникальный номер (например, номер из 1С или дилерский номер).</p>

    <div class="api-endpoint">
        <span class="method get">GET</span>
        <span class="url">/local/api-e-order/orders/number/{number}</span>
    </div>

    <div class="security-note">
        🔒 <strong>Требуется авторизация.</strong> <br>
        Необходимо передать заголовок <code>X-Auth-Token</code>.<br>
        Пользователь должен иметь права на просмотр этого заказа (быть создателем или курирующим менеджером).<br>
        <a href="auth">Подробнее об авторизации &rarr;</a>
    </div>

    <h2>Параметры пути (Path Parameters)</h2>

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
            <td><code>number</code></td>
            <td>string <span class="required">*</span></td>
            <td>Уникальный номер заказа (например, <code>72525161</code>).</td>
        </tr>
        </tbody>
    </table>

    <h2>Пример запроса (cURL)</h2>
    <details>
        <summary>Показать пример</summary>
        <pre class="response-content">
curl --location 'https://ligron.ru/local/api-e-order/orders/number/72525161' \
--header 'X-Auth-Token: ВАШ_ТОКЕН'
</pre>
    </details>

    <h2>Варианты ответов</h2>

    <h3>1. Заказ найден (200 OK)</h3>
    <div class="status-badge status-success">HTTP 200 OK</div>
    <p>Возвращает объект заказа со статусом и историей.</p>

    <details>
        <summary>Пример ответа</summary>
        <pre class="response-content">
{
    "status": "success",
    "message": "Детали заказа",
    "data": {
        "order": {
            "id": 65,
            "number": "72525161",
            "name": "Кухня (Иванов)",
            "status_id": 4,
            "created_by": 1,
            "dealer_prefix": "pro_",
            "comment": "Срочный заказ",
            "status_code": "104",
            "status_name": "Оплачен",
            "status_color": "#9ACD32",
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
            "created_at": 1764673858,
            "updated_at": 1764838541
        }
    }
}
</pre>
    </details>

    <h3>2. Заказ не найден (404 Not Found)</h3>
    <div class="status-badge status-error">HTTP 404 Not Found</div>
    <p>Заказ с таким номером отсутствует в системе.</p>
    <details>
        <summary>Пример ответа</summary>
        <pre class="response-content">
{
  "status": "error",
  "message": "Заказ не найден",
  "type": "OrderApi\\Services\\Exceptions\\NotFoundException"
}
</pre>
    </details>

    <h3>3. Доступ запрещен (403 Forbidden)</h3>
    <div class="status-badge status-error">HTTP 403 Forbidden</div>
    <p>Заказ существует, но текущий пользователь не имеет прав на его просмотр (другой дилер).</p>
    <details>
        <summary>Пример ответа</summary>
        <pre class="response-content">
{
  "status": "error",
  "message": "Access denied",
  "type": "Exception"
}
</pre>
    </details>

</div>