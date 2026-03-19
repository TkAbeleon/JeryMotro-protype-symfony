##
## JeryMotro Platform — Makefile
## Usage : make <commande>
##

.PHONY: help install migrate fixtures start test clean

# Couleurs
GREEN  = \033[0;32m
YELLOW = \033[0;33m
RESET  = \033[0m

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-18s$(RESET) %s\n", $$1, $$2}'

## ─── Installation ─────────────────────────────────────────────

install: ## Installer les dépendances Composer
	@echo "$(YELLOW)→ Installation des dépendances...$(RESET)"
	composer install --no-interaction
	@echo "$(GREEN)✓ Dépendances installées$(RESET)"

setup: ## Setup complet : install + migration
	cp -n .env.example .env || true
	@echo "$(YELLOW)⚠  Pensez à remplir DATABASE_URL et N8N_WEBHOOK_URL dans .env$(RESET)"
	$(MAKE) install
	$(MAKE) migrate

## ─── Base de données ──────────────────────────────────────────

migrate: ## Appliquer les migrations Doctrine
	@echo "$(YELLOW)→ Migration BDD...$(RESET)"
	php bin/console doctrine:migrations:migrate --no-interaction
	@echo "$(GREEN)✓ Migration appliquée$(RESET)"

migrate-diff: ## Générer une nouvelle migration depuis les entités
	php bin/console doctrine:migrations:diff

fixtures: ## Charger les données de test (users)
	php bin/console doctrine:fixtures:load --no-interaction

schema-validate: ## Valider le mapping Doctrine
	php bin/console doctrine:schema:validate

## ─── Serveur ──────────────────────────────────────────────────

start: ## Démarrer le serveur de dev Symfony
	symfony server:start --no-tls

start-php: ## Démarrer avec le serveur PHP intégré (sans symfony CLI)
	php -S 0.0.0.0:8000 -t public/

## ─── Qualité ──────────────────────────────────────────────────

cache-clear: ## Vider le cache Symfony
	php bin/console cache:clear

routes: ## Lister toutes les routes
	php bin/console debug:router

env-check: ## Vérifier les variables d'environnement
	php bin/console debug:dotenv

## ─── Nettoyage ────────────────────────────────────────────────

clean: ## Vider le cache et les logs
	rm -rf var/cache/* var/log/*
	@echo "$(GREEN)✓ Cache et logs nettoyés$(RESET)"
