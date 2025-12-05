<div class="api-doc-container">
    <h1>Обновление заказа</h1>
    <p>Метод позволяет изменить параметры существующего заказа. Набор доступных для изменения полей зависит от роли текущего пользователя.</p>

    <div class="api-endpoint">
        <span class="method put">PUT</span>
        <span class="url">/local/api-e-order/orders/{id}</span>
    </div>

    <div class="security-note">
        🔒 <strong>Требуется авторизация.</strong><br>
        Необходимо передать заголовок <code>X-Auth-Token</code>.<br>
        Пользователь может редактировать только свои заказы.<br>
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
            <td><code>id</code></td>
            <td>integer <span class="required">*</span></td>
            <td>ID заказа (например, <code>65</code>).</td>
        </tr>
        </tbody>
    </table>

    <h2>Параметры тела запроса (Body JSON)</h2>
    <p>Передаются только те поля, которые необходимо обновить.</p>

    <div class="header-block">
        <h3>👮‍♂️ Права доступа к полям</h3>
        <ul>
            <li><strong>Дилеры</strong> могут обновлять: <code>name</code>, <code>comment</code>.</li>
            <li><strong>Менеджеры</strong> могут обновлять: <code>name</code>, <code>comment</code>, <code>fabrication</code>, <code>ready_date</code>.</li>
        </ul>
        <p><em>Попытка обновить недоступное поле приведет к ошибке 400.</em></p>
    </div>

    <table class="param-table">
        <thead>
        <tr>
            <th>Поле</th>
            <th>Тип</th>
            <th>Описание</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><code>name</code></td>
            <td>string</td>
            <td>Название заказа.</td>
        </tr>
        <tr>
            <td><code>comment</code></td>
            <td>string</td>
            <td>Комментарий к заказу.</td>
        </tr>
        <tr>
            <td><code>fabrication</code></td>
            <td>int</td>
            <td>
                <span class="tag" style="background:#fff3cd; color:#856404;">Manager Only</span><br>
                Количество дней на изготовление.
            </td>
        </tr>
        <tr>
            <td><code>ready_date</code></td>
            <td>string (date)</td>
            <td>
                <span class="tag" style="background:#fff3cd; color:#856404;">Manager Only</span><br>
                Планируемая дата готовности (формат <code>YYYY-MM-DD</code>).
            </td>
        </tr>
        </tbody>
    </table>

    <h2>Пример запроса (cURL)</h2>
    <details>
        <summary>Показать пример</summary>
        <pre class="response-content">
curl --location --request PUT 'https://ligron.ru/local/api-e-order/orders/65' \
--header 'X-Auth-Token: ВАШ_ТОКЕН' \
--header 'Content-Type: application/json' \
--data '{
    "name": "Кухня (Иванов) - Изменено",
    "comment": "Уточнение: без выреза под мойку"
}'
</pre>
    </details>

    <h2>Варианты ответов</h2>

    <h3>1. Успешное обновление (200 OK)</h3>
    <div class="status-badge status-success">HTTP 200 OK</div>
    <p>Возвращает обновленный объект заказа.</p>

    <details>
        <summary>Пример ответа</summary>
        <pre class="response-content">
{
    "status": "success",
    "message": "Заказ обновлен",
    "data": {
        "order": {
            "id": 65,
            "number": "72525161",
            "name": "Кухня (Иванов) - Изменено",
            "comment": "Уточнение: без выреза под мойку",
            "status_id": 4,
            "status_code": "104",
            "updated_at": 1764840000,
             ...
        }
    }
}
</pre>
    </details>

    <h3>2. Попытка изменить запрещенное поле (400 Bad Request)</h3>
    <div class="status-badge status-error">HTTP 400 Bad Request</div>
    <p>Возникает, если дилер пытается передать поля менеджера (например, <code>ready_date</code>).</p>
    <details>
        <summary>Пример ответа</summary>
        <pre class="response-content">
{
    "status": "error",
    "message": "Поле 'ready_date' не разрешено для изменения вашей ролью",
    "type": "Exception"
}
</pre>
    </details>

    <h3>3. Заказ не найден (404 Not Found)</h3>
    <div class="status-badge status-error">HTTP 404 Not Found</div>
    <details>
        <summary>Пример ответа</summary>
        <pre class="response-content">
{
  "status": "error",
  "message": "Заказ не найден",
  "type": "Exception"
}
</pre>
    </details>

</div>