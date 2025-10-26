# CompBuddy Deployment Guide

## 🚀 Deploy ke Vercel + Supabase

### 1. Setup Supabase Database
1. Buka https://supabase.com
2. Create New Project
3. Pilih region terdekat (Singapore)
4. Tunggu database ready (~2 menit)
5. Pergi ke Settings > Database
6. Copy connection details:
   - Host: `db.[PROJECT-REF].supabase.co`
   - Database: `postgres`
   - User: `postgres`
   - Password: [your password]
   - Port: `5432`

### 2. Deploy ke Vercel
1. Buka https://vercel.com
2. Import Git Repository
3. Pilih repository: `apaaja`
4. Framework Preset: **Other**
5. Root Directory: `./`
6. Build Command: `npm run vercel-build`
7. Output Directory: `public`

### 3. Set Environment Variables di Vercel

**Settings > Environment Variables:**

```env
APP_NAME=CompBuddy
APP_ENV=production
APP_KEY=base64:wYtaZIJch5yrnhULLQ/VLYJfDaH88SmamHpo9aeN5Xc=
APP_DEBUG=false
APP_URL=https://your-app.vercel.app

DB_CONNECTION=pgsql
DB_HOST=db.your-project.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your_supabase_password

SESSION_DRIVER=cookie
CACHE_STORE=array
LOG_CHANNEL=stderr

GROQ_API_KEY=your_groq_api_key_here
SEMANTIC_SCHOLAR_API_KEY=your_semantic_scholar_api_key_here
```

### 4. Deploy!
Click **Deploy** dan tunggu 2-3 menit

### 5. Run Migration (Manual - ONE TIME)

Karena Vercel serverless, migration harus manual:

**Option A: Via Supabase SQL Editor**
1. Buka Supabase Dashboard
2. SQL Editor
3. Copy & paste migration SQL dari `database/migrations/`
4. Run query

**Option B: Via Local dengan Supabase DB**
```bash
# Update .env dengan Supabase credentials
php artisan migrate --force
```

### 6. Test
- URL: https://your-app.vercel.app
- Chat: https://your-app.vercel.app/chat

## ⚠️ Vercel Limitations

**Laravel di Vercel punya batasan:**
- ✅ Routes & Controllers work
- ✅ Database queries work
- ✅ API calls work
- ⚠️ File storage limited (use S3/Cloudinary)
- ⚠️ Artisan commands tidak bisa run (use local for migrations)
- ⚠️ Session harus use cookie/database
- ⚠️ 10 second timeout per request

## 🔧 Troubleshooting

### 500 Internal Server Error
- Check Vercel logs: Function Logs
- Check environment variables
- Verify database connection

### Database connection error
```bash
# Test locally first
php artisan tinker
> DB::connection()->getPdo();
```

### Build fails
```bash
# Test build locally
npm run vercel-build
composer install --no-dev
```

## 🎉 Done!
Your CompBuddy is now live on Vercel + Supabase!
