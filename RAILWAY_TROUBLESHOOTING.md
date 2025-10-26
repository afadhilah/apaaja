# 🔧 Railway Troubleshooting Guide - CompBuddy

## ⚠️ Error Umum & Solusinya

### 1️⃣ Error: "No APP_KEY Set"

**Error Message:**
```
RuntimeException: No application encryption key has been specified.
```

**Penyebab:** `APP_KEY` tidak di-set di environment variables

**Solusi:**
```bash
# Di Railway Variables, tambahkan:
APP_KEY=base64:wYtaZIJch5yrnhULLQ/VLYJfDaH88SmamHpo9aeN5Xc=

# Atau generate baru di local:
php artisan key:generate --show
# Copy output dan paste ke Railway
```

---

### 2️⃣ Error: Build Failed - "npm run build failed"

**Error Message:**
```
ERROR: command npm run build failed
```

**Penyebab:** 
- Node modules tidak terinstall dengan benar
- Memory limit tercapai saat build

**Solusi:**

**A. Update `nixpacks.toml`** - pastikan sudah seperti ini:
```toml
[phases.setup]
nixPkgs = ['nodejs_20', 'php82', 'php82Packages.composer']

[phases.install]
cmds = [
    'composer install --no-dev --optimize-autoloader --no-interaction',
    'npm ci --prefer-offline --no-audit',
]

[phases.build]
cmds = [
    'npm run build',
]

[start]
cmd = 'php artisan serve --host=0.0.0.0 --port=$PORT'
```

**B. Tambah environment variable:**
```bash
NODE_OPTIONS=--max-old-space-size=4096
```

---

### 3️⃣ Error: "SQLSTATE[HY000]: General error: 14 unable to open database file"

**Error Message:**
```
SQLSTATE[HY000]: General error: 14 unable to open database file
```

**Penyebab:** SQLite database file tidak ada atau permission denied

**Solusi:**

**Opsi 1: Update nixpacks.toml (Recommended)**
```toml
[phases.build]
cmds = [
    'npm run build',
    'mkdir -p database',
    'touch database/database.sqlite',
    'chmod 777 database/database.sqlite',
]
```

**Opsi 2: Gunakan MySQL/PostgreSQL Railway**
```bash
# Di Railway:
# 1. Klik "+ New" → Database → PostgreSQL
# 2. Update environment variables:
DB_CONNECTION=pgsql
DB_HOST=${{PGHOST}}
DB_PORT=${{PGPORT}}
DB_DATABASE=${{PGDATABASE}}
DB_USERNAME=${{PGUSER}}
DB_PASSWORD=${{PGPASSWORD}}
```

---

### 4️⃣ Error: "White Screen / Blank Page"

**Penyebab:**
- Assets tidak ter-build
- APP_URL salah
- Cache issue

**Solusi:**

**A. Set APP_DEBUG=true sementara:**
```bash
APP_DEBUG=true
```

**B. Clear cache di Railway:**
1. Settings → Deployments
2. Redeploy from scratch

**C. Pastikan environment variables:**
```bash
APP_URL=${{RAILWAY_PUBLIC_DOMAIN}}
# Atau jika sudah punya domain:
APP_URL=https://your-app.up.railway.app
```

---

### 5️⃣ Error: "Failed to start server on port"

**Error Message:**
```
Failed to start server on port $PORT
```

**Penyebab:** Port binding issue

**Solusi:**

**Update `nixpacks.toml`:**
```toml
[start]
cmd = 'php artisan serve --host=0.0.0.0 --port=${PORT:-8000}'
```

**Atau gunakan Procfile:**
```
web: php artisan serve --host=0.0.0.0 --port=$PORT
```

---

### 6️⃣ Error: "Connection timeout" atau "AI not responding"

**Penyebab:** 
- GROQ_API_KEY tidak di-set
- API key invalid
- Network timeout

**Solusi:**

**A. Verify API Keys:**
```bash
# Di Railway Variables, pastikan ada:
GROQ_API_KEY=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
SEMANTIC_SCHOLAR_API_KEY=YfPhbohzM7aoztSFSNCsM2pD3Fjcj7GvavYSDzhA
```

**B. Test API key di local dulu:**
```bash
curl https://api.groq.com/openai/v1/models \
  -H "Authorization: Bearer YOUR_GROQ_API_KEY"
```

