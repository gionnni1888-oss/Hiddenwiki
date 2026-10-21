FROM debian:bookworm-slim

RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    php-fpm \
    php-json \
    php-cli \
    tor \
    curl \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Crea directory
RUN mkdir -p /var/www/html /var/lib/tor/hiddenwiki /run/php /var/log/nginx

COPY nginx.conf /etc/nginx/sites-available/default
COPY torrc /etc/tor/torrc
COPY start.sh /start.sh
COPY config.php index.php admin.php data.json counter.php /var/www/html/

RUN chmod +x /start.sh \
    && chown -R www-data:www-data /var/www/html \
    && chmod 666 /var/www/html/data.json \
    && touch /var/www/html/visits.json \
    && chmod 666 /var/www/html/visits.json \
    && mkdir -p /var/www/html/uploads \
    && chmod 777 /var/www/html/uploads

EXPOSE 80

CMD ["/start.sh"]
