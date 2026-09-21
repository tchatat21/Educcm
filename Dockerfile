FROM php:8.2-apache

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Activer mod_rewrite Apache
RUN a2enmod rewrite

# Installer les dépendances système pour GD et ZIP
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libzip-dev \
    libcurl4-openssl-dev \
    zip \
    unzip \
    && docker-php-ext-install gd zip curl \
    && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier les fichiers du projet
COPY . /var/www/html/

# Installer les dépendances PHP (PHPMailer, dompdf)
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exposer le port
EXPOSE 80
