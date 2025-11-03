# server/Dockerfile
FROM php:8.3-fpm

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    nginx \
    gnupg \
    curl \
    wget \
    unzip \
    git \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    g++ \
    unixodbc-dev \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# === КЛЮЧЕВАЯ ЧАСТЬ: Установка Microsoft ODBC без apt-key ===
RUN curl -sSL https://packages.microsoft.com/keys/microsoft.asc | \
    gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg

RUN curl -sSL https://packages.microsoft.com/config/debian/12/prod.list | \
    tee /etc/apt/sources.list.d/mssql-release.list

RUN apt-get update && \
    ACCEPT_EULA=Y apt-get install -y msodbcsql18 mssql-tools18 && \
    rm -rf /var/lib/apt/lists/*

# PHP расширения
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        mysqli \
        pdo_mysql \
        zip \
        intl \
        mbstring \
        exif \
        pcntl \
        bcmath \
        soap

# SQL Server драйверы
RUN pecl install sqlsrv pdo_sqlsrv && docker-php-ext-enable sqlsrv pdo_sqlsrv

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Копирование конфигов
COPY server/nginx.conf /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/

# Копируем приложение
COPY . /var/www/html

# Права (без ошибок, если папок нет)
RUN chown -R www-data:www-data /var/www/html && \
    find /var/www/html -type d -exec chmod 755 {} \; && \
    find /var/www/html -type f -exec chmod 644 {} \; || true

# Очистка
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# entrypoint
COPY server/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]