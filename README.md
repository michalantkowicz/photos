# Photo Gallery — local dev + E2E

PHP photo gallery, runnable on a laptop with Docker plus a Playwright
regression suite that captures the current observable behaviour.

## Layout (added pieces)

```
config.local.php.example    # configuration template — copy to config.local.php and fill in values
config.php                  # loads config.local.php or env vars; dies with a clear message if anything is missing
docker/
  Dockerfile                # php:8.1-apache + mysqli + mod_rewrite
  apache-vhost.conf         # AllowOverride All so .htaccess is honoured
  htaccess                  # Docker .htaccess (no shared-hosting AddHandler)
docker-compose.yml          # app + mysql, mounts this directory as the docroot
db/init.sql                 # schema + 2 fixture sessions used by E2E
tests/
  package.json
  playwright.config.ts
  globalSetup.ts            # resets DB + writes fixture JPEGs before each run
  e2e/*.spec.ts             # Playwright suites
```

## Configuration

All environment-specific values (DB credentials, site URL, admin password)
live outside the codebase. **No default credentials are baked into the code.**

### For local dev (non-Docker) and shared hosting

Copy the template and fill in your values:

```bash
cp config.local.php.example config.local.php
# edit config.local.php with your values
```

To generate an `ADMIN_PASSWORD` bcrypt hash:

```bash
php -r "echo password_hash('your-password', PASSWORD_BCRYPT) . PHP_EOL;"
```

`config.local.php` is blocked from direct HTTP access via `.htaccess` and is
listed in `.gitignore` — it must never be committed.

### For Docker (local dev with the compose stack)

`docker-compose.yml` sets all env vars directly. No `config.local.php` needed.
Env vars always take precedence over the file.

### For shared hosting (production deploy)

Upload `config.local.php` alongside `config.php` inside `public_html/`.
The `<Files>` rule in `.htaccess` prevents Apache from serving it directly.
Alternatively, place it one level above `public_html/` (outside the webroot)
— `config.php` checks both locations.

## Run locally

```bash
docker compose up -d --build
# wait for the db healthcheck (a few seconds), then visit:
#   http://localhost:8080/admin                       admin (pwd: admin)
#   http://localhost:8080/sesja/test-no-pwd           open gallery
#   http://localhost:8080/sesja/test-with-pwd         protected (pwd: test123)
```

Shut down:
```bash
docker compose down            # keeps the named mysql volume
docker compose down -v         # also wipes the mysql data
```

## Run the E2E suite

Pre-reqs: Node 20+, Docker compose stack already running.

```bash
cd tests
npm install
npx playwright install chromium    # first time only
npm test
```

What's covered (see `tests/e2e/*.spec.ts`):

- `admin-auth` — password form, wrong password, login, logout, rate-limiting
- `gallery-public` — open session, 3 photos, 404 for unknown URL
- `gallery-password` — password form, wrong/right password, unlock
- `selection-persistence` — pick photos, save, reload shows them checked; re-submitting replaces prior choice, snapshot history retained; 403 for unauthorised POST
- `admin-listing` — sessions table, file counts, chosen-image counts, password plaintext display
- `admin-create-session` — upload form creates a new session + writes photo to disk
- `end-to-end-workflows` — user→admin chains in separate browser contexts
- `photo-proxy` — auth matrix, path traversal, extension allowlist, webroot blocking

`tests/globalSetup.ts` re-runs `db/init.sql` and re-creates the fixture photo
directories before every run, so tests start from a known state.

## Deploy to shared hosting (FTP)

There is no automated deploy. The site lives on a shared host and is updated
over FTP/SFTP with `lftp`.

### What to upload

Everything in the project root **except** dev-only files:

- skip: `docker/`, `docker-compose.yml`, `tests/`, `test-results/`, `db/`,
  `logs/`, `.git*`, `AGENT.md`, `README.md`, `config.local.php.example`
- upload: all root `*.php`, `security/`, and an empty `data/` containing
  only its `.htaccess` (`deny from all`)

### Files to create on the server

1. `config.local.php` alongside `config.php` inside the webroot. Copy
   `config.local.php.example`, then fill in `DB_*`, `BASE_URL`, and a bcrypt
   `ADMIN_PASSWORD`:

   ```bash
   php -r "echo password_hash('your-password', PASSWORD_BCRYPT) . PHP_EOL;"
   ```

2. *(Optional, alternative to `DB_PASS` in `config.local.php`)* a
   `db-pass.txt` one level **above** the webroot containing the DB password
   on a single line. Apache cannot serve it because it's outside the
   document root.

### Database schema

On a fresh database, load `db/prod-init.sql` once via phpMyAdmin (SQL tab →
paste → Go) or:

```bash
mysql -h HOST -u USER -p DBNAME < db/prod-init.sql
```

That file is the production-safe variant of `db/init.sql` — no `DROP TABLE`,
no E2E fixtures. **Never run `db/init.sql` against production** — it starts
with `DROP TABLE` and would wipe live data.

If the DB already has `session` / `choice` / `choice_snapshot` from a prior
deploy but is missing the rate-limiter table, run only:

```sql
CREATE TABLE IF NOT EXISTS login_attempts (
    ip           VARCHAR(45) NOT NULL,
    attempted_at DATETIME    NOT NULL,
    KEY idx_attempts_ip_time (ip, attempted_at)
);
```

Without it `auth.php` 500s on every login attempt.

### One-time photo-directory migration

Only relevant if the host already has `public_html/<uuid>/` photo directories
from before the `data/` refactor — move them under `data/`:

```
public_html/<uuid>/  →  public_html/data/<uuid>/
```

Skip this and galleries render but every photo 404s.

### lftp commands

From inside lftp (single line — backslash line continuations are not
supported at the interactive prompt):

```
mirror -R --exclude-glob docker/ --exclude-glob tests/ --exclude-glob test-results/ --exclude-glob db/ --exclude-glob logs/ --exclude-glob .git* --exclude AGENT.md --exclude README.md --exclude docker-compose.yml --exclude config.local.php.example /path/to/local/project/ .
```

Or as a one-shot from the shell:

```bash
lftp -u USER sftp://your-host -e "mirror -R --exclude-glob docker/ --exclude-glob tests/ --exclude-glob test-results/ --exclude-glob db/ --exclude-glob logs/ --exclude-glob .git* --exclude AGENT.md --exclude README.md --exclude docker-compose.yml --exclude config.local.php.example /path/to/local/project/ /domains/your-domain/public_html/; bye"
```

Useful flags:

- `--dry-run` — preview transfers without uploading
- `-n` / `--only-newer` — skip files that haven't changed
- `--delete` — remove remote files that no longer exist locally (use with care)

Upload `config.local.php` separately so it never lands on disk in your
local working tree:

```
put /path/to/your/config.local.php
```
