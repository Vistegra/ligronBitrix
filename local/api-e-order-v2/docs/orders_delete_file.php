<div class="api-doc-container">
    <h1>Удаление файла</h1>
    <p>Метод удаляет конкретный файл, прикрепленный к заказу. Файл удаляется физически с диска и из базы данных. Действие необратимо.</p>

    <div class="api-endpoint">
        <span class="method delete">DELETE</span>
        <span class="url"><?= $appPath ?>/orders/{id}/files/{fileId}</span>
    </div>

    <div class="security-note">
        🔒 <strong>Требуется авторизация.</strong><br>
        Необходимо передать заголовок <code>X-Auth-Token</code>.<br>
        Пользователь должен иметь права на редактирование заказа. <br>
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
        <tr>
            <td><code>fileId</code></td>
            <td>integer <span class="required">*</span></td>
            <td>ID файла, который нужно удалить (например, <code>14</code>).</td>
        </tr>
        </tbody>
    </table>

    <h2>Пример запроса (cURL)</h2>
    <details>
        <summary>Показать пример</summary>
        <pre class="response-content">
curl --location --request DELETE 'https://ligron.ru<?= $appPath ?>/orders/65/files/14' \
--header 'X-Auth-Token: ВАШ_ТОКЕН'
</pre>
    </details>

    <h2>Варианты ответов</h2>

    <h3>1. Успешное удаление (204 No Content)</h3>
    <div class="status-badge status-success">HTTP 204 No Content</div>
    <p>Файл успешно удален. Тело ответа может быть пустым или содержать подтверждение (в зависимости от клиента).</p>

    <details>
        <summary>Пример ответа (если возвращается тело)</summary>
        <pre class="response-content">
{
    "status": "success",
    "message": "Файл удален",
    "data": []
}
</pre>
    </details>

    <h3>2. Файл или заказ не найден (404 Not Found)</h3>
    <div class="status-badge status-error">HTTP 404 Not Found</div>
    <p>Возникает, если указан несуществующий ID заказа или ID файла, либо файл не принадлежит этому заказу.</p>
    <details>
        <summary>Пример ответа</summary>
        <pre class="response-content">
{
  "status": "error",
  "message": "Не найден заказ id=65",
  "type": "Exception"
}
</pre>
    </details>

    <h3>3. Ошибка доступа (403 Forbidden)</h3>
    <div class="status-badge status-error">HTTP 403 Forbidden</div>
    <p>У пользователя нет прав на редактирование этого заказа.</p>
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