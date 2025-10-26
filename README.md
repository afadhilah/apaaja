# 🤖 CompBuddy - AI Research Assistant

> **Chatbot pintar untuk mencari dan memahami paper Computer Science**

[![Deploy on Railway](https://railway.app/button.svg)](https://railway.app/template)

---

## 📖 About

**CompBuddy** adalah aplikasi web chatbot yang membantu mahasiswa, peneliti, dan praktisi untuk:
- 🔍 Mencari paper Computer Science yang relevan
- 💡 Memahami konsep dalam paper dengan bantuan AI
- 📚 Mendapatkan referensi paper dengan filter advanced
- 📋 Export citation dalam format APA, IEEE, dan BibTeX

### Tech Stack

- **Backend:** Laravel 12 (PHP 8.2+)
- **Frontend:** Vue.js 3 + Inertia.js
- **UI:** Tailwind CSS 4 + Reka UI
- **AI:** Groq API (Llama 3.3 70B)
- **Paper Search:** Semantic Scholar API
- **Auth:** Laravel Fortify (2FA support)

---

## ✨ Features

### 🎯 Core Features
- **AI-Powered Chat** - Tanya tentang paper CS, AI akan jelaskan dengan mudah
- **Smart Paper Search** - Cari paper by title, topic, atau keyword
- **Advanced Filters:**
  - Topic/Keyword filter
  - Minimum citation count (3-100+)
  - Year range filter
  - Venue filter
- **Reference Management** - Lihat paper details lengkap
- **Citation Generator** - Export dalam format APA, IEEE, BibTeX
- **Multi-Chat Sessions** - Kelola beberapa percakapan sekaligus

### 🎨 UI/UX
- Modern dark theme dengan CS wallpaper
- Responsive design (desktop & mobile)
- Real-time chat interface
- Citation click-to-view
- Paper quick open (DOI/Semantic Scholar/Google Scholar)

---

## 🚀 Quick Deploy to Railway

### One-Click Deploy

[![Deploy on Railway](https://railway.app/button.svg)](https://railway.app/new)

### Manual Deploy

1. **Fork/Clone** repository ini
2. **Login** ke [Railway.app](https://railway.app)
3. **New Project** → Deploy from GitHub repo
4. **Select** repository `apaaja`
5. **Add Environment Variables** (lihat `RAILWAY_ENV.md`)
6. **Deploy!** ✅

**Detail lengkap:** Baca [RAILWAY_DEPLOY.md](RAILWAY_DEPLOY.md)

---

## 💻 Local Development

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 20+
- npm/pnpm

### Installation

```bash
# Clone repository
git clone https://github.com/afadhilah/apaaja.git
cd apaaja

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create SQLite database
touch database/database.sqlite

# Run migrations
php artisan migrate

# Build frontend
npm run build
```

### Get API Keys

1. **Groq API** (Required - AI responses)
   - Daftar di [console.groq.com](https://console.groq.com/keys)
   - Copy API key ke `.env` → `GROQ_API_KEY`

2. **Semantic Scholar API** (Optional - paper search)
   - Request di [semanticscholar.org/product/api](https://www.semanticscholar.org/product/api)
   - Copy API key ke `.env` → `SEMANTIC_SCHOLAR_API_KEY`

### Run Development Server

```bash
# Start Laravel + Vue dev server
composer dev

# Or manually:
# Terminal 1: Laravel
php artisan serve

# Terminal 2: Vite (Vue)
npm run dev
```

Open **http://localhost:8000**

---

## 📚 Documentation

- **[RAILWAY_DEPLOY.md](RAILWAY_DEPLOY.md)** - Complete deployment guide
- **[RAILWAY_ENV.md](RAILWAY_ENV.md)** - Environment variables reference
- **[CHECKLIST.md](CHECKLIST.md)** - Pre-deployment checklist
- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Alternative deployment options

---

## 🔧 Configuration

### Environment Variables

```bash
# Application
APP_NAME=CompBuddy
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=sqlite

# AI Services
GROQ_API_KEY=your_groq_key
SEMANTIC_SCHOLAR_API_KEY=your_ss_key
```

Full list: `.env.example`

---

## 🎓 Academic Context

Proyek ini dibuat untuk:
- **Mata Kuliah:** PBKK (Pemrograman Berbasis Kerangka Kerja)
- **Semester:** 7
- **Institusi:** Institut Teknologi Sepuluh Nopember (ITS)

---

## 📸 Screenshots

### Chat Interface
![Chat Interface](docs/screenshot-chat.png)

### Reference Panel
![References](docs/screenshot-references.png)

### Citation Generator
![Citation](docs/screenshot-citation.png)

---

## 🛠️ Tech Details

### Backend Architecture
```
app/
├── Http/Controllers/
│   └── ChatController.php    # Main chat logic
├── Models/
│   └── User.php              # User model with 2FA
└── ...
```

### Frontend Architecture
```
resources/js/
├── pages/
│   └── Chat/Main.vue         # Main chat UI
├── components/               # Reusable components
├── layouts/                  # Page layouts
└── app.ts                    # Inertia app entry
```

### Key Dependencies
- `laravel/framework: ^12.0`
- `inertiajs/inertia-laravel: ^2.0`
- `vue: ^3.5`
- `tailwindcss: ^4.1`
- `laravel/fortify: ^1.30` (Authentication)

---

## 🤝 Contributing

Contributions welcome! Please:
1. Fork repository
2. Create feature branch
3. Commit changes
4. Push to branch
5. Open Pull Request

---

## 📄 License

MIT License - see [LICENSE](LICENSE) file

---

## 🆘 Support

- **Issues:** [GitHub Issues](https://github.com/afadhilah/apaaja/issues)
- **Railway Help:** [Railway Discord](https://discord.gg/railway)
- **Email:** your-email@example.com

---

## 🎉 Credits

- **AI Model:** Groq (Llama 3.3 70B)
- **Paper Database:** Semantic Scholar
- **Framework:** Laravel + Vue.js
- **Deployment:** Railway

---

**Made with ❤️ for PBKK ITS**

Happy researching! 🚀📚
