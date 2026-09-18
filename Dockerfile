FROM php:8.3-apache

# Extension nécessaire pour la base de données SQLite
RUN docker-php-ext-install pdo pdo_sqlite

RUN a2enmod rewrite

WORKDIR /var/www/html
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && chmod +x /var/www/html/docker-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
