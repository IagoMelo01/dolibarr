# Use ARG para escolher a versão do PHP sem tocar em FROM
ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-apache

# UID/GID para evitar arquivos root no host
ARG APP_UID=1000
ARG APP_GID=1000

# Pacotes e extensões necessárias ao Dolibarr
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      git unzip curl \
      libpng-dev libjpeg-dev libfreetype6-dev \
      libzip-dev libxml2-dev libicu-dev \
      libpq-dev default-mysql-client; \
    rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" gd zip intl pdo pdo_mysql pdo_pgsql opcache

# Apache: habilitar módulos e apontar para /var/www/html/htdocs
RUN a2enmod rewrite headers expires
RUN sed -ri 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/htdocs#g' /etc/apache2/sites-available/000-default.conf \
 && { \
      echo '<Directory "/var/www/html/htdocs">'; \
      echo '  AllowOverride All'; \
      echo '  Require all granted'; \
      echo '</Directory>'; \
    } > /etc/apache2/conf-available/dolibarr.conf \
 && a2enconf dolibarr

# PHP ajustes
RUN { \
      echo "memory_limit=512M"; \
      echo "upload_max_filesize=64M"; \
      echo "post_max_size=64M"; \
      echo "max_execution_time=120"; \
      echo "opcache.enable=1"; \
      echo "opcache.validate_timestamps=1"; \
      echo "opcache.memory_consumption=128"; \
    } > /usr/local/etc/php/conf.d/dolibarr.ini

RUN docker-php-ext-install calendar

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends libc-client-dev libkrb5-dev; \
    rm -rf /var/lib/apt/lists/*; \
    docker-php-ext-configure imap --with-kerberos --with-imap-ssl; \
    docker-php-ext-install imap


# Ajuste de usuário/grupo para coincidir com o host
RUN groupmod -o -g ${APP_GID} www-data && usermod -o -u ${APP_UID} -g ${APP_GID} www-data
USER www-data

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --retries=5 \
  CMD curl -fsS http://localhost/index.php >/dev/null || exit 1
