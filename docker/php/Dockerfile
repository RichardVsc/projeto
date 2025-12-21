FROM php:8.2-cli-alpine

RUN apk update && apk add --no-cache \
    bash \
    git \
    curl \
    zip \
    unzip \
    libpq \
    postgresql-dev \
    autoconf \
    build-base \
    libtool \
    pcre-dev \
    openssl \
    openssl-dev

RUN docker-php-ext-install pcntl pdo pdo_pgsql

RUN pecl install redis && docker-php-ext-enable redis

RUN printf "no\nyes\nno\nno\nno\nno\nno\nno\nno\nno\nno\nno\nno\n" \
    | pecl install swoole -- \
        --enable-openssl \
    && docker-php-ext-enable swoole \
    && echo "swoole.use_shortname = Off" > /usr/local/etc/php/conf.d/swoole.ini

RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin \
    --filename=composer

WORKDIR /var/www/project
