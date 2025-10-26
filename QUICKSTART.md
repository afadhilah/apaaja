# ⚡ Quick Start - Railway Deployment

## 🎯 Error di Railway? Ikuti ini!

### Step 1: Pastikan Code Sudah Push ke GitHub

```bash
git add .
git commit -m "Setup for Railway"
git push origin main
```

### Step 2: Login Railway & Deploy

1. Buka https://railway.app
2. Login dengan GitHub
3. Klik **"New Project"**
4. Pilih **"Deploy from GitHub repo"**
5. Pilih repository **apaaja**

### Step 3: Tunggu Build Selesai (3-5 menit)

Railway akan otomatis:
- ✅ Install PHP dependencies
- ✅ Install Node dependencies  
- ✅ Build Vue assets
- ✅ Start server

### Step 4: Add Environment Variables

**Klik tab "Variables"**, lalu copy-paste semua ini:

```
APP_NAME=CompBuddy
APP_ENV=production
APP_KEY=base64:wYtaZIJch5yrnhULLQ/VLYJfDaH88SmamHpo9aeN5Xc=
APP_DEBUG=false
DB_CONNECTION=sqlite
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
GROQ_API_KEY=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
SEMANTIC_SCHOLAR_API_KEY=YfPhbohzM7aoztSFSNCsM2pD3Fjcj7GvavYSDzhA
```

**PENTING:** Untuk `APP_URL`, klik "Generate Domain" dulu, lalu tambah:
```
APP_URL=https://your-generated-domain.up.railway.app
```

### Step 5: Redeploy

Setelah add variables:
1. Klik tab **"Deployments"**
2. Klik **"Redeploy"**
3. Tunggu 3-5 menit

### Step 6: Generate Domain & Test

1. Klik tab **"Settings"**
2. Scroll ke **"Networking"** → **"Public Networking"**
3. Klik **"Generate Domain"**
4. Copy URL dan buka di browser
5. ✅ **Done!**

---

## ❌ Masih Error?

### Error: Build Failed

**Check logs:**
1. Tab "Deployments" → Klik deployment → View logs
2. Cari kata "ERROR" atau "Failed"

**Common fixes:**

**Error: "npm run build failed"**
```bash
# Add variable:
NODE_OPTIONS=--max-old-space-size=4096
```

**Error: "No APP_KEY"**
```bash
# Pastikan APP_KEY sudah di-set di Variables
```

**Error: "composer install failed"**
```bash
# Pastikan composer.json valid
# Cek di local: composer install
```

---

### Error: White Screen

**Fix:**
1. Set `APP_DEBUG=true` (sementara)
2. Reload page
3. Lihat error message
4. Screenshot & check RAILWAY_TROUBLESHOOTING.md

---

### Error: AI Not Responding

**Fix:**
1. Pastikan `GROQ_API_KEY` benar
2. Test di local dulu:
```bash
php artisan tinker
>>> Http::withHeaders(['Authorization' => 'Bearer '.env('GROQ_API_KEY')])->get('https://api.groq.com/openai/v1/models');
```

---

## ✅ Verifikasi Deployment Sukses

Cek list ini:

- [ ] Build logs: ✅ No errors
- [ ] Environment variables: ✅ All set
- [ ] Domain generated: ✅ https://xxx.up.railway.app
- [ ] Homepage loads: ✅ Chat interface muncul
- [ ] Send message: ✅ AI responds
- [ ] References: ✅ Papers muncul
- [ ] Filters: ✅ Berfungsi

**Jika semua ✅ = SUKSES! 🎉**

---

## 📸 Share Error

Jika masih error:
1. Screenshot Railway logs
2. Screenshot error di browser
3. Check RAILWAY_TROUBLESHOOTING.md
4. Open GitHub issue dengan screenshot

---

**Need help? Check RAILWAY_TROUBLESHOOTING.md untuk solusi lengkap!**
