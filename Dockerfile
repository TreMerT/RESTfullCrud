FROM php:8.2-fpm

# Sistem paketlerini yükleme
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    netcat-traditional

# PHP eklentilerini yükleme
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Redis eklentisini kurma
RUN pecl install redis && \
    docker-php-ext-enable redis

# Composer kurulumu
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Composer root olarak çalışmasına izin ver
ENV COMPOSER_ALLOW_SUPERUSER=1

# Çalışma dizinini ayarlama
WORKDIR /var/www


# Uygulama dosyalarını kopyalama
COPY ./src /var/www



# Composer bağımlılıklarını yükleme
RUN composer install --no-interaction --no-scripts

# Dosya izinlerini ayarlama
RUN chmod -R 777 storage bootstrap/cache

# Başlangıç scriptini oluştur
RUN echo '#!/bin/sh\n\
php artisan key:generate --force\n\
php artisan migrate --seed --force\n\
php artisan cache:clear\n\
php artisan config:clear\n\
php artisan route:clear\n\
php artisan view:clear\n\
php-fpm' > /usr/local/bin/start-container.sh

RUN chmod +x /usr/local/bin/start-container.sh

CMD ["/usr/local/bin/start-container.sh"]