FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath

# Install Composer without relying on Docker Hub (avoids 503 on composer:latest)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Create basic .env file for build process
RUN cp .env.example .env || echo "APP_NAME=SkillsXchangee\nAPP_ENV=production\nAPP_KEY=\nAPP_DEBUG=false" > .env

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install npm dependencies with retry and increased timeout
RUN npm config set fetch-retries 5 && \
    npm config set fetch-retry-mintimeout 20000 && \
    npm config set fetch-retry-maxtimeout 120000 && \
    npm install --timeout=300000 || (echo "npm install failed, retrying..." && npm install --timeout=300000)

# Build assets (skip if build fails)
RUN npm run build || echo "Asset build failed, continuing with fallback CSS"

# Ensure Firebase files are accessible
RUN chmod 644 public/firebase-config.js public/firebase-video-integration.js public/firebase-video-call.js

# Application key will be generated at runtime in start.sh

# Expose port
EXPOSE $PORT

# Make start script executable
RUN chmod +x start.sh

# Start the application
CMD ./start.sh
