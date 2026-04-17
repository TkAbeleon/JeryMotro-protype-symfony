---
title: JeryMotro Platform
emoji: 🌍
colorFrom: red
colorTo: yellow
sdk: docker
app_port: 7860
app_file: Dockerfile
pinned: false
---

# 🔥 JeryMotro Platform (Symfony Prototype)

Bienvenue sur le repository du prototype Symfony de la plateforme **JeryMotro**.

**JeryMotro** est une plateforme de télédétection spatiale avancée, dédiée à la surveillance en temps réel des feux de brousse à Madagascar. Ce prototype démontre l'intégration de capacités d'analyse de données (satellites MODIS/VIIRS), d'une interface de tableau de bord moderne et d'un assistant IA.

🌍 **Accéder à l'application en direct** : [JeryMotro sur Hugging Face Spaces](https://rtsikynyantsa-jerymotro-platform-symfony.hf.space)

---

## 🏗️ Architecture Technique

- **Backend:** Symfony 7.2 (PHP 8.3)
- **Base de données:** PostgreSQL (hébergé sur Supabase)
- **Modèle IA (Agent):** Mistral RAG (Intégration via API)
- **Orchestration / Alertes:** Pipeline automatisé via **n8n**
- **Déploiement:** Dockerisé & Hébergé sur **Hugging Face Spaces**

## ✨ Fonctionnalités Clés & UI/UX

L'interface de JeryMotro a été optimisée pour offrir une expérience utilisateur (UX) digne d'un centre de contrôle cyber-analytique professionnel :

- **Dashboard "Cyber-Analytique"** : Interface en thème sombre profond avec cartes vitrées (Glassmorphism), effets de lueur néon dynamiques (Glow effects) et animations fluides.
- **Visualisation de Données** : Intégration de graphiques *Chart.js* (Barres et Anneaux) entièrement customisés avec infobulles immersives interactives et animations d'échelle (easeOutQuart).
- **Responsive "Mobile-First"** : Menu de navigation à basculement "Off-canvas" avec fond de superposition (backdrop), tableaux de données à défilement horizontal (`.table-responsive-dark`), typographie fluide (`clamp()`).
- **Écran de Chargement Global (SPA-Feel)** : Un "SplashScreen" global animé par un indicateur de rythme et une barre de chargement continue masque les rechargements de pages Symfony, procurant une fluidité digne d'une Single Page Application (SPA).
- **Agent IA Intégré** : Interface de chat minimaliste en mode pleine hauteur ("flush layout") facilitant l'interaction avec le modèle n8n.

## 🚀 Guide de Déploiement (Hugging Face)

Ce repository est pré-configuré pour un déploiement "Zero-Config" sur **Hugging Face Spaces**. Le moteur de configuration en tête de ce `README.md` communique automatiquement avec les serveurs Hugging Face.

1. L'application est servie via un `Dockerfile` personnalisé avec **PHP 8.3 Development Server**.
2. Un script d'initialisation (`entrypoint.sh`) configure de manière dynamique les variables d'environnement telles que la `DATABASE_URL` pour prévenir les erreurs d'injection de secrets.
3. Le port réseau exposé et attendu par l'orchestrateur est le **7860**.

## 🛠️ Installation Locale

1. Clonez ce repository.
2. Assurez-vous d'avoir PHP 8.3 et Composer installés.
3. Copiez `.env` vers `.env.local` et configurez votre `DATABASE_URL` (Supabase).
4. Lancez le serveur local : 
```bash
symfony server:start
```

---
*Développé pour la surveillance et l'alerte précoce des feux à Madagascar. (Mémoire L3 — Génie Logiciel 2026).*
