<div class="api-doc-container">
    <h1>Получение деталей заказа</h1>
    <p>Метод возвращает полную информацию о заказе по его внутреннему ID, включая историю статусов и <strong>список прикрепленных файлов</strong>.</p>

    <div class="api-endpoint">
        <span class="method get">GET</span>
        <span class="url"><?= $appPath ?>/orders/{id}</span>
    </div>

    <div class="security-note">
        🔒 <strong>Требуется авторизация.</strong><br>
        Необходимо передать заголовок <code>X-Auth-Token</code>. <br>
        Пользователь видит только свои заказы (для дилеров) или заказы своих подопечных (для менеджеров).<br>
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
            <td>Внутренний ID заказа в системе (например, <code>65</code>).</td>
        </tr>
        </tbody>
    </table>

    <h2>Пример запроса (cURL)</h2>
    <details>
        <summary>Показать пример</summary>
        <pre class="response-content">
curl --location 'https://ligron.ru<?= $appPath ?>/orders/65' \
--header 'X-Auth-Token: ВАШ_ТОКЕН'
</pre>
    </details>

    <h2>Варианты ответов</h2>

    <h3>1. Успешный запрос (200 OK)</h3>
    <div class="status-badge status-success">HTTP 200 OK</div>
    <p>Возвращает объект <code>order</code> и массив <code>files</code>.</p>

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
            "name": "Столешница (Иванов)",
            "status_id": 4,

            "created_by_id": 3,
            "comment": "Без плинтуса",
            "status_code": "104",
            "status_name": "Оплачен",
            "status_color": "#9ACD32",
            "created_at": 1764673858,
            "updated_at": 1764838541,
            "status_history": [
                {
                    "id": 4,
                    "code": "104",
                    "date": "04.12.2025 11:05:30"
                }
            ]
        },
        "files": [
            {
                "id": 12,
                "order_id": 65,
                "name": "chertezh_final.pdf",
                "path": "/upload/e-order/files/pro_/3/65/",
                "size": 1048576,
                "mime": "application/pdf",
                "created_at": "2025-12-04T10:00:00"
            },
            {
                "id": 13,
                "order_id": 65,
                "name": "photo.jpg",
                "path": "/upload/e-order/files/pro_/3/65/",
                "size": 204800,
                "mime": "image/jpeg",
                "created_at": "2025-12-04T10:00:05"
            }
        ]
    }
}
</pre>
    </details>

    <h3>2. Заказ не найден (404 Not Found)</h3>
    <div class="status-badge status-error">HTTP 404 Not Found</div>
    <p>Заказ с указанным ID не существует.</p>
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

    <h3>3. Доступ запрещен (403 Forbidden)</h3>
    <div class="status-badge status-error">HTTP 403 Forbidden</div>
    <p>Попытка доступа к чужому заказу.</p>
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