````markdown
# 🚂 Railway Environment Variables

Copy paste variables ini ke Railway dashboard:

## Required Variables

```bash
APP_NAME=CompBuddy
APP_ENV=production
APP_KEY=base64:wYtaZIJch5yrnhULLQ/VLYJfDaH88SmamHpo9aeN5Xc=
APP_DEBUG=false
APP_URL=${{RAILWAY_PUBLIC_DOMAIN}}
DB_CONNECTION=sqlite
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

## Optional Performance Settings

```bash
PHP_CLI_SERVER_WORKERS=4
LOG_LEVEL=error
SESSION_LIFETIME=120
```

## Notes:

1. `APP_URL` menggunakan `${{RAILWAY_PUBLIC_DOMAIN}}` - Railway akan auto-replace dengan domain Anda
2. Jika ingin generate `APP_KEY` baru: run `php artisan key:generate --show` di local
3. `GROQ_API_KEY` & `SEMANTIC_SCHOLAR_API_KEY` sudah terisi - ganti jika perlu

## How to Add in Railway:

1. Dashboard → Project → **Variables** tab
2. Klik **"+ New Variable"**
3. Copy-paste name & value
4. Atau upload `.env` file langsung (klik **"Raw Editor"**)
