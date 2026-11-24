FROM php:8.3-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && a2enmod headers \
    && rm -rf /var/lib/apt/lists/*

RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/apache2.conf

COPY . /var/www/html

RUN printf '%s\n' '<?php header("Content-Type: application/json; charset=utf-8"); echo json_encode(["service" => "MDRRMO Incident Reporting API", "status" => "ok"], JSON_UNESCAPED_SLASHES);' \
    > /var/www/html/public/index.php \
    && chown -R www-data:www-data /var/www/html

EXPOSE 10000
CMD ["sh", "-c", "set -eu; port=\"${PORT:-10000}\"; case \"$port\" in ''|*[!0-9]*) echo 'PORT must be numeric.' >&2; exit 1;; esac; sed -ri \"s/^Listen 80$/Listen ${port}/\" /etc/apache2/ports.conf; sed -ri \"s/<VirtualHost [*]:80>/<VirtualHost *:${port}>/\" /etc/apache2/sites-available/000-default.conf; php -r 'require \"/var/www/html/src/database.php\"; initialize_database();'; exec apache2-foreground"]