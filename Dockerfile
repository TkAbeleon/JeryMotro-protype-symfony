FROM php:8.3-cli

# Install system dependencies needed for Symfony
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Install and configure PHP extensions
RUN docker-php-ext-configure intl \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql intl opcache zip mbstring dom xml

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Setup user for Hugging Face (UID 1000 is required)
RUN useradd -m -u 1000 hfuser
USER hfuser

# Define home and workdir
ENV HOME=/home/hfuser \
    PATH=/home/hfuser/.local/bin:$PATH \
    APP_ENV=prod \
    APP_DEBUG=0 \
    COMPOSER_ALLOW_SUPERUSER=0 \
    COMPOSER_MEMORY_LIMIT=-1

# Build-time environment variables
# We do NOT set DATABASE_URL here to ensure it's unresolved during build-time compilation
ARG APP_SECRET="dummy_secret_for_build_phase"

WORKDIR $HOME/app

# Copy application files with appropriate ownership
COPY --chown=hfuser:hfuser . $HOME/app/

# Ensure var directories exist and have proper permissions
RUN mkdir -p var/cache var/log && \
    chmod -R 777 var/

# Setup a fallback .env and FORCE prod environment
# CRITICAL: We completely remove DATABASE_URL and secrets from the image to ensure runtime secrets are used.
RUN if [ ! -f .env ]; then cp .env.example .env; fi && \
    sed -i 's/APP_ENV=dev/APP_ENV=prod/g' .env && \
    sed -i 's/APP_DEBUG=true/APP_DEBUG=false/g' .env && \
    sed -i '/DATABASE_URL=/d' .env && \
    sed -i '/APP_SECRET=/d' .env && \
    echo "DEFAULT_URI=http://localhost" >> .env

# Install PHP dependencies without dev packages
# We do NOT run scripts here because they might require DATABASE_URL
RUN APP_SECRET=$APP_SECRET \
    composer install --no-interaction --optimize-autoloader --no-dev --ignore-platform-reqs --no-scripts

# We skip cache:warmup during the build to prevent baking dummy configuration.
# The container will be compiled at runtime with the real Hugging Face Secrets.

EXPOSE 7860

# Run Symfony builtin server
CMD ["php", "-S", "0.0.0.0:7860", "-t", "public"]
