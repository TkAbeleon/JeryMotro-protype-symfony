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
    APP_DEBUG=0

WORKDIR $HOME/app

# Copy application files with appropriate ownership
COPY --chown=hfuser:hfuser . $HOME/app/

# Ensure var directories exist and have proper permissions
RUN mkdir -p var/cache var/log && \
    chmod -R 777 var/

# Setup a fallback .env if missing (important for build scripts)
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# Install PHP dependencies without dev packages
RUN composer install --no-interaction --optimize-autoloader --no-dev --ignore-platform-reqs

# Prepare cache
RUN php bin/console cache:warmup || true

EXPOSE 7860

# Run Symfony builtin server
CMD ["php", "-S", "0.0.0.0:7860", "-t", "public"]
