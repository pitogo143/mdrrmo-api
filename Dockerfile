FROM php:8.3-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && a2enmod headers \
    && rm -rf /var/lib/apt/lists/*

RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/apache2.conf

COPY . /var/www/html
COPY docker-entrypoint.sh /usr/local/bin/mdrrmo-entrypoint

RUN chmod +x /usr/local/bin/mdrrmo-entrypoint \
    && chown -R www-data:www-data /var/www/html

EXPOSE 10000
ENTRYPOINT ["/usr/local/bin/mdrrmo-entrypoint"]
