<div class="api-doc-container">
    <h1>Webhook: Создание заказа из 1С</h1>
    <p>Метод используется для регистрации нового заказа, пришедшего из внешней системы (1С Предприятие или Калькулятор).</p>

    <div class="api-endpoint">
        <span class="method post">POST</span>
        <span class="url">/local/api-e-order/webhook/1c/orders</span>
    </div>

    <div class="note">
        <strong>Примечание:</strong> Эндпоинт публичный, не требует Bearer токена.
    </div>

    <h2>Логика обработки</h2>
    <ol class="list-decimal pl-5 space-y-2">
        <li><strong>Проверка дублей:</strong> Система проверяет наличие заказа с таким <code>ligron_number</code>. Если заказ уже есть, возвращается ошибка <strong>409 Conflict</strong>.</li>
        <li><strong>Поиск Дилера:</strong> По полю <code>client</code> (ИНН) ищется дилер в базе сайта. Если не найден — ошибка 400.</li>
        <li><strong>Поиск Салона:</strong> По полю <code>salon</code> (Код салона) ищется конкретный менеджер(пользователь) внутри дилера.</li>
        <li><strong>Создание:</strong> Заказ сохраняется с типом источника <code>1С</code>.</li>
    </ol>

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
        <!-- Системные поля -->
        <tr>
            <td><code>action</code></td>
            <td>string</td>
            <td><span class="required">Да</span></td>
            <td>Действие. Строго <code>"CREATE"</code>.</td>
        </tr>
        <tr>
            <td><code>type</code></td>
            <td>string</td>
            <td><span class="required">Да</span></td>
            <td>Тип объекта. Строго <code>"ORDER"</code>.</td>
        </tr>
        <tr>
            <td><code>ligron_number</code></td>
            <td>string</td>
            <td><span class="required">Да</span></td>
            <td>Уникальный номер заказа в 1С. Используется как идентификатор для проверки дублей.</td>
        </tr>
        <tr>
            <td><code>origin_type</code></td>
            <td>integer</td>
            <td><span class="required">Да</span></td>
            <td>Источник заказа: <code>1</code> (1С) или <code>2</code> (Калькулятор).</td>
        </tr>

        <!-- Поля привязки -->
        <tr>
            <td><code>client</code></td>
            <td>string</td>
            <td><span class="required">Да</span></td>
            <td>ИНН контрагента. Используется для поиска Дилера в системе.</td>
        </tr>
        <tr>
            <td><code>salon</code></td>
            <td>string</td>
            <td><span class="required">Да</span></td>
            <td>Код салона. Используется для привязки заказа к конкретному пользователю (менеджеру).</td>
        </tr>
        <tr>
            <td><code>manager_id</code></td>
            <td>integer</td>
            <td><span class="optional">Нет</span></td>
            <td>Внешний ID менеджера (если требуется).</td>
        </tr>

        <!-- Основные данные -->
        <tr>
            <td><code>name</code></td>
            <td>string</td>
            <td><span class="required">Да</span></td>
            <td>Внутренний номер или название заказа (например, "Внутренний номер 123").</td>
        </tr>
        <tr>
            <td><code>date</code></td>
            <td>string</td>
            <td><span class="required">Да</span></td>
            <td>Дата и время оформления заказа (формат <code>dd.mm.yyyy H:i:s</code>).</td>
        </tr>
        <tr>
            <td><code>comment</code></td>
            <td>string</td>
            <td><span class="optional">Нет</span></td>
            <td>Комментарий к заказу.</td>
        </tr>

        <!-- Статус -->
        <tr>
            <td><code>status_code</code></td>
            <td>string</td>
            <td><span class="required">Да</span></td>
            <td>Код начального статуса (например, <code>101</code>).</td>
        </tr>
        <tr>
            <td><code>status_date</code></td>
            <td>string</td>
            <td><span class="required">Да</span></td>
            <td>Дата и время установки статуса.</td>
        </tr>

        <!-- Производство и оплата -->
        <tr>
            <td><code>production_date</code></td>
            <td>string</td>
            <td><span class="optional">Нет</span></td>
            <td>Планируемая дата готовности (формат <code>dd.mm.yyyy</code>).</td>
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

    <h2>Варианты ответов</h2>

    <h3>1. Успешное создание (201 Created)</h3>
    <div class="status-badge status-success">HTTP 201 Created</div>
    <pre class="response-content">
{
    "status": "success",
    "message": "Заказ успешно создан",
    "data": {
        "received_at": "2025-12-22T10:00:05+03:00",
        "method": "post",
        "order": {
            "id": 85,
            "number": "777000123",
            "status_code": "101",
            ...
        }
    }
}
</pre>

    <h3>2. Заказ уже существует (409 Conflict)</h3>
    <div class="status-badge" style="background:#ffc107; color:#000;">HTTP 409 Conflict</div>
    <p>Возвращается, если заказ с таким <code>ligron_number</code> уже есть в базе. 1С должна считать это подтверждением, что заказ доставлен.</p>
    <pre class="response-content">
{
    "status": "error",
    "message": "Заказ с номером 777000123 уже существует.",
    "type": null
}
</pre>

    <h3>3. Ошибка валидации (400 Bad Request)</h3>
    <div class="status-badge status-error">HTTP 400 Bad Request</div>
    <p>Возникает, если не передан обязательный параметр, не найден дилер или передан неверный JSON.</p>
    <pre class="response-content">
{
    "status": "error",
    "message": "Не передан обязательный параметр: salon",
    "type": null
}
</pre>
</div>