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

RUN apt-get update -y

RUN apt-get install -y --no-install-recommends sudo python3 poppler-utils

RUN echo 'developer ALL=(ALL) NOPASSWD:ALL' > /etc/sudoers.d/developer && \
    chmod 0440 /etc/sudoers.d/developer

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN sed -i 's/^memory_limit = .*/memory_limit = 1024M/' "$PHP_INI_DIR/php.ini"

RUN mkdir -p /tmp/php-cs-fixer

# --- BEGIN manual Node.js install (temporary, see .devcontainer/devcontainer.json) ---
#
# The devcontainer normally gets Node.js from the
# "ghcr.io/devcontainers/features/node:1" feature, but that feature is
# currently disabled (commented out) in .devcontainer/devcontainer.json:
# its install.sh calls `source` to load nvm, which breaks under dash on
# Debian trixie (our php:8.5-fpm base), even on the latest 1.7.1 release
# (devcontainers/features issues #565 and #1534). NodeSource's apt repo
# doesn't support trixie either, so we install the official prebuilt
# tarball from nodejs.org directly instead, which is distro-agnostic.
#
# Once the upstream feature is fixed for trixie, remove this block, delete
# the ARG below, and re-enable the feature in devcontainer.json.
ARG NODE_VERSION=24.22.0
RUN apt-get install -y --no-install-recommends xz-utils \
    && ARCH="$(dpkg --print-architecture | sed 's/amd64/x64/;s/arm64/arm64/')" \
    && curl -fsSL "https://nodejs.org/dist/v${NODE_VERSION}/node-v${NODE_VERSION}-linux-${ARCH}.tar.xz" \
       | tar -xJ -C /usr/local --strip-components=1 \
    && corepack enable
# --- END manual Node.js install ---

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

## Install Python3 for Claude
# RUN apt-get install -y --no-install-recommends python3

FROM base AS prod

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN sed -i 's/^memory_limit = .*/memory_limit = 1024M/' "$PHP_INI_DIR/php.ini"

