
FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    libssl-dev \
    libzip-dev \
    unzip \
    git \
    ca-certificates \
    && docker-php-ext-install curl \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite rules
RUN a2enmod rewrite

# Set the web root
ENV APACHE_DOCUMENT_ROOT=/var/www/html

WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Install Composer dependencies if composer.json exists
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN if [ -f composer.json ]; then composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader; fi

# Ensure Apache listens on Render's port
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:10000>/' /etc/apache2/sites-available/000-default.conf

EXPOSE 10000

CMD ["apache2-foreground"]
