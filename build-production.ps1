# Build script for production deployment
Write-Host "🚀 Building CompBuddy for Production..." -ForegroundColor Cyan

# Clear previous builds
Write-Host "`n📦 Clearing cache..." -ForegroundColor Yellow
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Install dependencies
Write-Host "`n📥 Installing dependencies..." -ForegroundColor Yellow
composer install --optimize-autoloader --no-dev
npm ci

# Build frontend assets
Write-Host "`n🔨 Building frontend assets..." -ForegroundColor Yellow
npm run build

# Optimize for production
Write-Host "`n⚡ Optimizing for production..." -ForegroundColor Yellow
php artisan config:cache
php artisan route:cache
php artisan view:cache

Write-Host "`n✅ Build completed successfully!" -ForegroundColor Green
Write-Host "`nYour app is ready to deploy!" -ForegroundColor Cyan
Write-Host "Next steps:" -ForegroundColor White
Write-Host "  1. Push to GitHub: git push origin main" -ForegroundColor Gray
Write-Host "  2. Deploy on Railway: https://railway.app" -ForegroundColor Gray
Write-Host "  3. Configure environment variables" -ForegroundColor Gray
