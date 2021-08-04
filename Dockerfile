FROM php:8.0-apache

RUN apt-get update \
    && apt-get install -y \
        git \
        # zip
        libzip-dev \
        # gd
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev

# Nouvelle syntaxe pour les paramètres (cf. https://github.com/docker-library/php/issues/945)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

RUN docker-php-ext-install zip gd

COPY run.sh /usr/paysdufle.fr/run.sh
RUN chmod +x /usr/paysdufle.fr/run.sh

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

ENTRYPOINT [ "/usr/paysdufle.fr/run.sh" ]