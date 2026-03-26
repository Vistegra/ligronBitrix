# Настройка сборки шаблона Bitrix

Этот проект использует Vite для сборки стилей и скриптов шаблона Bitrix.

## Установка зависимостей

```bash
cd local/src-e-order-tmpl
npm install
```

## Разработка

Для запуска в режиме разработки с автоматической сборкой:

```bash
npm run dev
```

Vite будет отслеживать изменения в файлах `js/` и `scss/` и автоматически собирать их в папку шаблона. Файлы будут не минифицированы для удобства отладки.

## Сборка для продакшена

Для сборки стилей и скриптов для продакшена (минифицированных):

```bash
npm run build
```

После сборки файлы будут находиться:
- JavaScript: `local/templates/e-order/js/main.js` (минифицирован)
- CSS: `local/templates/e-order/css/index.css` (минифицирован)

## Структура проекта

```
src-e-order-tmpl/
├── js/
│   └── index.js          # Главный JS файл
├── scss/
│   ├── index.scss        # Главный SCSS файл
│   ├── _variables.scss   # SCSS переменные
│   ├── _common.scss      # Общие стили
│   ├── _header.scss      # Стили шапки
│   ├── _footer.scss      # Стили подвала
│   └── ...               # Другие стили
├── vite.config.js        # Конфигурация Vite
└── package.json          # Зависимости проекта
```

## Использование в Bitrix

После сборки подключите стили и скрипты в файлах шаблона:

### header.php
```php
<?php
use Bitrix\Main\Page\Asset;
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . '/css/index.css');
Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . '/js/main.js');
?>
```


