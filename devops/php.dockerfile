FROM spiralscout/roadrunner:2025.1.0 AS roadrunner

FROM php:8.4-cli-alpine AS build

ARG CONTEXT_ENV=dev

RUN apk add --no-cache --virtual .build-deps \
    git \
    curl \
    libzip-dev \
    mpdecimal-dev \
    oniguruma-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    linux-headers \
    bash \
    mysql-client \
    $PHPIZE_DEPS

RUN docker-php-ext-install -j"$(nproc)" \
    pdo \
    pdo_mysql \
    mysqli \
    zip \
    mbstring \
    exif \
    pcntl \
    bcmath \
    intl \
    opcache \
    sockets

RUN pecl install http://pecl.php.net/get/decimal-1.5.0.tgz \
 && docker-php-ext-enable decimal

RUN curl -sS https://getcomposer.org/installer \
 | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /application

COPY . .

RUN if [ "$CONTEXT_ENV" = "prod" ]; then \
      composer install --no-scripts --no-autoloader --no-dev --prefer-dist --optimize-autoloader; \
    else \
      composer install --no-scripts --no-autoloader --prefer-dist; \
    fi \
 && composer dump-autoload --optimize

RUN if [ "$CONTEXT_ENV" = "dev" ]; then \
      pecl install xdebug; \
    fi

RUN apk del .build-deps \
 && rm -rf /root/.pearrc /tmp/pear ~/.pearrc /var/cache/apk/*

FROM php:8.4-cli-alpine

ARG CONTEXT_ENV=dev

RUN apk add --no-cache \
    bash \
    icu-libs \
    libjpeg-turbo \
    libpng \
    freetype \
    mpdecimal \
    libzip \
    oniguruma \
    mysql-client \
    curl \
 && rm -rf /var/cache/apk/*

COPY --from=roadrunner /usr/bin/rr /usr/local/bin/roadrunner
RUN chmod +x /usr/local/bin/roadrunner

COPY --from=build /usr/local/bin/composer /usr/local/bin/composer

WORKDIR /application
COPY --from=build /application /application

COPY --from=build /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=build /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

COPY devops/php/configs/php.ini /usr/local/etc/php/php.ini
COPY devops/php/configs/docker-php-ext-opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY devops/php/configs/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.available/xdebug.ini

COPY devops/php/scripts/init.d /docker-entrypoint-init.d
COPY devops/php/scripts/entrypoint.sh /entrypoint.sh

RUN if [ "$CONTEXT_ENV" != "dev" ]; then \
      find /usr/local/etc/php/conf.d -maxdepth 1 -type f -name "*xdebug*.ini" -delete; \
      find /usr/local/lib/php/extensions -type f -name "xdebug.so" -delete; \
      rm -f /usr/local/etc/php/conf.available/xdebug.ini; \
    else \
      rm -f /usr/local/etc/php/conf.d/zz-opcache.ini; \
      rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
      cp /usr/local/etc/php/conf.available/xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug.ini; \
    fi

RUN chown -R www-data:www-data /application/var \
 && rm -rf /root/.composer /root/.pearrc /tmp/*

USER www-data

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
