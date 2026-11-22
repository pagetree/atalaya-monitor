FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && (a2dismod -f mpm_event mpm_worker || true) \
    && a2enmod mpm_prefork \
    && rm -rf /var/lib/apt/lists/*

COPY docker/start.sh /usr/local/bin/start.sh
RUN sed -i 's/\r$//' /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

COPY . /var/www/html

CMD ["start.sh"]
