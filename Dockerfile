FROM php:8.2-cli
RUN apt-get update \
      && apt-get install -y libzip-dev zip git \
      && docker-php-ext-install zip
COPY --from=composer/composer:latest-bin /composer /usr/bin/composer
WORKDIR /app
