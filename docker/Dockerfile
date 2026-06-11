FROM php:8.4-fpm

# Instalar dependencias del sistema y herramientas necesarias
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    libonig-dev \
    libpq-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Instalar y configurar extensiones PHP requeridas por Laravel y PostgreSQL
RUN docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    bcmath \
    gd \
    zip \
    opcache

# Instalar la extensión de Redis para PHP
RUN pecl install redis \
    && docker-php-ext-enable redis

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar el usuario de trabajo para evitar problemas de permisos de archivos
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# Configurar el directorio de trabajo
WORKDIR /var/www

# Cambiar el usuario actual a www-data
USER www-data

EXPOSE 9000

CMD ["php-fpm"]
