FROM ubuntu:22.04

# Устанавливаем переменные окружения для избежания интерактивных запросов
ENV DEBIAN_FRONTEND=noninteractive

# Обновляем пакеты и устанавливаем Apache, PHP и необходимые модули
RUN apt-get update && apt-get install -y \
    apache2 \
    php \
    php-cli \
    php-common \
    php-mysql \
    php-pgsql \
    php-sqlite3 \
    php-pdo \
    php-json \
    php-curl \
    php-mbstring \
    php-xml \
    php-zip \
    libapache2-mod-php \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Включаем необходимые модули Apache
RUN a2enmod rewrite

# Настраиваем рабочую директорию
WORKDIR /var/www/html

# Копируем файлы
COPY index.html .
COPY api.php .

# Исправляем права
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Открываем порт 80
EXPOSE 80

# Запускаем Apache
CMD ["apache2ctl", "-D", "FOREGROUND"]

