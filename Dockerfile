# Escolha a versão do PHP sem trocar o FROM
ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-apache

# UID/GID para evitar arquivos root no host
ARG APP_UID=1000
ARG APP_GID=1000

# Pacotes de build/runtime
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      git curl unzip \
      libpng-dev libjpeg-dev libfreetype6-dev \
      libzip-dev zlib1g-dev \
      libxml2-dev libicu-dev \
      libpq-dev default-mysql-client \
      libc-client2007e-dev libkrb5-dev \
    ; \
    rm -rf /var/lib/apt/lists/*

# Extensões PHP necessárias ao Dolibarr
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
 && docker-php-ext-install -j"$(nproc)" \
      gd zip intl calendar \
      mysqli pdo pdo_mysql \
      pgsql pdo_pgsql \
      imap opcache \
 && docker-php-ext-enable mysqli pgsql


# Apache: habilitar módulos e apontar para /var/www/html/htdocs
RUN a2enmod rewrite headers expires \
 && sed -ri 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/htdocs#g' /etc/apache2/sites-available/000-default.conf \
 && { \
      echo '<Directory "/var/www/html/htdocs">'; \
      echo '  AllowOverride All'; \
      echo '  Require all granted'; \
      echo '</Directory>'; \
    } > /etc/apache2/conf-available/dolibarr.conf \
 && a2enconf dolibarr

# PHP.ini básico
RUN { \
      echo "memory_limit=512M"; \
      echo "upload_max_filesize=256M"; \
      echo "post_max_size=256M"; \
      echo "max_execution_time=120"; \
      echo "date.timezone=America/Sao_Paulo"; \
      echo "opcache.enable=1"; \
      echo "opcache.validate_timestamps=1"; \  
      echo "opcache.memory_consumption=128"; \
      echo "opcache.interned_strings_buffer=16"; \
      echo "opcache.max_accelerated_files=10000"; \
      echo "realpath_cache_size=4096K"; \
      echo "realpath_cache_ttl=600"; \
    } > /usr/local/etc/php/conf.d/dolibarr.ini

# Ajuste de usuário/grupo para casar com o host
RUN groupmod -o -g ${APP_GID} www-data && usermod -o -u ${APP_UID} -g ${APP_GID} www-data
USER www-data

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --retries=5 \
  CMD curl -fsS http://localhost/index.php >/dev/null || exit 1
