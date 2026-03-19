<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration initiale JeryMotro Platform.
 *
 * Crée :
 *   - users          → destinataires des alertes
 *   - alerts         → liaison user ↔ firms_fire_detections
 *   - keepalive      → ping anti-veille Supabase
 *
 * ⚠️ NE TOUCHE PAS firms_fire_detections (gérée par le pipeline n8n FIRMS).
 *    Le schema_filter dans doctrine.yaml exclut cette table automatiquement.
 */
final class Version20260301000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables users, alerts et keepalive (JeryMotro Platform)';
    }

    public function up(Schema $schema): void
    {
        // ─── TABLE USERS ──────────────────────────────────────────────────────
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS users (
                id          BIGSERIAL        PRIMARY KEY,
                email       VARCHAR(255)     NOT NULL,
                name        VARCHAR(100)     NOT NULL,
                phone       VARCHAR(20),
                created_at  TIMESTAMPTZ      NOT NULL DEFAULT NOW(),
                updated_at  TIMESTAMPTZ      NOT NULL DEFAULT NOW(),
                CONSTRAINT uq_users_email UNIQUE (email)
            )
        SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_users_email      ON users(email)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_users_created_at ON users(created_at DESC)');

        // Trigger updated_at automatique
        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION update_users_updated_at()
            RETURNS TRIGGER AS $$
            BEGIN NEW.updated_at = NOW(); RETURN NEW; END;
            $$ LANGUAGE plpgsql
        SQL);

        // ✅ CORRECTION ICI : séparation des requêtes
        $this->addSql('DROP TRIGGER IF EXISTS trg_users_updated_at ON users');

        $this->addSql(<<<'SQL'
            CREATE TRIGGER trg_users_updated_at
                BEFORE UPDATE ON users
                FOR EACH ROW EXECUTE FUNCTION update_users_updated_at()
        SQL);

        // ─── TABLE ALERTS ─────────────────────────────────────────────────────
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS alerts (
                id              BIGSERIAL    PRIMARY KEY,
                user_id         BIGINT       NOT NULL
                                    REFERENCES users(id) ON DELETE CASCADE,
                detection_id    BIGINT
                                    REFERENCES firms_fire_detections(id) ON DELETE SET NULL,
                message         TEXT         NOT NULL,
                status          VARCHAR(20)  NOT NULL DEFAULT 'pending'
                                    CHECK (status IN ('pending', 'sent', 'failed')),
                sent_at         TIMESTAMPTZ,
                created_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW()
            )
        SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_alerts_user_id      ON alerts(user_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_alerts_detection_id ON alerts(detection_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_alerts_status       ON alerts(status)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_alerts_created_at   ON alerts(created_at DESC)');

        // ─── TABLE KEEPALIVE ──────────────────────────────────────────────────
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS keepalive (
                id        BIGSERIAL   PRIMARY KEY,
                pinged_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        // Ordre inverse pour respecter les FK
        $this->addSql('DROP TABLE IF EXISTS alerts    CASCADE');
        $this->addSql('DROP TABLE IF EXISTS users     CASCADE');
        $this->addSql('DROP TABLE IF EXISTS keepalive CASCADE');
        $this->addSql('DROP FUNCTION IF EXISTS update_users_updated_at()');
    }
}
