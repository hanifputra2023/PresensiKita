FROM php:8.1-apache

# Install ekstensi PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Aktifkan mod_rewrite DAN mod_headers (PENTING!)
RUN a2enmod rewrite headers