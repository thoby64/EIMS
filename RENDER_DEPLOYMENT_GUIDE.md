# Deploying EIMS to Render with Docker and an External MySQL Database

This guide explains how to deploy **EIMS — Enterprise Infrastructure Management System** to Render using Docker while keeping the production database on an external MySQL provider such as FreeSQLDatabase.

## 1. Files added for Render

The deployment setup uses these files:

- `Dockerfile` — builds the Laravel application, installs PHP extensions, installs Composer dependencies, builds Vite assets and runs Apache.
- `.dockerignore` — prevents local secrets, `vendor`, `node_modules`, logs and cache files from being copied into the Docker image.
- `docker/apache-vhost.conf` — points Apache to Laravel’s `public` directory.
- `docker/entrypoint.sh` — prepares Laravel, runs migrations, optionally runs seeders, caches configuration/routes/views and starts Apache.
- `render.yaml` — Render Blueprint for creating the web service.

## 2. External MySQL database setup

Create a MySQL database from your external provider and collect these values:

- Host
- Port, usually `3306`
- Database name
- Database username
- Database password

Important: the database host must allow connections from Render. If your MySQL provider has an IP allowlist/firewall, allow Render outbound connections or allow public connections as required by that provider.

## 3. Generate the Laravel application key

On your local machine, inside the Laravel directory, run:

```bash
php artisan key:generate --show
```

Copy the full result. It normally looks like:

```text
base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=
```

Use that value for the `APP_KEY` environment variable on Render.

## 4. Push the Laravel project to GitHub

Recommended structure:

```text
your-repository/
├── app/
├── bootstrap/
├── config/
├── database/
├── docker/
├── public/
├── resources/
├── routes/
├── Dockerfile
├── render.yaml
├── composer.json
└── package.json
```

This means the **contents of `eims-laravel`** should be the root of the GitHub repository.

If instead you push the parent `EIMS` folder, then Render’s Dockerfile path must be changed from:

```yaml
dockerfilePath: ./Dockerfile
```

to:

```yaml
dockerfilePath: ./eims-laravel/Dockerfile
```

and the `render.yaml` file should be placed at the repository root.

## 5. Create the Render service

Option A — using Blueprint:

1. Log in to Render.
2. Click **New +**.
3. Choose **Blueprint**.
4. Connect the GitHub repository containing `render.yaml`.
5. Render will read the file and create the `eims-laravel` web service.
6. Render will ask you for every variable marked `sync: false`.

Option B — manual web service:

1. Click **New +**.
2. Choose **Web Service**.
3. Connect your GitHub repository.
4. Set runtime/language to **Docker**.
5. Use Dockerfile path `./Dockerfile`.
6. Add the environment variables listed below.

## 6. Required Render environment variables

Set these values in Render:

| Key | Value |
| --- | --- |
| `APP_NAME` | `EIMS — Enterprise Infrastructure Management System` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Output from `php artisan key:generate --show` |
| `APP_URL` | Your Render URL, for example `https://eims-laravel.onrender.com` |
| `ASSET_URL` | Same as `APP_URL` |
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `info` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | External MySQL host |
| `DB_PORT` | `3306`, unless your provider gives another port |
| `DB_DATABASE` | External MySQL database name |
| `DB_USERNAME` | External MySQL username |
| `DB_PASSWORD` | External MySQL password |
| `SESSION_DRIVER` | `database` |
| `SESSION_SECURE_COOKIE` | `true` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `FILESYSTEM_DISK` | `local` |
| `MAIL_MAILER` | `log` for now |
| `EIMS_INITIAL_ADMIN_PASSWORD` | Strong first admin password |
| `EIMS_RUN_SEEDERS` | `true` on first deployment, then preferably `false` after the database is initialized |

## 7. First deployment behavior

When the container starts, it will:

1. Verify that `APP_KEY` exists.
2. Clear old Laravel caches.
3. Run:

```bash
php artisan migrate --force
```

4. If `EIMS_RUN_SEEDERS=true`, run:

```bash
php artisan db:seed --force
```

5. Create the Laravel storage link.
6. Cache config, routes and views.
7. Start Apache.

After the first successful deployment, set:

```text
EIMS_RUN_SEEDERS=false
```

This prevents seed data from being re-applied on every redeploy.

## 8. First login

The seeded administrator account is:

```text
Email: admin@eims.local
Staff number: EIMS-ADMIN
Password: value of EIMS_INITIAL_ADMIN_PASSWORD
```

You can log in using either:

- `admin@eims.local`
- `EIMS-ADMIN`

After logging in, immediately create real administrator/staff accounts and change the seeded admin password.

## 9. Health check

Render will check:

```text
/health
```

The application should return:

```text
ok
```

## 10. Common deployment problems

### “APP_KEY is missing”

Set `APP_KEY` in Render. Generate it locally with:

```bash
php artisan key:generate --show
```

### “SQLSTATE connection refused” or “could not connect”

Check:

- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- Whether the MySQL provider allows external connections from Render.

### Assets or styling missing

The Docker build runs:

```bash
npm run build
```

If styling is missing, check the Render build logs for Vite or Node errors.

### Login works locally but not on Render

Check:

- `APP_URL` must be the Render HTTPS URL.
- `ASSET_URL` should also be the Render HTTPS URL.
- `SESSION_SECURE_COOKIE=true` should only be used with HTTPS, which Render provides.

## 11. After deployment

After the app is live:

1. Log in as the seeded administrator.
2. Create real departments if needed.
3. Create real users and assign roles.
4. Confirm seeded asset groups and categories.
5. Set `EIMS_RUN_SEEDERS=false`.
6. Keep regular backups of the external MySQL database.

