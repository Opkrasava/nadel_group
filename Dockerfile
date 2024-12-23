FROM php:8.2-fpm

# Установить зависимости
RUN apt-get update && apt-get install -y \
    nano \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    && docker-php-ext-install intl pdo pdo_mysql zip

# Установить Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Установить Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Установить рабочую директорию
WORKDIR /app

# Копируем файлы проекта
COPY . /app

# Убедитесь, что PHP-FPM запускается
CMD ["php-fpm"]