FROM composer:2.5.7 as composer


COPY . /app
WORKDIR /app

RUN composer install

FROM php:8.1-apache

COPY ./config/apache_vhost.conf /etc/apache2/sites-available/000-default.conf
COPY --from=composer --chown=www-data:www-data /app /var/www/html
