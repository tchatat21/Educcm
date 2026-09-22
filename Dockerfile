FROM php:8.2-cli

# Installer les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libzip-dev \
    libcurl4-openssl-dev \
    zip \
    unzip \
    && docker-php-ext-install mysqli pdo pdo_mysql gd zip curl \
    && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier les fichiers du projet
WORKDIR /app
COPY . /app/

# Installer les dépendances PHP (PHPMailer, dompdf)
RUN composer install --no-dev --optimize-autoloader

# Permissions sur les dossiers uploads
RUN mkdir -p /app/uploads/justificatifs /app/uploads/photos \
    && chmod -R 755 /app/uploads

EXPOSE 80

CMD ["php", "-S", "0.0.0.0:80", "-t", "/app"]
