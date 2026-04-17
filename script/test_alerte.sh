#!/bin/bash

# --- CONFIGURATION ---
# Remplace par ton vrai token Hugging Face (disponible dans Settings > Access Tokens)
HF_TOKEN="${HF_TOKEN}"
WEBHOOK_URL="https://rtsikynyantsa-jerymotro-pipeline.hf.space/webhook-test/madfire-alert"

echo "Envoi de la requête à JeryMotro Platform..."

# Exécution de curl et capture de la réponse
RESPONSE=$(curl -s -X POST "$WEBHOOK_URL" \
     -H "Authorization: Bearer $HF_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{
           "alert_id": "ALT-2026-089",
           "email": "rtsikynyantsa@gmail.com",
           "name": "Jury de Soutenance",
           "message": "Une anomalie thermique a été détectée dans la région du Vakinankaratra.\nMerci de vérifier les images satellites correspondantes.",
           "detection": {
             "id": "MODIS-90210",
             "risk_label": "Critique",
             "source": "NASA FIRMS",
             "satellite": "Aqua",
             "latitude": -19.8659,
             "longitude": 47.0333,
             "frp": 142.5,
             "acq_datetime": "2026-04-16T17:45:00Z"
           }
         }')

echo "--------------------------------------"
echo "RÉPONSE DU SERVEUR :"
echo "$RESPONSE"
echo "--------------------------------------"

echo -e "\nTerminé. Vérifiez l'interface n8n pour voir l'exécution."
