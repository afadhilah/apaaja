# CompBuddy Deployment Guide

## 🚀 Deploy to Railway (Recommended)

Railway is the easiest way to deploy this Laravel + Vue (Inertia.js) application.

### Prerequisites
- GitHub account
- Railway account (free): https://railway.app

### Step-by-Step Deployment

#### 1. Push to GitHub
```bash
git init
git add .
git commit -m "Initial commit"
git remote add origin YOUR_GITHUB_REPO_URL
git push -u origin main
```

#### 2. Deploy on Railway

1. Go to https://railway.app
2. Click "New Project"
3. Select "Deploy from GitHub repo"
4. Choose your repository
5. Railway will auto-detect Laravel and deploy

#### 3. Configure Environment Variables

In Railway dashboard, add these environment variables:

```
APP_NAME=CompBuddy
APP_ENV=production
APP_KEY=base64:wYtaZIJch5yrnhULLQ/VLYJfDaH88SmamHpo9aeN5Xc=
APP_DEBUG=false
APP_URL=https://your-app.up.railway.app

DB_CONNECTION=sqlite

# Get your Groq API key from: https://console.groq.com/keys
GROQ_API_KEY=<paste-your-key-here>

# Get your Semantic Scholar API key from: https://www.semanticscholar.org/product/api
SEMANTIC_SCHOLAR_API_KEY=<paste-your-key-here>

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

#### 4. Generate APP_KEY (if needed)

```bash
php artisan key:generate --show
```

Copy the output and update APP_KEY in Railway.

#### 5. Deploy!

Railway will automatically:
- Install PHP dependencies (`composer install`)
- Install Node dependencies (`npm ci`)
- Build frontend (`npm run build`)
- Start the application

### Access Your App

Your app will be available at: `https://your-app-name.up.railway.app`

---

## Alternative: Vercel (Frontend) + Railway (Backend)

If you want to split frontend and backend:

### Backend (Railway)
Same as above, but API only.

### Frontend (Vercel)

1. Build static files:
```bash
npm run build
```

2. Deploy `public/build` to Vercel
3. Configure API endpoint in `.env`

---

## Local Testing Before Deploy

```bash
# Build production assets
npm run build

# Test production mode
APP_ENV=production php artisan serve

# Clear cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Post-Deployment Checklist

✅ Set `APP_DEBUG=false` in production
✅ Set correct `APP_URL`
✅ Configure GROQ_API_KEY
✅ Configure SEMANTIC_SCHOLAR_API_KEY
✅ Test chat functionality
✅ Check error logs if issues occur

---

## Troubleshooting

### Issue: White screen
- Check `APP_KEY` is set
- Check `npm run build` completed
- Check logs in Railway dashboard

### Issue: 500 Error
- Check environment variables
- Check `APP_DEBUG=true` temporarily to see errors
- Check storage permissions

### Issue: Assets not loading
- Ensure `APP_URL` matches your Railway URL
- Run `php artisan config:cache`

---

## Support

For issues, check Railway logs:
1. Go to Railway dashboard
2. Select your project
3. Click "Deployments"
4. Click latest deployment
5. View logs
