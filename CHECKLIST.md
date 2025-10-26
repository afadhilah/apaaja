# ✅ Railway Deployment Checklist

## Pre-Deployment

- [ ] Code sudah di-commit
- [ ] Code sudah di-push ke GitHub repository
- [ ] `.env.example` sudah update dengan API keys template
- [ ] `composer.json` dan `package.json` valid
- [ ] Frontend sudah di-build dan test di local (`npm run build`)

## Railway Setup

- [ ] Daftar/login ke [railway.app](https://railway.app)
- [ ] Connect GitHub account
- [ ] Create new project
- [ ] Select repository `afadhilah/apaaja`
- [ ] Railway auto-detect Laravel ✅

## Environment Configuration

- [ ] Add `APP_NAME=CompBuddy`
- [ ] Add `APP_ENV=production`
- [ ] Add `APP_KEY` (gunakan yang ada atau generate baru)
- [ ] Add `APP_DEBUG=false`
- [ ] Add `APP_URL=${{RAILWAY_PUBLIC_DOMAIN}}`
- [ ] Add `DB_CONNECTION=sqlite`
- [ ] Add `GROQ_API_KEY` (dari console.groq.com)
- [ ] Add `SEMANTIC_SCHOLAR_API_KEY` (optional)
- [ ] Add `SESSION_DRIVER=file`
- [ ] Add `CACHE_STORE=file`
- [ ] Add `QUEUE_CONNECTION=sync`

## Deployment

- [ ] Wait for initial build (~3-5 minutes)
- [ ] Check build logs for errors
- [ ] Generate public domain
- [ ] Access app via Railway domain

## Testing

- [ ] Homepage loads successfully
- [ ] Chat interface appears
- [ ] Can send message to AI
- [ ] AI responds correctly
- [ ] References panel shows papers
- [ ] Filters work (topic, citations, year)
- [ ] Citation copy works
- [ ] Paper links open correctly
- [ ] No console errors in browser

## Post-Deployment

- [ ] Set `APP_DEBUG=false` (if not already)
- [ ] Update `APP_URL` to match Railway domain
- [ ] Test all features one more time
- [ ] Setup custom domain (optional)
- [ ] Share app URL! 🎉

## Troubleshooting

If anything goes wrong:
- [ ] Check Railway logs (Deployments → View logs)
- [ ] Verify all environment variables are set
- [ ] Try redeploy
- [ ] Check RAILWAY_DEPLOY.md for solutions

---

**When all checked ✅ = SUCCESS! 🚀**

Your app is live at: `https://your-app.up.railway.app`
