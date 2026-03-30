<div class="api-doc-container">
    <h1>Загрузка файлов к заказу</h1>
    <p>Метод позволяет прикрепить дополнительные файлы к уже созданному заказу. Поддерживается одновременная загрузка нескольких файлов.</p>

    <div class="api-endpoint">
        <span class="method post">POST</span>
        <span class="url"><?= $appPath ?>/orders/{id}/files</span>
    </div>

    <div class="security-note">
        🔒 <strong>Требуется авторизация.</strong><br>
        Необходимо передать заголовок <code>X-Auth-Token</code>.<br>
        Пользователь должен иметь права на редактирование этого заказа.<br>
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
            <td>ID заказа, к которому загружаются файлы.</td>
        </tr>
        </tbody>
    </table>

    <h2>Параметры тела (Body)</h2>
    <p>Тип содержимого: <code>multipart/form-data</code>.</p>
    <table class="param-table">
        <thead>
        <tr>
            <th>Ключ</th>
            <th>Тип</th>
            <th>Описание</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><code>file</code></td>
            <td>file <span class="required">*</span></td>
            <td>Файл для загрузки. Можно передать несколько файлов, используя этот ключ несколько раз.</td>
        </tr>
        </tbody>
    </table>

    <h2>Пример запроса (cURL)</h2>
    <details>
        <summary>Показать пример</summary>
        <pre class="response-content">
curl --location 'https://ligron.ru<?= $appPath ?>/orders/65/files' \
--header 'X-Auth-Token: ВАШ_ТОКЕН' \
--form 'file=@"/C:/Documents/scan.pdf"' \
--form 'file=@"/C:/Documents/photo.jpg"'
</pre>
    </details>

    <h2>Варианты ответов</h2>

    <h3>1. Успешная загрузка (201 Created)</h3>
    <div class="status-badge status-success">HTTP 201 Created</div>
    <p>Все переданные файлы успешно сохранены.</p>

    <details>
        <summary>Пример ответа</summary>
        <pre class="response-content">
{
    "status": "success",
    "message": "Все файлы загружены",
    "data": {
        "files": [
            {
                "id": 14,
                "order_id": 65,
                "name": "scan.pdf",
                "path": "/upload/e-order/files/pro_/3/65/",
                "size": 512000,
                "mime": "application/pdf",
                "created_at": "2025-12-04T12:00:00"
            },
            {
                "id": 15,
                "order_id": 65,
                "name": "photo.jpg",
                "path": "/upload/e-order/files/pro_/3/65/",
                "size": 204800,
                "mime": "image/jpeg",
                "created_at": "2025-12-04T12:00:01"
            }
        ]
    }
}
</pre>
    </details>

    <h3>2. Частичная загрузка (207 Multi-Status)</h3>
    <div class="status-badge status-partial">HTTP 207 Multi-Status</div>
    <p>Возникает, если часть файлов загрузилась, а часть — нет (например, из-за превышения размера или недопустимого формата).</p>

    <details>
        <summary>Пример ответа</summary>
        <pre class="response-content">
{
    "status": "partial",
    "message": "Файлы загружены частично: virus.exe",
    "data": {
        "files": [
            {
                "id": 16,
                "name": "good_file.jpg",
                "path": "...",
                "size": 1024
            }
        ]
    }
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

    <h3>4. Файлы не переданы (400 Bad Request)</h3>
    <div class="status-badge status-error">HTTP 400 Bad Request</div>
    <details>
        <summary>Пример ответа</summary>
        <pre class="response-content">
{
  "status": "error",
  "message": "Файлы не переданы",
  "type": "Exception"
}
</pre>
    </details>

</div>