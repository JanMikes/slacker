FROM php:7.3

WORKDIR /slacker

# Install php extensions
RUN apt-get update && apt-get install -y \
        git \
        unzip \
        g++ \
        zlib1g-dev \
        libicu-dev \
        libzip-dev \
        libxml2-dev \
    && pecl -q install \
        zip \
    && docker-php-ext-install soap \
    && docker-php-ext-enable zip


# Installing composer and prestissimo globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_MEMORY_LIMIT=-1
RUN composer global require "hirak/prestissimo:^0.3" --prefer-dist --no-progress --no-suggest --classmap-authoritative --no-plugins --no-scripts

COPY composer.json composer.lock phpunit.xml ./

RUN composer install --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress --no-suggest \
    && composer clear-cache

RUN mkdir -p ./var/cache \
    ./var/log \
        && composer dump-autoload -o --no-dev

COPY . .

ENTRYPOINT [ 'bin/console' ]
CMD [ 'check-mail' ]
