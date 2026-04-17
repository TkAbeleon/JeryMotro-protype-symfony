FROM php:8.3-cli

# 1. Installation des dépendances système nécessaires
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# 2. Installation des extensions PHP (PostgreSQL + Intl)
RUN docker-php-ext-configure intl \
    && docker-php-ext-install pdo_pgsql intl opcache

# 3. Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# 4. Configuration Utilisateur Hugging Face (UID 1000 OBLIGATOIRE)
# Hugging Face exige que le conteneur tourne sans privilèges root sous l'UID 1000
RUN useradd -m -u 1000 hfuser
USER hfuser

# 5. Définition du répertoire de travail et des chemins locaux
ENV HOME=/home/hfuser \
    PATH=/home/hfuser/.local/bin:$PATH
WORKDIR $HOME/app

# 6. Copie des fichiers de l'application avec les bonnes permissions
COPY --chown=hfuser:hfuser . $HOME/app/

# 7. Préparation de l'environnement (Fichier .env factice si manquant)
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# 8. Installation des dépendances Symfony (sans interaction)
RUN composer install --no-interaction --optimize-autoloader --no-dev --ignore-platform-reqs

# Nettoyage et préparation du cache pour Symfony
RUN php bin/console cache:clear --env=prod || true

# 9. Exposition du port Hugging Face par défaut
EXPOSE 7860

# 10. Commande de lancement (Serveur PHP embarqué pour contourner Apache)
CMD ["php", "-S", "0.0.0.0:7860", "-t", "public"]
