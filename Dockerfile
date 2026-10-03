FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y ca-certificates \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite expires deflate headers \
    && rm -rf /var/lib/apt/lists/*

# hide the php version and apache details
RUN echo "expose_php = Off" > /usr/local/etc/php/conf.d/security.ini \
    && printf "ServerTokens Prod\nServerSignature Off\n" > /etc/apache2/conf-available/zz-security.conf \
    && a2enconf zz-security

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
