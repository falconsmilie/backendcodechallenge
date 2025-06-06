FROM php:8.4-apache

# Install PHP extensions: pdo, pdo_mysql, mbstring
RUN apt-get update && apt-get install -y \
    libonig-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy the app
COPY . /var/www/html

# Set Apache DocumentRoot to public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

# Allow access to public folder
RUN echo '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf
