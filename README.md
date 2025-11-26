# FirmWizard Onboarding App

> AI-powered onboarding wizard for law firms with automated marketing profile generation and intelligent insights.

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php)](https://php.net)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3.x-38B2AC?logo=tailwind-css)](https://tailwindcss.com)
[![OpenAI](https://img.shields.io/badge/OpenAI-API-412991?logo=openai)](https://openai.com)

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & npm
- MySQL 8.0+
- OpenAI API key

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/mdameron76/app.firmwizard.com.git
cd app.firmwizard.com/app
```

2. **Install PHP dependencies**
```bash
composer install
```

3. **Install Node dependencies**
```bash
npm install
```

4. **Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Update `.env` with your credentials**
```env
DB_DATABASE=app_firmwizard
DB_USERNAME=root
DB_PASSWORD=your_password

OPENAI_API_KEY=your_openai_api_key
```

6. **Run migrations**
```bash
php artisan migrate
```

7. **Build frontend assets**
```bash
npm run build
```

8. **Start the development server**
```bash
php artisan serve
```

9. **Start queue worker (separate terminal)**
```bash
php artisan queue:work
```

Visit: `http://localhost:8000`

## ✨ Features

### 🧙 5-Step Onboarding Wizard
- **Step 1**: Brand Voice & Pain Points with AI suggestions
- **Step 2**: Practice Areas & Specializations
- **Step 3**: Target Client Demographics
- **Step 4**: Geographic Service Areas
- **Step 5**: Marketing Goals & Budget

### 🤖 AI Agent System
10 intelligent agents for automated insights:
- 📝 **Blog Post Topics** - SEO-optimized content ideas
- 🔍 **Competitor Analysis** - Competitive landscape insights
- 🌐 **Website Analysis** - Performance and SEO audit
- 📍 **Google Business Profile** - Local SEO optimization
- 💰 **Google Ads Strategy** - PPC campaign recommendations
- 🎯 **Local Services Ads** - LSA optimization tips
- 📱 **Meta Ads Strategy** - Facebook/Instagram campaigns
- 🔗 **Backlink Opportunities** - Link building suggestions
- 📊 **Analytics Insights** - Traffic and behavior analysis
- 🏢 **Firm Profile Review** - Profile optimization recommendations

### 📊 Dashboard
- Real-time KPI metrics (leads, visibility, AI jobs)
- AI Agents Hub with one-click agent triggering
- Recent activity feed
- Onboarding progress tracking

### 🔌 RESTful API
- Sanctum token authentication
- Complete CRUD operations
- OpenAI-powered suggestions
- N8N webhook integration
- Metrics time-series data

## 🏗️ Architecture

### Technology Stack
- **Backend**: Laravel 12, PHP 8.2+
- **Database**: MySQL 8.0
- **Authentication**: Laravel Breeze + Sanctum
- **Frontend**: Blade Templates, Tailwind CSS, Alpine.js
- **AI Integration**: OpenAI PHP Client (GPT-4o)
- **Queue System**: Laravel Queue for background jobs

### Design Patterns
- Service Layer Pattern (OnboardingService, OpenAiSuggestionService)
- Factory Pattern (AgentFactory for AI agents)
- Repository Pattern (Eloquent ORM)
- Queue Pattern (ProcessAiJob for async execution)

### Database Schema
9 tables with optimized relationships:
- `firms`, `firm_user`, `firm_profiles`
- `firm_onboarding_sessions`, `ai_firm_profiles`, `ai_firm_profile_versions`
- `firm_metrics_daily`, `firm_metrics_monthly`, `ai_jobs`

## 📖 Documentation

See [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md) for:
- Complete API documentation
- Database schema details
- Service class architecture
- Deployment instructions
- Future enhancement roadmap

## 🎨 Branding

Custom FirmWizard color palette:
- Dark: `#2a1437`
- Darker: `#220f2d`
- Accent: `#8fd4d9`
- Gold: `#c99c58`

## 🔐 Security

- CSRF protection on all forms
- Sanctum token authentication
- SQL injection prevention via Eloquent
- XSS protection via Blade templating
- Password hashing with bcrypt
- Foreign key constraints

## 🧪 Testing

```bash
# Run PHPUnit tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
```

## 📝 API Examples

### Trigger AI Agent
```bash
POST /api/firms/{firm}/ai-jobs
Content-Type: application/json
Authorization: Bearer {token}

{
  "job_type": "blog_post",
  "input_data": {}
}
```

### Get AI Suggestions
```bash
POST /api/firms/{firm}/ai-suggestions/brand-voice
Content-Type: application/json
Authorization: Bearer {token}

{
  "firm_description": "Personal injury law firm in Chicago..."
}
```

### Save Wizard Step
```bash
POST /api/firms/{firm}/onboarding/step/1
Content-Type: application/json
Authorization: Bearer {token}

{
  "brand_voice": "Professional and empathetic",
  "pain_points": ["Legal complexity", "Cost concerns"]
}
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is proprietary software. All rights reserved.

## 🙏 Acknowledgments

- [Laravel](https://laravel.com) - The PHP framework
- [Tailwind CSS](https://tailwindcss.com) - Utility-first CSS framework
- [OpenAI](https://openai.com) - AI-powered suggestions
- [Alpine.js](https://alpinejs.dev) - Minimal JavaScript framework

---

**Built with ❤️ for law firms by FirmWizard**

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
