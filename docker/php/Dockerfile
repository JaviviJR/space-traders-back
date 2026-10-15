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

FROM base AS dev-base

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN sed -i 's/^memory_limit = .*/memory_limit = 1024M/' "$PHP_INI_DIR/php.ini"

RUN mkdir -p /tmp/php-cs-fixer

FROM dev-base AS dev

RUN install-php-extensions xdebug

COPY --chmod=0644 xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY --chmod=0755 php-no-xdebug /usr/local/bin/php-no-xdebug

RUN mkdir -p /tmp/php-cs-fixer

RUN git clone --depth 1 https://github.com/phpactor/phpactor.git /opt/phpactor \
    && composer install -d /opt/phpactor --no-dev --no-interaction --optimize-autoloader \
    && ln -s /opt/phpactor/bin/phpactor /usr/local/bin/phpactor

RUN mkdir -p /home/developer/.local/share/phpactor \
    && printf '{"/home/developer/app": true}' > /home/developer/.local/share/phpactor/trust.json \
    && chown -R developer:developer /home/developer/.local/share/phpactor

RUN git config --global --add safe.directory /opt/phpactor

FROM base AS prod

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN sed -i 's/^memory_limit = .*/memory_limit = 1024M/' "$PHP_INI_DIR/php.ini"

