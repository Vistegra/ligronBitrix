<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->SetTitle("ЛИГРОН | Электронный заказ");

// Если пользователь не авторизован
if (!$USER->IsAuthorized()) {
  ?>
  <style>

      * { margin: 0; padding: 0; box-sizing: border-box; }

      .bx-auth-serv-icons {
          display: none !important;
      }
      body {
          font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          min-height: 100vh;
          padding: 20px;
          display: flex;
          align-items: center; /* Центрирование по вертикали */
          justify-content: center;
      }
      .container {
          width: 100%;
          max-width: 500px; /* Немного уменьшил для формы логина */
          margin: 0 auto;
          background: white;
          border-radius: 15px;
          box-shadow: 0 20px 40px rgba(0,0,0,0.1);
          overflow: hidden;
      }
      .header {
          background: linear-gradient(135deg, #2c3e50, #34495e);
          color: white;
          padding: 30px;
          text-align: center;
      }
      .header h1 { font-size: 24px; margin-bottom: 10px; font-weight: 300; }
      .header .subtitle { opacity: 0.8; font-size: 14px; }
      .content { padding: 40px; }
      .form-group { margin-bottom: 25px; }
      label { display: block; margin-bottom: 8px; font-weight: 500; color: #2c3e50; }
      input[type="text"], input[type="password"] {
          width: 100%;
          padding: 12px 15px;
          border: 2px solid #e9ecef;
          border-radius: 8px;
          font-size: 16px;
          transition: all 0.3s ease;
      }
      input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }

      input[type="submit"],
      .btn {
          background: linear-gradient(135deg, #667eea, #764ba2);
          color: white;
          border: none;
          padding: 15px 30px;
          border-radius: 8px;
          font-size: 16px;
          font-weight: 500;
          cursor: pointer;
          transition: all 0.3s ease;
          width: 100%;
          display: block;
      }
      input[type="submit"]:hover ,
      .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3); }

      /* Дополнительные стили для чекбокса и ссылок, чтобы они сочетались с вашим дизайном */
      .checkbox-group {
          display: flex;
          align-items: center;
          margin-bottom: 25px;
      }
      .checkbox-group input { margin-right: 10px; width: auto; }
      .checkbox-group label { margin-bottom: 0; cursor: pointer; }

      .auth-links { margin-top: 20px; text-align: center; font-size: 14px; }
      .auth-links a { color: #667eea; text-decoration: none; transition: 0.3s; }
      .auth-links a:hover { color: #764ba2; text-decoration: underline; }

      .social-login {
          margin-top: 30px;
          padding-top: 20px;
          border-top: 1px solid #e9ecef;
          text-align: center;
      }
      .social-login-title { margin-bottom: 15px; color: #7f8c8d; font-size: 14px; }
      .bx-auth-serv-icons { display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; }
      /* Стилизация иконок, если у вас нет CSS для bx-ss-icon */
      .bx-ss-icon {
          display: inline-block;
          width: 32px;
          height: 32px;
          background-color: #eee;
          border-radius: 4px; /* Мягкий квадрат */
      }

      /* Адаптив из вашего примера */
      @media (max-width: 480px) {
          .content { padding: 20px; }
          .header h1 { font-size: 20px; }
      }
  </style>

  <div class="auth-required" style="max-width: 400px; margin: 100px auto; text-align: center; padding: 40px; background: white; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
    <h2 style="color: #2c3e50; margin-bottom: 20px;">Требуется авторизация</h2>
    <p style="color: #7f8c8d; margin-bottom: 30px;">Для доступа к этой странице необходимо войти как администратор</p>

    <?php
    // Показываем стандартный компонент авторизации Bitrix
    $APPLICATION->IncludeComponent(
      "bitrix:system.auth.form",
      "",
      Array(
        "REGISTER_URL" => "",
        "FORGOT_PASSWORD_URL" => "",
        "PROFILE_URL" => "",
        "SHOW_ERRORS" => "Y"
      )
    );
    ?>
  </div>
  <?php
  require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
  exit();
}

// Проверяем права администратора
if (!$USER->IsAdmin()) {
  ShowError("Доступ запрещен. Требуются права администратора.");
  require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
  exit();
}

?>
  <style>
      * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
      }

      body {
          font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          min-height: 100vh;
          padding: 20px;
      }

      .container {
          max-width: 800px;
          margin: 0 auto;
          background: white;
          border-radius: 15px;
          box-shadow: 0 20px 40px rgba(0,0,0,0.1);
          overflow: hidden;
      }

      .header {
          background: linear-gradient(135deg, #2c3e50, #34495e);
          color: white;
          padding: 30px;
          text-align: center;
      }

      .header h1 {
          font-size: 28px;
          margin-bottom: 10px;
          font-weight: 300;
      }

      .header .subtitle {
          opacity: 0.8;
          font-size: 16px;
      }

      .content {
          padding: 40px;
      }

      .tabs {
          display: flex;
          margin-bottom: 30px;
          background: #f8f9fa;
          border-radius: 10px;
          padding: 5px;
      }

      .tab {
          flex: 1;
          padding: 15px;
          text-align: center;
          cursor: pointer;
          border-radius: 8px;
          transition: all 0.3s ease;
          font-weight: 500;
      }

      .tab.active {
          background: white;
          box-shadow: 0 2px 10px rgba(0,0,0,0.1);
          color: #667eea;
      }

      .form-section {
          display: none;
      }

      .form-section.active {
          display: block;
          animation: fadeIn 0.5s ease;
      }

      @keyframes fadeIn {
          from { opacity: 0; transform: translateY(10px); }
          to { opacity: 1; transform: translateY(0); }
      }

      .form-group {
          margin-bottom: 25px;
      }

      label {
          display: block;
          margin-bottom: 8px;
          font-weight: 500;
          color: #2c3e50;
      }

      input, textarea {
          width: 100%;
          padding: 12px 15px;
          border: 2px solid #e9ecef;
          border-radius: 8px;
          font-size: 16px;
          transition: all 0.3s ease;
      }

      input:focus, textarea:focus {
          outline: none;
          border-color: #667eea;
          box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      }

      textarea {
          resize: vertical;
          min-height: 100px;
          font-family: monospace;
      }

      .btn {
          background: linear-gradient(135deg, #667eea, #764ba2);
          color: white;
          border: none;
          padding: 15px 30px;
          border-radius: 8px;
          font-size: 16px;
          font-weight: 500;
          cursor: pointer;
          transition: all 0.3s ease;
          width: 100%;
      }

      .btn:hover {
          transform: translateY(-2px);
          box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
      }

      .btn:active {
          transform: translateY(0);
      }

      .btn:disabled {
          background: #bdc3c7;
          cursor: not-allowed;
          transform: none;
          box-shadow: none;
      }

      .result {
          margin-top: 25px;
          padding: 20px;
          background: #f8f9fa;
          border-radius: 8px;
          border-left: 4px solid #667eea;
          display: none;
      }

      .result.success {
          border-left-color: #27ae60;
      }

      .result.error {
          border-left-color: #e74c3c;
      }

      .result-title {
          font-weight: 600;
          margin-bottom: 10px;
          color: #2c3e50;
      }

      .result-content {
          font-family: monospace;
          background: white;
          padding: 15px;
          border-radius: 5px;
          word-break: break-all;
          font-size: 14px;
          line-height: 1.4;
      }

      .loading {
          display: none;
          text-align: center;
          padding: 20px;
      }

      .spinner {
          border: 3px solid #f3f3f3;
          border-top: 3px solid #667eea;
          border-radius: 50%;
          width: 30px;
          height: 30px;
          animation: spin 1s linear infinite;
          margin: 0 auto 10px;
      }

      @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
      }

      .copy-btn {
          background: #34495e;
          color: white;
          border: none;
          padding: 10px 15px;
          border-radius: 5px;
          cursor: pointer;
          margin-top: 10px;
          font-size: 14px;
          transition: background 0.3s ease;
          width: 100%;
      }

      .copy-btn:hover {
          background: #2c3e50;
      }

      /* Адаптив для планшетов */
      @media (max-width: 768px) {
          body {
              padding: 15px;
          }

          .content {
              padding: 25px;
          }

          .header {
              padding: 25px 20px;
          }

          .header h1 {
              font-size: 24px;
          }

          .header .subtitle {
              font-size: 14px;
          }

          .tabs {
              flex-direction: column;
              gap: 5px;
          }

          .tab {
              padding: 12px;
              font-size: 15px;
          }

          input, textarea {
              padding: 14px;
              font-size: 16px; /* Увеличиваем для мобильных */
          }

          .btn {
              padding: 16px 20px;
              font-size: 17px;
          }

          .result {
              padding: 15px;
              margin-top: 20px;
          }

          .result-content {
              padding: 12px;
              font-size: 13px;
          }

          .copy-btn {
              padding: 12px;
              font-size: 15px;
          }
      }

      /* Адаптив для маленьких телефонов */
      @media (max-width: 480px) {
          body {
              padding: 10px;
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
              min-height: 100vh;
          }

          .container {
              border-radius: 12px;
          }

          .content {
              padding: 20px 15px;
          }

          .header {
              padding: 20px 15px;
          }

          .header h1 {
              font-size: 22px;
              margin-bottom: 8px;
          }

          .header .subtitle {
              font-size: 13px;
          }

          .tabs {
              margin-bottom: 20px;
          }

          .tab {
              padding: 10px;
              font-size: 14px;
          }

          .form-group {
              margin-bottom: 20px;
          }

          label {
              font-size: 15px;
              margin-bottom: 6px;
          }

          input, textarea {
              padding: 12px;
              font-size: 16px; /* Важно для iOS */
              border-radius: 6px;
          }

          textarea {
              min-height: 80px;
          }

          .btn {
              padding: 14px 20px;
              font-size: 16px;
              border-radius: 6px;
          }

          .result {
              padding: 12px;
              margin-top: 15px;
              border-radius: 6px;
          }

          .result-title {
              font-size: 15px;
              margin-bottom: 8px;
          }

          .result-content {
              padding: 10px;
              font-size: 12px;
              border-radius: 4px;
          }

          .loading {
              padding: 15px;
          }

          .spinner {
              width: 25px;
              height: 25px;
          }

          .copy-btn {
              padding: 10px 12px;
              font-size: 14px;
              border-radius: 4px;
          }
      }

      /* Адаптив для очень маленьких экранов */
      @media (max-width: 360px) {
          .content {
              padding: 15px 10px;
          }

          .header {
              padding: 15px 10px;
          }

          .header h1 {
              font-size: 20px;
          }

          .tab {
              padding: 8px;
              font-size: 13px;
          }

          input, textarea {
              padding: 10px;
          }

          .btn {
              padding: 12px 15px;
          }
      }

      /* Поддержка landscape ориентации */
      @media (max-height: 500px) and (orientation: landscape) {
          body {
              padding: 10px;
          }

          .container {
              max-height: 90vh;
              overflow-y: auto;
          }

          .content {
              padding: 15px;
          }

          .form-group {
              margin-bottom: 15px;
          }

          textarea {
              min-height: 60px;
          }
      }

      /* Улучшение для touch устройств */
      @media (hover: none) and (pointer: coarse) {
          .btn:hover {
              transform: none;
              box-shadow: none;
          }

          .tab:hover {
              transform: none;
          }

          .copy-btn:hover {
              background: #34495e;
          }
      }
  </style>
  <div class="container">
    <div class="header">
      <h1>ЛИГРОН | Генератор токенов</h1>
      <div class="subtitle">Шифрование и дешифрование кодов пользователей</div>
    </div>

    <div class="content">
      <div class="tabs">
        <div class="tab active" data-tab="encrypt">Шифрование</div>
        <div class="tab" data-tab="decrypt">Дешифрование</div>
      </div>

      <!-- Форма шифрования -->
      <div class="form-section active" id="encrypt-section">
        <div class="form-group">
          <label for="code">Код пользователя:</label>
          <input type="text" id="code" placeholder="Введите код (например: CB00012564)" maxlength="50">
        </div>

        <button class="btn" onclick="encryptCode()">Зашифровать</button>

        <div class="loading" id="encrypt-loading">
          <div class="spinner"></div>
          <div>Шифруем...</div>
        </div>

        <div class="result" id="encrypt-result">
          <div class="result-title">Результат шифрования:</div>
          <div class="result-content" id="encrypt-token"></div>
          <button class="copy-btn" onclick="copyToClipboard('encrypt-token')">Скопировать токен</button>
        </div>
      </div>

      <!-- Форма дешифрования -->
      <div class="form-section" id="decrypt-section">
        <div class="form-group">
          <label for="token">Токен:</label>
          <textarea id="token" placeholder="Введите токен для дешифрования"></textarea>
        </div>

        <button class="btn" onclick="decryptToken()">Дешифровать</button>

        <div class="loading" id="decrypt-loading">
          <div class="spinner"></div>
          <div>Дешифруем...</div>
        </div>

        <div class="result" id="decrypt-result">
          <div class="result-title">Результат дешифрования:</div>
          <div class="result-content" id="decrypt-code"></div>
          <button class="copy-btn" onclick="copyToClipboard('decrypt-code')">Скопировать код</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Переключение между вкладками
    document.querySelectorAll('.tab').forEach(tab => {
      tab.addEventListener('click', () => {
        // Убираем активный класс у всех вкладок
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        // Скрываем все секции
        document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));

        // Активируем выбранную вкладку и секцию
        tab.classList.add('active');
        const tabId = tab.getAttribute('data-tab');
        document.getElementById(`${tabId}-section`).classList.add('active');

        // Очищаем результаты при переключении
        clearResults();
      });
    });

    // Функция шифрования
    async function encryptCode() {
      const code = document.getElementById('code').value.trim();

      if (!code) {
        showResult('encrypt', 'error', 'Пожалуйста, введите код пользователя');
        return;
      }

      const loading = document.getElementById('encrypt-loading');
      const result = document.getElementById('encrypt-result');

      loading.style.display = 'block';
      result.style.display = 'none';

      try {
        const response = await fetch('https://ligron.ru/local/api-e-order/auth/crypt', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            encrypt: true,
            code: code
          })
        });

        const data = await response.json();

        if (data.status === 'success') {
          showResult('encrypt', 'success', data.data.param);
        } else {
          showResult('encrypt', 'error', data.message || 'Ошибка при шифровании');
        }
      } catch (error) {
        showResult('encrypt', 'error', 'Ошибка сети: ' + error.message);
      } finally {
        loading.style.display = 'none';
      }
    }

    // Функция дешифрования
    async function decryptToken() {
      const token = document.getElementById('token').value.trim();

      if (!token) {
        showResult('decrypt', 'error', 'Пожалуйста, введите токен');
        return;
      }

      const loading = document.getElementById('decrypt-loading');
      const result = document.getElementById('decrypt-result');

      loading.style.display = 'block';
      result.style.display = 'none';

      try {
        const response = await fetch('https://ligron.ru/local/api-e-order/auth/crypt', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            decrypt: true,
            token: token
          })
        });

        const data = await response.json();

        if (data.status === 'success') {
          showResult('decrypt', 'success', data.data.param);
        } else {
          showResult('decrypt', 'error', data.message || 'Ошибка при дешифровании');
        }
      } catch (error) {
        showResult('decrypt', 'error', 'Ошибка сети: ' + error.message);
      } finally {
        loading.style.display = 'none';
      }
    }

    // Показать результат
    function showResult(type, status, content) {
      const result = document.getElementById(`${type}-result`);
      const contentElement = document.getElementById(`${type}-${type === 'encrypt' ? 'token' : 'code'}`);

      result.className = `result ${status}`;
      contentElement.textContent = content;
      result.style.display = 'block';
    }

    // Очистить результаты
    function clearResults() {
      document.querySelectorAll('.result').forEach(result => {
        result.style.display = 'none';
      });
      document.querySelectorAll('.loading').forEach(loading => {
        loading.style.display = 'none';
      });
    }

    // Копирование в буфер обмена
    function copyToClipboard(elementId) {
      const element = document.getElementById(elementId);
      const text = element.textContent;

      navigator.clipboard.writeText(text).then(() => {
        // Показываем временное сообщение об успехе
        const btn = event.target;
        const originalText = btn.textContent;
        btn.textContent = 'Скопировано!';
        btn.style.background = '#27ae60';

        setTimeout(() => {
          btn.textContent = originalText;
          btn.style.background = '#34495e';
        }, 2000);
      }).catch(err => {
        console.error('Ошибка копирования: ', err);
      });
    }

    // Обработка нажатия Enter
    document.getElementById('code').addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        encryptCode();
      }
    });

    document.getElementById('token').addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        decryptToken();
      }
    });
  </script>

<?php

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>