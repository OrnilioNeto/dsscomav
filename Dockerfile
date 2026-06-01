FROM php:8.2-cli-bookworm

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APP_ENV=local \
    APP_DEBUG=true

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    curl \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        zip \
        intl \
        bcmath \
        xml \
        gd \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/app

# Copia o projeto inteiro
COPY . .

# Instala dependências
RUN composer install --no-interaction --prefer-dist

# Dependências adicionais
RUN composer require \
    tecnickcom/tcpdf:^6.7 \
    simplesoftwareio/simple-qrcode:^4.2 \
    --no-interaction \
    --with-all-dependencies

# Permissões Laravel
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["entrypoint.sh"]

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]