
FROM php:8.2-apache

# Install required system packages and trusted CA certificates
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    openssl \
    libcurl4-openssl-dev \
    libssl-dev \
    unzip \
    git \
    && update-ca-certificates \
    && docker-php-ext-install curl \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite rules
RUN a2enmod rewrite

WORKDIR /var/www/html

# Copy application source
COPY . /var/www/html/

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Install PHP dependencies, including mongodb/mongodb
RUN if [ -f composer.json ]; then \
      composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader; \
    fi

# Configure Apache to listen on Render's default web port
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:10000>/' \
       /etc/apache2/sites-available/000-default.conf

EXPOSE 10000

CMD ["apache2-foreground"]
