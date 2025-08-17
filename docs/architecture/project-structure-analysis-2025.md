# 🏗️ DriveJob Project Structure Analysis - 2025

## 📋 Επισκόπηση Έργου

Το DriveJob είναι μια πλατφόρμα σύνδεσης οδηγών και εταιρειών με προηγμένα AI features και ολοκληρωμένο σύστημα διαχείρισης.

## 🗂️ Δομή Καταλόγων

### Root Directory
```
drivejob/
├── backup/                 # Αντίγραφα ασφαλείας
├── config/                 # Ρυθμίσεις συστήματος
├── database/               # Migrations & DB scripts
├── docs/                   # Τεκμηρίωση
├── logs/                   # System logs
├── mcp-servers/           # MCP servers για AI
├── public/                # Public web files
├── routes/                # Routing definitions
├── src/                   # Core application code
├── templates/             # Email templates
├── tests/                 # Unit & functional tests
├── uploads/               # User uploads
└── composer.json          # PHP dependencies
```

## 🔐 1. ΣΥΣΤΗΜΑ AUTHENTICATION (LOGIN)

### Αρχεία Κλειδιά:
- `src/Controllers/AuthController.php` - Κεντρικός controller
- `src/Views/auth/login.php` - Login form
- `config/routes.php` - Auth routes
- `src/Middleware/AuthenticationMiddleware.php` - Auth middleware

### Routes:
```php
/auth/login (GET/POST)
/auth/logout
/auth/verify/{token}
/auth/password-reset
/auth/access-denied
```

### Session Management:
- Χρησιμοποιεί `$_SESSION['user_id']`, `$_SESSION['user_role']`
- Roles: 'admin', 'driver', 'company'
- Redirect based on role after login

### Features:
- ✅ Password reset με email
- ✅ Account verification
- ✅ CSRF protection
- ✅ Role-based redirects
- ✅ Session management

## 🛣️ 2. ΣΥΣΤΗΜΑ ROUTING

### Αρχεία Κλειδιά:
- `config/routes.php` - Main routing configuration
- `routes/api.php` - API routes
- `routes/web.php` - Web routes
- `src/Core/Router.php` - Router class

### Route Groups:
```php
/auth/*          # Authentication
/drivers/*       # Driver functionality
/companies/*     # Company functionality
/job-listings/*  # Job listings
/admin/*         # Admin panel
/api/*           # API endpoints
/matching/*      # AI matching system
```

### Features:
- ✅ RESTful routing
- ✅ Route groups με prefixes
- ✅ Named routes
- ✅ Middleware support
- ✅ Parameter binding

## 👥 3. ΣΥΣΤΗΜΑ ΕΓΓΡΑΦΗΣ

### Driver Registration:
- `src/Controllers/Driver/DriversController.php`
- `src/Views/drivers/drivers-registration.php`
- Route: `/drivers/register`

### Company Registration:
- `src/Controllers/Company/CompaniesController.php`
- `src/Views/companies/company-registration.php`
- Route: `/companies/register`

### Features:
- ✅ Multi-step registration
- ✅ Email verification
- ✅ Profile completion
- ✅ Document upload
- ✅ Validation & sanitization

## 🤖 4. ΣΥΣΤΗΜΑ AI

### Αρχεία Κλειδιά:
- `src/Services/EnterpriseAIService.php` - Main AI service
- `config/openai.php` - OpenAI configuration
- `public/admin/ai-settings.php` - AI admin panel
- `mcp-servers/openai-server/` - MCP server για AI

### AI Models Configuration:
```php
- Matching Model: o1-preview (ChatGPT-5)
- Insights Model: o1-mini
- Analysis Model: gpt-4o
- General Model: gpt-4o-mini
```

### Database Tables:
- `ai_configuration` - AI settings
- `ai_models` - Available models
- `ai_matching_results` - Matching results
- `match_history` - Match tracking

### Features:
- ✅ OpenAI Integration
- ✅ Multiple model support
- ✅ AI-powered matching
- ✅ Configuration management
- ✅ Performance monitoring

## 📧 5. ΣΥΣΤΗΜΑ EMAIL

### Αρχεία Κλειδιά:
- `config/email.php` - Email configuration
- `templates/email_template.php` - Base template
- `templates/emails/` - Email templates
- `src/Services/EmailService.php` - Email service

### Email Types:
- Welcome emails
- Password reset
- Account verification
- Job notifications
- Match notifications

### Features:
- ✅ HTML email templates
- ✅ SMTP configuration
- ✅ Queue support
- ✅ Template system

## 🎯 6. ΣΥΣΤΗΜΑ MATCHING

### Αρχεία Κλειδιά:
- `src/Controllers/MatchingController.php`
- `src/Services/MatchingService.php`
- `src/Models/MatchingModel.php`

### Database Tables:
- `match_preferences` - User preferences
- `match_history` - Match tracking
- `ai_matching_results` - AI results

### Features:
- ✅ AI-powered matching
- ✅ Preference-based filtering
- ✅ Score calculation
- ✅ Match history tracking

## 📊 7. ΣΥΣΤΗΜΑ ADMIN

