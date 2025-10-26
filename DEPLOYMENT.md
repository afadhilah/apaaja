# CompBuddy Deployment Guide

## 🚀 Deploy ke Railway + Supabase

### 1. Setup Supabase Database
1. Buka https://supabase.com
2. Create New Project
3. Pilih region terdekat (Singapore)
4. Tunggu database ready
5. Pergi ke Settings > Database
6. Copy connection string:
   ```
   postgresql://postgres:[PASSWORD]@db.[PROJECT-REF].supabase.co:5432/postgres
   ```

### 2. Deploy Backend ke Railway
1. Buka https://railway.app
2. Login dengan GitHub
3. Click "New Project"
4. Select "Deploy from GitHub repo"
5. Pilih repository: `apaaja`
6. Railway akan auto-detect Laravel

### 3. Set Environment Variables di Railway
```env
APP_NAME=CompBuddy
APP_ENV=production
APP_KEY=base64:wYtaZIJch5yrnhULLQ/VLYJfDaH88SmamHpo9aeN5Xc=
APP_DEBUG=false
APP_URL=https://your-app.railway.app

DB_CONNECTION=pgsql
DB_HOST=db.your-project.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your_supabase_password

SESSION_DRIVER=database
CACHE_STORE=database

GROQ_API_KEY=your_groq_api_key_here
SEMANTIC_SCHOLAR_API_KEY=your_semantic_scholar_api_key_here
```

### 4. Deploy Frontend ke Vercel
1. Buka https://vercel.com
2. Import Git Repository
3. Pilih `apaaja`
4. Framework Preset: **Other**
5. Build Command: `npm run build`
6. Output Directory: `public/build`
7. Add Environment Variable:
   ```
   VITE_API_URL=https://your-app.railway.app
   ```

### 5. Run Migration
Di Railway console:
```bash
php artisan migrate --force
```

### 6. Test
- Backend: https://your-app.railway.app
- Frontend: https://your-app.vercel.app
- Chat: https://your-app.vercel.app/chat

## 📝 Checklist
- [ ] Supabase database created
- [ ] Railway backend deployed
- [ ] Environment variables set
- [ ] Database migrated
- [ ] Vercel frontend deployed
- [ ] API connection working
- [ ] Chat functionality working

## 🔧 Troubleshooting

### Backend not starting
```bash
# Check logs di Railway
railway logs
```

### Frontend can't connect to backend
- Check VITE_API_URL in Vercel
- Check CORS in Laravel

### Database connection error
- Verify Supabase credentials
- Check if IP is whitelisted (Supabase allows all by default)

## 🎉 Done!
Your CompBuddy is now live!
