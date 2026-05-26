FROM php:8.1-apache-bullseye

ARG APP_UID=1000
ARG APP_GID=1000

# Ferramentas de build + libs nativas
RUN set -eux; \
  apt-get update; \
  apt-get install -y --no-install-recommends \
    $PHPIZE_DEPS pkg-config git curl unzip \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libzip-dev zlib1g-dev \
    libxml2-dev libicu-dev \
    libpq-dev default-mysql-client \
    libc-client2007e-dev libkrb5-dev \
    libonig-dev \
  ; rm -rf /var/lib/apt/lists/*

# GD
RUN set -eux; \
  docker-php-ext-configure gd --with-freetype --with-jpeg; \
  docker-php-ext-install -j"$(nproc)" gd

# IMAP (opcional; se der erro, comente estas 2 linhas)
RUN set -eux; \
  docker-php-ext-configure imap --with-kerberos --with-imap-ssl; \
  docker-php-ext-install -j"$(nproc)" imap

# MySQL
RUN set -eux; \
  docker-php-ext-install -j"$(nproc)" mysqli pdo pdo_mysql; \
  docker-php-ext-enable mysqli

# ZIP (isola para ver erro se houver)
RUN set -eux; docker-php-ext-install -j"$(nproc)" zip

# INTL
RUN set -eux; docker-php-ext-install -j"$(nproc)" intl

# OPCACHE
RUN set -eux; docker-php-ext-install -j"$(nproc)" opcache

# CALENDAR
RUN set -eux; docker-php-ext-install -j"$(nproc)" calendar

# MBSTRING
RUN set -eux; docker-php-ext-install -j"$(nproc)" mbstring

# PostgreSQL (se não usa PG, pode remover este bloco)
RUN set -eux; \
  docker-php-ext-install -j"$(nproc)" pgsql pdo_pgsql; \
  docker-php-ext-enable pgsql

# Apache + htdocs
RUN set -eux; \
  a2enmod rewrite headers expires; \
  sed -ri 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/htdocs#g' /etc/apache2/sites-available/000-default.conf; \
  printf '%s\n' '<Directory "/var/www/html/htdocs">' '  AllowOverride All' '  Require all granted' '</Directory>' \
    > /etc/apache2/conf-available/dolibarr.conf; \
  a2enconf dolibarr

# php.ini
RUN set -eux; \
  { \
    echo "date.timezone=America/Sao_Paulo"; \
    echo "memory_limit=512M"; \
    echo "upload_max_filesize=256M"; \
    echo "post_max_size=256M"; \
    echo "max_execution_time=300"; \
    echo "display_errors=Off"; \
    echo "display_startup_errors=Off"; \
    echo "log_errors=On"; \
    echo "error_reporting=E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED"; \
    echo "opcache.enable=1"; \
    echo "opcache.validate_timestamps=1"; \
    echo "opcache.memory_consumption=128"; \
    echo "opcache.interned_strings_buffer=16"; \
    echo "opcache.max_accelerated_files=10000"; \
    echo "realpath_cache_size=4096K"; \
    echo "realpath_cache_ttl=600"; \
  } > /usr/local/etc/php/conf.d/dolibarr.ini

# Usuário
RUN groupmod -o -g ${APP_GID} www-data && usermod -o -u ${APP_UID} -g ${APP_GID} www-data
USER www-data

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --retries=5 \
  CMD curl -fsS http://localhost/index.php >/dev/null || exit 1