### Αρχεία Κλειδιά:
- `src/Controllers/Admin/AdminController.php`
- `src/Controllers/Admin/SystemMonitoringController.php`
- `src/Views/Admin/` - Admin views
- `public/admin/` - Admin assets

### Admin Routes:
```php
/admin/dashboard
/admin/users
/admin/job-listings
/admin/analytics
/admin/settings
/admin/monitoring/*
```

### Features:
- ✅ User management
- ✅ System monitoring
- ✅ Analytics dashboard
- ✅ Settings management
- ✅ Activity logs

## 💼 8. ΣΥΣΤΗΜΑ JOB LISTINGS

### Αρχεία Κλειδιά:
- `src/Controllers/UnifiedJobListingController.php`
- `src/Models/JobListingModel.php`
- `src/Views/job-listings/`

### Features:
- ✅ Create/Edit/Delete listings
- ✅ Search & filtering
- ✅ Application system
- ✅ Company/Driver specific views

## 🚛 9. ΣΥΣΤΗΜΑ DRIVERS

### Αρχεία Κλειδιά:
- `src/Controllers/Driver/DriversController.php`
- `src/Models/Driver/ProfileModel.php`
- `src/Views/drivers/`

### Features:
- ✅ Profile management
- ✅ Vehicle experience
- ✅ Certifications
- ✅ Rating system
- ✅ Availability status

## 🏢 10. ΣΥΣΤΗΜΑ COMPANIES

### Αρχεία Κλειδιά:
- `src/Controllers/Company/CompaniesController.php`
- `src/Models/Company/CompaniesModel.php`
- `src/Views/companies/`

### Features:
- ✅ Company profiles
- ✅ Job posting
- ✅ Driver search
- ✅ Review system

## 💬 11. ΣΥΣΤΗΜΑ MESSAGING

### Database Tables:
- `conversations`
- `messages`
- `message_participants`

### Features:
- ✅ Real-time messaging
- ✅ Conversation threads
- ✅ File attachments
- ✅ Read receipts

## 📱 12. API SYSTEM

### Αρχεία Κλειδιά:
- `routes/api.php`
- `src/Api/` - API controllers
- `public/api/` - API endpoints

### API Endpoints:
```php
/api/matching/*
/api/messages/*
/api/jobs/*
/api/users/*
```

## 🗄️ 13. DATABASE SYSTEM

### Migrations:
- `database/migrations/` - 30+ migration files
- Auto-migration system
- Schema versioning

### Key Tables:
- `users` - User accounts
- `drivers` - Driver profiles
- `companies` - Company profiles
- `job_listings` - Job postings
- `ai_*` - AI related tables
- `match_*` - Matching system tables

## 🔧 14. CONFIGURATION SYSTEM

### Config Files:
- `config/database.php` - DB settings
- `config/email.php` - Email settings
- `config/openai.php` - AI settings
- `config/routes.php` - Routing
- `config/services.php` - Service bindings

## 🧪 15. TESTING SYSTEM

### Test Structure:
- `tests/Unit/` - Unit tests
- `tests/Functional/` - Functional tests
- `tests/Mocks/` - Mock objects
- PHPUnit configuration

## 📈 16. MONITORING SYSTEM

### Features:
- System health monitoring
- Performance metrics
- Error tracking
- Usage analytics
- Database backup

## 🔍 ΠΡΟΒΛΗΜΑΤΑ ΠΟΥ ΕΝΤΟΠΙΣΤΗΚΑΝ

### 1. Settings Page Redirect Issue
**Πρόβλημα:** `/admin/settings` ανακατευθύνει στο dashboard
**Αιτία:** Το AdminController::settings() δεν βρίσκει το view
**Λύση:** Δημιουργήθηκε το `src/Views/Admin/settings.php`

### 2. Session Management
**Πρόβλημα:** Inconsistent session variables
**Αιτία:** Μερικά αρχεία χρησιμοποιούν `$_SESSION['role']` αντί για `$_SESSION['user_role']`
**Λύση:** Standardization needed

### 3. Login System
**Πρόβλημα:** Παλιά αρχεία redirect σε `/auth/login` που δεν υπάρχει
**Λύση:** Χρήση του σωστού routing system

## 🚀 ΕΠΟΜΕΝΑ ΒΗΜΑΤΑ

1. **Διόρθωση Settings Page**
2. **Session Management Standardization**
3. **API Documentation**
4. **Performance Optimization**
5. **Security Audit**
6. **Mobile Responsiveness**
7. **Real-time Features**

## 📊 ΣΤΑΤΙΣΤΙΚΑ ΕΡΓΟΥ

- **Total Files:** 200+ PHP files
- **Database Tables:** 25+ tables
- **Routes:** 100+ defined routes
- **Controllers:** 15+ controllers
- **Models:** 20+ models
- **Views:** 50+ view files
- **Migrations:** 30+ migrations
- **Tests:** Multiple test suites

## 🏆 ΤΕΧΝΟΛΟΓΙΕΣ

- **Backend:** PHP 8.x, Custom MVC Framework
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5
- **AI:** OpenAI GPT-4/5 models
- **Email:** SMTP
- **Testing:** PHPUnit
- **Version Control:** Git
- **Dependency Management:** Composer

---

*Τελευταία ενημέρωση: Ιανουάριος 2025*
