# Photo Proofing Gallery

A tool for photographers to share a photo session with clients and collect
their orders: the photographer uploads the session's photos in the admin
panel and shares the gallery (optionally password-protected); the client
picks the photos they want printed and saves the selection.

Stack: PHP 8.1 + MySQL. Runs locally with Docker, deploys to shared hosting
with a GitHub Actions button, and has a Playwright E2E suite.

## Run locally

```bash
docker compose up -d --build
#   http://localhost:8080/admin                admin       (pwd: admin)
#   http://localhost:8080/sesja/test-no-pwd    open gallery
#   http://localhost:8080/sesja/test-with-pwd  protected   (pwd: test123)

docker compose down       # stop, keep the mysql volume
docker compose down -v    # stop, wipe the mysql volume
```

Docker sets every env var itself — no config file needed.

## Run the E2E suite

Pre-reqs: Node 20+, Docker stack already running.

```bash
cd tests
npm install
npx playwright install chromium    # first time only
npm test
```

`tests/globalSetup.ts` re-runs `db/init.sql` and recreates the fixture photos
before every run. Suites in `tests/e2e/*.spec.ts` cover admin auth, public and
password galleries, selection persistence, the admin listing, uploads, and the
photo proxy.

## Deploy

A GitHub Actions workflow (`.github/workflows/deploy.yml`) uploads the site
over FTP when you press a button.

**One-time setup** — add four repository secrets
(*Settings → Secrets and variables → Actions*):

| Secret | Value |
|---|---|
| `FTP_HOST` | FTP hostname |
| `FTP_USER` | FTP username |
| `FTP_PASSWORD` | FTP password |
| `FTP_SERVER_DIR` | target folder, e.g. `domains/<domain>/public_html/galeria/` |

**To deploy** — GitHub → **Actions** → **Deploy to FTP** → **Run workflow**.

The workflow uploads the app files and skips dev-only paths. `config.local.php`
and `data/` are excluded, so they are never overwritten or deleted.

**The upload is additive** — it sends new and changed files but never deletes
anything. If you delete or rename a file in the repo, remove the stale file
from the server by hand (over FTP); the workflow will not clean it up.

### Server-side, by hand (once per environment)

The workflow never touches these:

1. **`config.local.php`** — create it in the webroot next to `config.php` (or
   one level above it — `config.php` checks both). Copy
   `config.local.php.example` and fill in `DB_*`, `BASE_URL`, and a bcrypt
   `ADMIN_PASSWORD`:

   ```bash
   php -r "echo password_hash('your-password', PASSWORD_BCRYPT).PHP_EOL;"
   ```

   It is git-ignored and blocked from HTTP access by `.htaccess`.

2. **Database** — load `db/prod-init.sql` once via phpMyAdmin (Import tab, or
   SQL tab → paste → Go). It is idempotent (`CREATE TABLE IF NOT EXISTS`), so
   re-running it is safe. **Never run `db/init.sql` on production** — it starts
   with `DROP TABLE`.

### Manual FTP fallback

If you ever need to deploy without the workflow, from `lftp` — note that
directory excludes **must end with a trailing slash**, or lftp silently
skips them (`.git/` works, `.git*` does not):

```
mirror -R --exclude-glob .git/ --exclude-glob .github/ --exclude-glob .claude/ --exclude-glob .gitignore --exclude-glob docker/ --exclude-glob tests/ --exclude-glob test-results/ --exclude-glob db/ --exclude-glob logs/ --exclude-glob data/ --exclude AGENT.md --exclude README.md --exclude docker-compose.yml --exclude config.local.php --exclude config.local.php.example --exclude .dockerignore /path/to/project/ .
```

## File map

```
config.php                 # loads config.local.php or env vars
db.php auth.php session.php view.php log.php   # helpers
index.php                  # /sesja/<slug> gallery entry
admin.php                  # /admin dashboard entry
photo.php                  # auth-gated image proxy
submit.php submit_choices.php delete_session.php   # POST handlers
security/                  # included dashboard + gallery views (deny from all)
data/                      # photo storage (deny from all, served via photo.php)
docker/  db/  tests/        # dev + test only — not deployed
```

## Configuration (non-Docker only)

Docker and the deploy workflow handle configuration themselves. Only if you run
PHP directly without Docker: `cp config.local.php.example config.local.php` and
fill in `DB_*`, `BASE_URL`, `ADMIN_PASSWORD`. Env vars override the file.
