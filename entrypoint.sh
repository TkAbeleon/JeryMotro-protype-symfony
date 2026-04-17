#!/bin/sh
# Diagnostic startup script for Hugging Face deployment
# Shows DB host (without password) to verify HF secret injection

DB_HOST=$(echo "${DATABASE_URL:-NOT_SET}" | sed 's/.*@//' | cut -d/ -f1 | cut -d? -f1 | cut -d: -f1)

echo "============================================"
echo "[STARTUP] APP_ENV = ${APP_ENV:-NOT_SET}"
echo "[STARTUP] DATABASE_URL is set: $([ -n "$DATABASE_URL" ] && echo YES || echo NO)"
echo "[STARTUP] DB host: ${DB_HOST}"
echo "============================================"

exec php -S 0.0.0.0:7860 -t public
