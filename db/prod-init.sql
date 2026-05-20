-- Production schema. Idempotent — safe to run any number of times via
-- phpMyAdmin (or `mysql -u USER -p DBNAME < db/prod-init.sql` if the host
-- allows it). Every CREATE uses IF NOT EXISTS, so re-running on a database
-- that already has these tables is a harmless no-op.
--
-- Differs from db/init.sql in two ways:
--   1. No DROP TABLE statements — never wipes existing data.
--   2. No test fixtures — those are E2E-only and would create dead
--      /sesja/test-no-pwd and /sesja/test-with-pwd URLs in production.
--
-- IF NOT EXISTS only guards against the table already existing; it does not
-- migrate a table whose columns differ from the definition below. This script
-- creates the initial schema — it is not a migration tool.

CREATE TABLE IF NOT EXISTS session (
    id          VARCHAR(36)  NOT NULL PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    url         VARCHAR(512) NOT NULL,
    description TEXT,
    file_names  TEXT,
    password    VARCHAR(255) DEFAULT NULL,
    email       VARCHAR(255) DEFAULT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_url (url)
);

CREATE TABLE IF NOT EXISTS choice (
    session_id VARCHAR(36) NOT NULL,
    image      TEXT        NOT NULL,
    timestamp  DATETIME    NOT NULL,
    KEY idx_choice_session (session_id)
);

CREATE TABLE IF NOT EXISTS choice_snapshot (
    session_id   VARCHAR(36)  NOT NULL,
    session_name VARCHAR(255) NOT NULL,
    images       TEXT,
    timestamp    DATETIME     NOT NULL,
    KEY idx_snapshot_session (session_id)
);

CREATE TABLE IF NOT EXISTS login_attempts (
    ip           VARCHAR(45) NOT NULL,
    attempted_at DATETIME    NOT NULL,
    KEY idx_attempts_ip_time (ip, attempted_at)
);
