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

# JeryMotro Platform (Symfony Prototype)

Bienvenue sur le repository du prototype Symfony de la plateforme **JeryMotro**.

Ce repository est configuré pour se déployer automatiquement sur Hugging Face Spaces via Docker. 
Le fichier `Dockerfile` à la racine prend en charge l'installation de PHP 8.3, d'Apache (sur le port 7860), ainsi que des extensions nécessaires.

## Architecture 
- **Backend:** Symfony 7.2
- **Base de données:** PostgreSQL (via Supabase)
- **Modèle IA (Agent):** Mistral RAG
- **Orchestration:** n8n Pipeline
