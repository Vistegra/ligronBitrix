<div class="api-doc-container">
    <h1>Создание заказа</h1>
    <p>Метод создает новый заказ в системе, загружает прикрепленные файлы и (если не черновик) отправляет данные в 1С Ligron для регистрации.</p>

    <div class="api-endpoint">
        <span class="method post">POST</span>
        <span class="url"><?= $appPath ?>/orders</span>
    </div>

    <!-- Уведомление об авторизации -->
    <div class="security-note">
        🔒 <strong>Требуется авторизация.</strong> <br>
        Необходимо передать заголовок <code>X-Auth-Token</code>. <br>
        <a href="auth">Подробнее об авторизации &rarr;</a>
    </div>

    <h2>Параметры запроса</h2>
    <p>Тип содержимого: <code>multipart/form-data</code> (для поддержки загрузки файлов).</p>

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
            <td><code>name</code></td>
            <td>string</td>
            <td><span class="required">Да</span></td>
            <td>Название заказа (например, "Кухня для клиента Иванова").</td>
        </tr>
        <tr>
            <td><code>file</code></td>
            <td>file / array</td>
            <td><span class="optional">Нет</span></td>
            <td>Один или несколько файлов для загрузки.</td>
        </tr>
        <tr>
            <td><code>comment</code></td>
            <td>string</td>
            <td><span class="optional">Нет</span></td>
            <td>Комментарий к заказу.</td>
        </tr>
        <tr>
            <td><code>is_draft</code></td>
            <td>0 | 1</td>
            <td><span class="optional">Нет</span></td>
            <td>
                <code>1</code> — сохранить как черновик (не отправлять в 1С).<br>
                <code>0</code> — создать и отправить в 1С (по умолчанию).
            </td>
        </tr>
        </tbody>
    </table>

    <div class="security-note" style="background: #e2e3e5; border-color: #d6d8db; color: #383d41; margin-top: 10px;">
        <strong>Примечание для менеджеров:</strong>
        Пользователи с ролью <code>manager</code> также могут передавать поля:
        <code>production_time</code> (дней на производство) и <code>ready_date</code> (дата готовности).
    </div>

    <h2>Пример запроса (cURL)</h2>
    <details>
        <summary>Показать пример</summary>
        <pre class="response-content">
curl --location 'https://ваш-сайт.ru<?= $appPath ?>/orders' \
--header 'X-Auth-Token: ВАШ_ТОКЕН' \
--form 'name="Заказ №555"' \
--form 'comment="Просьба упаковать в двойную пленку"' \
--form 'is_draft="0"' \
--form 'file=@"/path/to/image.jpg"' \
--form 'file=@"/path/to/drawing.pdf"'
</pre>
    </details>

    <h2>Варианты ответов</h2>

    <h3>1. Успешное создание (201 Created)</h3>
    <div class="status-badge status-success">HTTP 201 Created</div>
    <p>Заказ успешно создан, файлы загружены, номер от Ligron получен (если не черновик).</p>

    <details>
        <summary>Пример ответа</summary>
        <pre class="response-content">
{
  "status": "success",
  "message": "Заказ создан",
  "data": {
    "order": {
      "id": 105,
      "name": "Заказ №555",
      "number": "LIG-72525161",
      "status_id": 4,
      "status_code": "104",
      "status_name": "Оплачен",

      "created_at": 1701768000,
      "status_history": [
        {
          "id": 4,
          "code": "104",
          "date": "04.12.2025 12:00:00"
        }
      ]
    },
    "files": [
      {
        "id": 10,
        "name": "image.jpg",
        "path": "/upload/e-order/files/pro_/3/105/",
        "size": 102400
      },
      {
        "id": 11,
        "name": "drawing.pdf",
        "path": "/upload/e-order/files/pro_/3/105/",
        "size": 204800
      }
    ]
  }
}
</pre>
    </details>

    <h3>2. Частичный успех (207 Multi-Status)</h3>
    <div class="status-badge status-partial">HTTP 207 Multi-Status</div>
    <p>Заказ создан, но некоторые (или все) файлы не удалось загрузить.</p>
    <details>
        <summary>Пример ответа</summary>
        <pre class="response-content">
{
  "status": "partial",
  "message": "Заказ создан. Файлы загружены частично: bad_file.exe",
  "data": {
    "order": {
      "id": 106,
      "name": "Заказ с ошибкой файла",
      ...
    },
    "files": [
        // Только успешно загруженные файлы
    ]
  }
}
</pre>
    </details>

    <h3>3. Ошибка валидации (400 Bad Request)</h3>
    <div class="status-badge status-error">HTTP 400 Bad Request</div>
    <p>Не переданы обязательные поля (например, имя заказа) или произошла ошибка логики.</p>
    <details>
        <summary>Пример ответа</summary>
        <pre class="response-content">
{
  "status": "error",
  "message": "Ошибка создания заказа: Не указано имя заказа",
  "type": "RuntimeException"
}
</pre>
    </details>

</div>