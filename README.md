# ewelina.antkowicz.pl — local dev + E2E

Original PHP gallery, now runnable on a laptop with Docker plus a Playwright
regression suite that captures the current observable behavior.

## Layout (added pieces)

```
config.php               # env-driven constants, prod defaults preserved
docker/
  Dockerfile             # php:8.1-apache + mysqli + mod_rewrite
  apache-vhost.conf      # AllowOverride All so .htaccess is honoured
  htaccess               # Docker .htaccess (no shared-hosting AddHandler)
docker-compose.yml       # app + mysql, mounts this directory as the docroot
db/init.sql              # schema + 2 fixture sessions used by E2E
tests/
  package.json
  playwright.config.ts
  globalSetup.ts         # resets DB + writes fixture JPEGs before each run
  e2e/*.spec.ts          # Playwright suites
```

The production PHP files were patched only to pull DB credentials and the base
URL from `config.php` (which honors env vars with prod-default fallbacks) —
nothing else changed in behaviour.

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

- `admin-auth` — password form, wrong password, login, logout
- `gallery-public` — open session, 3 photos, 404 for unknown URL
- `gallery-password` — password form, wrong password, unlock
- `selection-persistence` — pick photos, save, reload shows them checked; re-submitting replaces prior choice, snapshot history retained
- `admin-listing` — sessions table, file counts, chosen-image counts
- `admin-create-session` — upload form creates a new session + writes photo to disk

`tests/globalSetup.ts` re-runs `db/init.sql` and re-creates the fixture photo
directories (`00000000-0000-4000-8000-00000000000{1,2}/`) on every run, so
tests start from a known state.

## Production defaults

`config.php` uses these defaults when env vars are unset — they match what
was hard-coded in the original files, so deploying to prod with no env
overrides preserves current behaviour exactly:

| Constant              | Prod default                                                  |
|-----------------------|---------------------------------------------------------------|
| `DB_HOST`             | `localhost`                                                   |
| `DB_USER`             | `mantkowi_ewelina`                                            |
| `DB_PASS`             | (the previously hardcoded password)                           |
| `DB_NAME`             | `mantkowi_ewelina_sessions`                                   |
| `BASE_URL`            | `http://ewelina.antkowicz.pl`                                 |
| `ADMIN_PASSWORD_MD5`  | `4e6ca650f52383d9054a826b0b4db1f5` (the original MD5)         |

In docker-compose, `BASE_URL=http://localhost:8080` and the admin password is
overridden to `admin` so tests have a known credential.
