#!/bin/sh
# Diagnostic startup script for Hugging Face deployment

DB_HOST=$(echo "${DATABASE_URL:-NOT_SET}" | sed 's/.*@//' | cut -d/ -f1 | cut -d? -f1 | cut -d: -f1)

echo "============================================"
echo "[STARTUP] APP_ENV = ${APP_ENV:-NOT_SET}"
echo "[STARTUP] DATABASE_URL is set: $([ -n "$DATABASE_URL" ] && echo YES || echo NO)"
echo "[STARTUP] DB host: ${DB_HOST}"
echo "============================================"

# Write all HF secrets to .env before PHP starts
if [ -n "$DATABASE_URL" ]; then
    echo "DATABASE_URL=${DATABASE_URL}" >> /home/hfuser/app/.env
    echo "[STARTUP] DATABASE_URL written to .env"
fi
if [ -n "$APP_SECRET" ]; then echo "APP_SECRET=${APP_SECRET}" >> /home/hfuser/app/.env; fi
if [ -n "$N8N_WEBHOOK_URL" ]; then echo "N8N_WEBHOOK_URL=${N8N_WEBHOOK_URL}" >> /home/hfuser/app/.env; fi
if [ -n "$N8N_AGENT_WEBHOOK_URL" ]; then echo "N8N_AGENT_WEBHOOK_URL=${N8N_AGENT_WEBHOOK_URL}" >> /home/hfuser/app/.env; fi
if [ -n "$HF_TOKEN" ]; then echo "HF_TOKEN=${HF_TOKEN}" >> /home/hfuser/app/.env; fi

# ────────────────────────────────────────────────────────────────
# DIRECT PHP CONNECTION TEST — bypass Doctrine/Symfony entirely
# This tells us exactly what DSN is built and what error PHP gives
# ────────────────────────────────────────────────────────────────
echo "[STARTUP] Testing raw PDO connection..."
php -r "
\$url = getenv('DATABASE_URL');
\$parts = parse_url(\$url);
\$host    = \$parts['host'] ?? '';
\$port    = \$parts['port'] ?? 5432;
\$dbname  = ltrim(\$parts['path'] ?? '/postgres', '/');
\$user    = urldecode(\$parts['user'] ?? '');
\$pass    = urldecode(\$parts['pass'] ?? '');
\$dsn = 'pgsql:host=' . \$host . ';port=' . \$port . ';dbname=' . \$dbname . ';sslmode=require';
echo '[PDO-TEST] DSN: ' . \$dsn . PHP_EOL;
echo '[PDO-TEST] User: ' . \$user . PHP_EOL;
echo '[PDO-TEST] pdo_pgsql loaded: ' . (extension_loaded(\"pdo_pgsql\") ? 'YES' : 'NO') . PHP_EOL;
try {
    \$pdo = new PDO(\$dsn, \$user, \$pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]);
    echo '[PDO-TEST] Connection OK!' . PHP_EOL;
} catch (Exception \$e) {
    echo '[PDO-TEST] Connection FAILED: ' . \$e->getMessage() . PHP_EOL;
}
"

echo "[STARTUP] Starting PHP server on port 7860..."
exec php -S 0.0.0.0:7860 -t public
