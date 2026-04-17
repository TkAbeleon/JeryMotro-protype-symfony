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

# ALWAYS generate a fresh .env with production defaults
# We NEVER use the committed .env because it may contain dev settings including DATABASE_URL pointing to localhost
# The DATABASE_URL is NOT set here — HF Secret injects it as a real OS env var at runtime
RUN printf "APP_ENV=prod\nAPP_DEBUG=false\nDEFAULT_URI=http://localhost\n" > .env && \
    echo "" >> .env

# Install PHP dependencies without dev packages
# We do NOT run scripts here because they might require DATABASE_URL
RUN APP_SECRET=$APP_SECRET \
    composer install --no-interaction --optimize-autoloader --no-dev --ignore-platform-reqs --no-scripts

# We skip cache:warmup during the build to prevent baking dummy configuration.
# The container will be compiled at runtime with the real Hugging Face Secrets.

# Copy entrypoint script
COPY --chown=hfuser:hfuser entrypoint.sh /home/hfuser/entrypoint.sh
RUN chmod +x /home/hfuser/entrypoint.sh

EXPOSE 7860

# Use entrypoint for diagnostics + server start
CMD ["/home/hfuser/entrypoint.sh"]
