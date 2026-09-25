FROM --platform=linux/amd64 php:8.5-fpm as base

ARG UID=1000
ARG GID=1000

RUN groupadd -g ${GID} developer \
    && useradd -u ${UID} -g developer -m developer

RUN apt-get update -y && apt-get dist-upgrade -y

RUN apt-get install -y --no-install-recommends locales ca-certificates nano git

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
# intl
RUN install-php-extensions curl zip bcmath opcache intl pdo_pgsql

# Composer v2
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash
RUN apt install symfony-cli -y

RUN git config --global --add safe.directory /home/app

RUN ln -sf /usr/local/bin/php /usr/bin/php

FROM base AS dev

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN sed -i 's/^memory_limit = .*/memory_limit = 1024M/' "$PHP_INI_DIR/php.ini"

RUN install-php-extensions xdebug

COPY --chmod=0644 xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

FROM base AS prod

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN sed -i 's/^memory_limit = .*/memory_limit = 1024M/' "$PHP_INI_DIR/php.ini"

