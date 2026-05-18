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
