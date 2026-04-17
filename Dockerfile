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
    && docker-php-ext-install pdo_pgsql intl opcache zip mbstring dom xml

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

# Dummy env vars to satisfy container compilation during build phase
ENV DATABASE_URL="postgresql://db_user:db_password@127.0.0.1:5432/db_name?serverVersion=16&charset=utf8" \
    APP_SECRET="dummy_secret_for_build_phase" \
    N8N_WEBHOOK_URL="https://dummy.n8n.webhook"

WORKDIR $HOME/app

# Copy application files with appropriate ownership
COPY --chown=hfuser:hfuser . $HOME/app/

# Ensure var directories exist and have proper permissions
RUN mkdir -p var/cache var/log && \
    chmod -R 777 var/

# Setup a fallback .env and FORCE prod environment
# We use sed to ensure APP_ENV is prod and APP_DEBUG is false, 
# preventing .env.example values from causing a crash by loading dev bundles
RUN if [ ! -f .env ]; then cp .env.example .env; fi && \
    sed -i 's/APP_ENV=dev/APP_ENV=prod/g' .env && \
    sed -i 's/APP_DEBUG=true/APP_DEBUG=false/g' .env

# Install PHP dependencies without dev packages
RUN composer install --no-interaction --optimize-autoloader --no-dev --ignore-platform-reqs --no-scripts

# Manual cache warmup (suppress errors during build)
RUN php bin/console cache:warmup --env=prod || true

EXPOSE 7860

# Run Symfony builtin server
CMD ["php", "-S", "0.0.0.0:7860", "-t", "public"]
