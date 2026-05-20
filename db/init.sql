-- Schema reverse-engineered from the existing PHP code. Loaded by MySQL
-- on first container start, and re-run by tests/globalSetup.ts before
-- every Playwright run to give each test suite a clean state.

DROP TABLE IF EXISTS choice;
DROP TABLE IF EXISTS choice_snapshot;
DROP TABLE IF EXISTS session;
DROP TABLE IF EXISTS login_attempts;

CREATE TABLE session (
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

-- NB: no `id` column on choice — security/admin.php uses the correlated
-- subquery `(SELECT count(*) FROM choice WHERE session_id = id)` where
-- `id` must resolve to the OUTER session.id. Adding an `id` column to
-- choice would silently break that count.
CREATE TABLE choice (
    session_id VARCHAR(36) NOT NULL,
    image      TEXT        NOT NULL,
    timestamp  DATETIME    NOT NULL,
    KEY idx_choice_session (session_id)
);

CREATE TABLE choice_snapshot (
    session_id   VARCHAR(36)  NOT NULL,
    session_name VARCHAR(255) NOT NULL,
    images       TEXT,
    timestamp    DATETIME     NOT NULL,
    KEY idx_snapshot_session (session_id)
);

-- Failed admin login attempts — used by the PHP rate-limiter.
CREATE TABLE login_attempts (
    ip           VARCHAR(45) NOT NULL,
    attempted_at DATETIME    NOT NULL,
    KEY idx_attempts_ip_time (ip, attempted_at)
);

-- ---------------------------------------------------------------------------
-- Fixtures used by the Playwright suite. The corresponding photo directories
-- are created on disk by tests/globalSetup.ts.

INSERT INTO session (id, name, url, description, file_names, password, created_at) VALUES
('00000000-0000-4000-8000-000000000001',
 'Test Session No Password',
 'http://localhost:8080/sesja/test-no-pwd',
 'Open gallery used by E2E tests',
 'photo1.jpg\nphoto2.jpg\nphoto3.jpg\nphoto4.jpg\nphoto5.jpg\nphoto6.jpg',
 NULL,
 '2024-01-01 10:00:00'),
('00000000-0000-4000-8000-000000000002',
 'Test Session With Password',
 'http://localhost:8080/sesja/test-with-pwd',
 'Password-protected gallery used by E2E tests',
 'photo1.jpg\nphoto2.jpg\nphoto3.jpg\nphoto4.jpg\nphoto5.jpg\nphoto6.jpg',
 'test123',
 '2024-01-02 10:00:00');
