# Use official PHP image with Apache
FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy backend code
COPY . .

# Install Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions (adjust as needed)
RUN chown -R www-data:www-data /var/www/html

# Create startup script without .env copy
RUN echo '#!/bin/bash' > /usr/local/bin/start.sh && \
    echo 'cd /var/www/html' >> /usr/local/bin/start.sh && \
    echo 'echo "Running setup script..."' >> /usr/local/bin/start.sh && \
    echo 'php setup.php' >> /usr/local/bin/start.sh && \
    echo 'echo "Starting Apache..."' >> /usr/local/bin/start.sh && \
    echo 'apache2-foreground' >> /usr/local/bin/start.sh && \
    chmod +x /usr/local/bin/start.sh

# Create .env file from environment variables
RUN echo '#!/bin/bash' > /usr/local/bin/create-env.sh && \
    echo 'cat > /var/www/html/.env << EOF' >> /usr/local/bin/create-env.sh && \
    echo 'DB_HOST=${DB_HOST:-localhost}' >> /usr/local/bin/create-env.sh && \
    echo 'DB_PORT=${DB_PORT:-5432}' >> /usr/local/bin/create-env.sh && \
    echo 'DB_USERNAME=${DB_USERNAME:-postgres}' >> /usr/local/bin/create-env.sh && \
    echo 'DB_PASSWORD=${DB_PASSWORD:-your_password}' >> /usr/local/bin/create-env.sh && \
    echo 'DB_DATABASE=${DB_DATABASE:-task_management}' >> /usr/local/bin/create-env.sh && \
    echo 'MAIL_FROM=${MAIL_FROM:-your-email@gmail.com}' >> /usr/local/bin/create-env.sh && \
    echo 'MAIL_FROM_NAME=${MAIL_FROM_NAME:-Task Manager}' >> /usr/local/bin/create-env.sh && \
    echo 'SMTP_HOST=${SMTP_HOST:-smtp.gmail.com}' >> /usr/local/bin/create-env.sh && \
    echo 'SMTP_PORT=${SMTP_PORT:-587}' >> /usr/local/bin/create-env.sh && \
    echo 'SMTP_USERNAME=${SMTP_USERNAME:-your-email@gmail.com}' >> /usr/local/bin/create-env.sh && \
    echo 'SMTP_PASSWORD=${SMTP_PASSWORD:-your-app-password}' >> /usr/local/bin/create-env.sh && \
    echo 'APP_ENV=${APP_ENV:-development}' >> /usr/local/bin/create-env.sh && \
    echo 'APP_DEBUG=${APP_DEBUG:-true}' >> /usr/local/bin/create-env.sh && \
    echo 'EOF' >> /usr/local/bin/create-env.sh && \
    chmod +x /usr/local/bin/create-env.sh

# Create .env file from environment variables
RUN echo '#!/bin/bash' > /usr/local/bin/create-env.sh && \
    echo 'cat > /var/www/html/.env << EOF' >> /usr/local/bin/create-env.sh && \
    echo 'DB_HOST=${DB_HOST:-localhost}' >> /usr/local/bin/create-env.sh && \
    echo 'DB_PORT=${DB_PORT:-5432}' >> /usr/local/bin/create-env.sh && \
    echo 'DB_USERNAME=${DB_USERNAME:-postgres}' >> /usr/local/bin/create-env.sh && \
    echo 'DB_PASSWORD=${DB_PASSWORD:-dbpassword}' >> /usr/local/bin/create-env.sh && \
    echo 'DB_DATABASE=${DB_DATABASE:-task_management}' >> /usr/local/bin/create-env.sh && \
    echo 'MAIL_FROM=${MAIL_FROM:-wanjui.dev@gmail.com}' >> /usr/local/bin/create-env.sh && \
    echo 'MAIL_FROM_NAME=${MAIL_FROM_NAME:-Task Manager}' >> /usr/local/bin/create-env.sh && \
    echo 'SMTP_HOST=${SMTP_HOST:-smtp.gmail.com}' >> /usr/local/bin/create-env.sh && \
    echo 'SMTP_PORT=${SMTP_PORT:-587}' >> /usr/local/bin/create-env.sh && \
    echo 'SMTP_USERNAME=${SMTP_USERNAME:-your-email@gmail.com}' >> /usr/local/bin/create-env.sh && \
    echo 'SMTP_PASSWORD=${SMTP_PASSWORD:-your-app-password}' >> /usr/local/bin/create-env.sh && \
    echo 'APP_ENV=${APP_ENV:-development}' >> /usr/local/bin/create-env.sh && \
    echo 'APP_DEBUG=${APP_DEBUG:-true}' >> /usr/local/bin/create-env.sh && \
    echo 'EOF' >> /usr/local/bin/create-env.sh && \
    chmod +x /usr/local/bin/create-env.sh

# Expose port 80
EXPOSE 80

# Start with setup script
CMD ["/bin/bash", "-c", "/usr/local/bin/create-env.sh && /usr/local/bin/start.sh"]

