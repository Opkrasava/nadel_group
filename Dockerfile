FROM php:8.2-cli

# Установить зависимости
RUN apt-get update && apt-get install -y \
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

# Запуск Symfony сервера
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
