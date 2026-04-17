#!/bin/sh
# Diagnostic startup script for Hugging Face deployment

DB_HOST=$(echo "${DATABASE_URL:-NOT_SET}" | sed 's/.*@//' | cut -d/ -f1 | cut -d? -f1 | cut -d: -f1)

echo "============================================"
echo "[STARTUP] APP_ENV = ${APP_ENV:-NOT_SET}"
echo "[STARTUP] DATABASE_URL is set: $([ -n "$DATABASE_URL" ] && echo YES || echo NO)"
echo "[STARTUP] DB host: ${DB_HOST}"
echo "============================================"

# CRITICAL: Write DATABASE_URL to .env file before PHP starts.
# This ensures Symfony's Dotenv component reads it and populates $_ENV properly,
# regardless of PHP's variables_order setting.
if [ -n "$DATABASE_URL" ]; then
    echo "DATABASE_URL=${DATABASE_URL}" >> /home/hfuser/app/.env
    echo "[STARTUP] DATABASE_URL written to .env"
fi

if [ -n "$APP_SECRET" ]; then
    echo "APP_SECRET=${APP_SECRET}" >> /home/hfuser/app/.env
fi

if [ -n "$N8N_WEBHOOK_URL" ]; then
    echo "N8N_WEBHOOK_URL=${N8N_WEBHOOK_URL}" >> /home/hfuser/app/.env
fi

if [ -n "$N8N_AGENT_WEBHOOK_URL" ]; then
    echo "N8N_AGENT_WEBHOOK_URL=${N8N_AGENT_WEBHOOK_URL}" >> /home/hfuser/app/.env
fi

if [ -n "$HF_TOKEN" ]; then
    echo "HF_TOKEN=${HF_TOKEN}" >> /home/hfuser/app/.env
fi

echo "[STARTUP] Starting PHP server on port 7860..."
exec php -S 0.0.0.0:7860 -t public
