FROM php:8.3-apache

# Instalar dependências do sistema
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

# Configurar Apache para a porta do Render
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:10000>/' /etc/apache2/sites-available/000-default.conf

# Diretório do projeto
WORKDIR /var/www/html

# Copiar arquivos
COPY . /var/www/html

# Instalar dependências
RUN composer install --no-dev --optimize-autoloader

# Permitir execução do Apache
EXPOSE 10000

CMD ["apache2-foreground"]