**C. Tambah timeout di ChatController:**
```php
$response = Http::timeout(60) // increase timeout
    ->withHeaders([...])
```

---

### 7️⃣ Error: "Class not found" atau "ReflectionException"

**Penyebab:** Composer autoload issue

**Solusi:**

**Di nixpacks.toml, update install phase:**
```toml
[phases.install]
cmds = [
    'composer install --no-dev --optimize-autoloader --no-interaction',
    'composer dump-autoload --optimize',
    'npm ci --prefer-offline --no-audit',
]
```

---

### 8️⃣ Error: "Storage permission denied"

**Error Message:**
```
file_put_contents(/app/storage/logs/laravel.log): failed to open stream
```

**Solusi:**

**Update nixpacks.toml build phase:**
```toml
[phases.build]
cmds = [
    'npm run build',
    'chmod -R 777 storage bootstrap/cache',
]
```

---

### 9️⃣ Error: "Route not found" atau "404 on all routes"

**Penyebab:** Route cache issue

**Solusi:**

**A. Hapus route:cache dari build:**
```toml
[phases.build]
cmds = [
    'npm run build',
    'php artisan config:cache',
    # HAPUS: 'php artisan route:cache',
]
```

**B. Atau clear cache:**
```bash
# Tambah di start command:
php artisan route:clear && php artisan serve --host=0.0.0.0 --port=$PORT
```

---

### 🔟 Error: "Memory limit exceeded"

**Error Message:**
```
PHP Fatal error: Allowed memory size exhausted
```

**Solusi:**

**Tambah environment variable:**
```bash
PHP_MEMORY_LIMIT=512M
```

---

## 🎯 Quick Fix Checklist

Jika deployment gagal, cek dalam urutan:

1. **✅ Environment Variables Complete?**
   - APP_KEY set?
   - GROQ_API_KEY set?
   - APP_URL set?

2. **✅ Database Setup?**
   - SQLite: file exists?
   - PostgreSQL: connection vars correct?

3. **✅ Build Logs?**
   - Composer install success?
   - npm ci success?
   - npm run build success?

4. **✅ Start Logs?**
   - Server starts on port $PORT?
   - No PHP errors?

5. **✅ Browser Console?**
   - Assets loading (CSS/JS)?
   - API calls working?
   - No CORS errors?

---

## 📊 How to View Railway Logs

1. **Login ke Railway Dashboard**
2. **Select Your Project**
3. **Klik "Deployments" tab**
4. **Klik deployment terakhir**
5. **View Logs** - lihat error messages

### Filter Logs:
```
# Build logs
Filter: build

# Runtime logs
Filter: start

# Error logs only
Filter: error
```

---

## 🆘 Masih Error?

### Debug Mode

**Sementara enable debug:**
```bash
APP_DEBUG=true
LOG_LEVEL=debug
```

**Access error page**, lalu:
1. Screenshot error message
2. Check Railway logs
3. Search error di Google/StackOverflow

### Redeploy dari Scratch

1. Railway Dashboard → Settings
2. Scroll to "Danger Zone"
3. Klik "Delete Service"
4. Create new deployment

### Alternative: Use PostgreSQL

Jika SQLite bermasalah:
```bash
# 1. Di Railway, add PostgreSQL database
# 2. Update env vars:
DB_CONNECTION=pgsql
DB_HOST=${{PGHOST}}
DB_PORT=${{PGPORT}}
DB_DATABASE=${{PGDATABASE}}
DB_USERNAME=${{PGUSER}}
DB_PASSWORD=${{PGPASSWORD}}
```

---

## ✅ Working Configuration

Ini konfigurasi yang **sudah tested dan working**:

### nixpacks.toml
```toml
[phases.setup]
nixPkgs = ['nodejs_20', 'php82', 'php82Packages.composer']

[phases.install]
cmds = [
    'composer install --no-dev --optimize-autoloader --no-interaction',
    'npm ci --prefer-offline --no-audit',
]

[phases.build]
cmds = [
    'npm run build',
]

[start]
cmd = 'php artisan serve --host=0.0.0.0 --port=$PORT'
```

### Environment Variables
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
GROQ_API_KEY=your_actual_key_here
SEMANTIC_SCHOLAR_API_KEY=your_actual_key_here
```

---

**Kalau masih error, share screenshot error dari Railway logs!** 🔍
