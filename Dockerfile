FROM php:8.3-apache

# Instalar dependências necessárias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libssl-dev \
    pkg-config \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Definir pasta do sistema
WORKDIR /var/www/html

# Copiar arquivos do projeto
COPY . /var/www/html

# Instalar dependências PHP
RUN composer install --no-dev --optimize-autoloader

# Configurar Apache
RUN a2enmod rewrite

EXPOSE 80
