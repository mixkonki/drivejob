# DriveJob - Οδηγός Αρχιτεκτονικής και Δομής Έργου

## Πίνακας Περιεχομένων
1. [Δομή Φακέλων](#δομή-φακέλων)
2. [Δομή Βάσης Δεδομένων](#δομή-βάσης-δεδομένων)
3. [Αρχιτεκτονική Εφαρμογής](#αρχιτεκτονική-εφαρμογής)
4. [Σύστημα Authentication](#σύστημα-authentication)
5. [API Endpoints](#api-endpoints)
6. [Frontend Components](#frontend-components)

---

## Δομή Φακέλων

```
drivejob/
├── config/                 # Αρχεία ρυθμίσεων
│   ├── database.php       # Ρυθμίσεις βάσης δεδομένων
│   ├── routes.php         # Ορισμός routes
│   └── services.php       # Service container bindings
│
├── database/              
│   └── migrations/        # Database migrations
│
├── docs/                  # Τεκμηρίωση έργου
│
├── public/                # Publicly accessible files
│   ├── index.php         # Entry point
│   ├── api/              # API endpoints
│   │   └── matching/     # AI matching endpoints
│   ├── companies/        # Company-related pages
│   ├── drivers/          # Driver-related pages
│   ├── admin/            # Admin panel
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   └── uploads/          # User uploads
│
├── src/                   # Source code
│   ├── Controllers/       # MVC Controllers
│   │   ├── Api/          # API Controllers
│   │   ├── Admin/        # Admin Controllers
│   │   ├── Company/      # Company Controllers
│   │   └── Driver/       # Driver Controllers
│   ├── Core/             # Core framework classes
│   ├── Middleware/       # Request middleware
│   ├── Models/           # Data models
│   ├── Services/         # Business logic services
│   │   └── AI/           # AI/Matching services
│   ├── Views/            # View templates
│   │   ├── admin/        
│   │   ├── companies/    
│   │   └── drivers/      
│   └── Utils/            # Utility classes
│
└── templates/            # Email templates
```

---

## Δομή Βάσης Δεδομένων

### 1. Πίνακας `users` (για drivers και admins)
```sql
- id (int, PK)
- username (varchar 255)
- email (varchar 100)
- password (varchar 255)
- created_at (timestamp)
- role (enum: 'driver', 'company', 'admin')
- role_id (int)
- is_active (tinyint)
- last_login (datetime)
- login_attempts (int)
- locked_until (datetime)
```

### 2. Πίνακας `companies` (αυτόνομος πίνακας για εταιρείες)
```sql
- id (int, PK)
- email (varchar 191, UNIQUE) - Login credential
- password (varchar 255)
- company_name (varchar 255)
- phone (varchar 20)
- address (text)
- is_verified (tinyint)
- is_active (tinyint)
- created_at (timestamp)
- city (varchar 100)
- country (varchar 100)
- vat_number (varchar 50)
- rating (decimal 3,2)
- status (enum: 'active', 'inactive', 'pending', 'suspended')
- fleet_size (int)
- active_drivers (int)
... (και άλλα πεδία)
```

### 3. Πίνακας `drivers` (αυτόνομος πίνακας για οδηγούς)
```sql
- id (int, PK)
- email (varchar 191, UNIQUE) - Login credential
- password (varchar 255)
- first_name (varchar 255)
- last_name (varchar 255)
- phone (varchar 20)
- is_verified (tinyint)
- is_active (tinyint)
- created_at (timestamp)
- city (varchar 100)
- experience_years (int)
- available_for_work (tinyint)
- rating (decimal 3,2)
- license_number (varchar 30)
- adr_certificate (tinyint)
... (και άλλα πεδία)
```

### 4. Πίνακας `job_listings`
```sql
- id (int, PK)
- company_id (int, FK -> companies.id)
- title (varchar 255)
- description (text)
- location (varchar 255)
- is_active (tinyint)
- created_at (timestamp)
... (και άλλα πεδία)
```

### 5. Πίνακας `matching_scores` (AI matching results)
```sql
- id (int, PK)
- driver_id (int, FK -> drivers.id)
- job_id (int, FK -> job_listings.id)
- overall_score (decimal 5,4)
- skill_match_score (decimal 5,4)
- location_match_score (decimal 5,4)
- experience_match_score (decimal 5,4)
- availability_match_score (decimal 5,4)
- created_at (timestamp)
- updated_at (timestamp)
```

### 6. Άλλοι σημαντικοί πίνακες
- `driver_skills` - Δεξιότητες οδηγών
- `driver_certifications` - Πιστοποιήσεις οδηγών
- `driver_vehicle_experience` - Εμπειρία με οχήματα
- `company_reviews` - Αξιολογήσεις εταιρειών
- `sessions` - PHP sessions

---

## Αρχιτεκτονική Εφαρμογής

### MVC Pattern
```
Request → Router → Controller → Service → Model → Database
                        ↓
                      View → Response
```

### Βασικές Κλάσεις

#### Core Classes
- `Core\Database` - Database connection singleton
- `Core\Router` - Request routing
- `Core\Controller` - Base controller
- `Core\Session` - Session management
- `Core\JsonResponse` - JSON response helper

#### Services
- `Services\AI\MatchingService` - AI matching logic
- `Services\AI\MatchingCacheService` - Caching για matching
- `Services\CompanyService` - Company business logic
- `Services\DriverService` - Driver business logic

#### Middleware
- `Middleware\AuthMiddleware` - Web authentication
- `Middleware\ApiAuthMiddleware` - API authentication
- `Middleware\AdminMiddleware` - Admin access control

---

## Σύστημα Authentication

### Τρεις ξεχωριστοί τύποι χρηστών:

1. **Drivers**
   - Login: `/login.php`
   - Credentials στον πίνακα `drivers`
   - Session: `$_SESSION['user_role'] = 'driver'`
   - Session: `$_SESSION['user_id'] = driver.id`

2. **Companies**
   - Login: `/login.php`
   - Credentials στον πίνακα `companies`
   - Session: `$_SESSION['user_role'] = 'company'`
   - Session: `$_SESSION['user_id'] = company.id`

3. **Admins**
   - Login: `/admin/login.php`
   - Credentials στον πίνακα `users` με role='admin'
   - Session: `$_SESSION['user_role'] = 'admin'`
   - Session: `$_SESSION['user_id'] = user.id`

### Session Structure
```php
$_SESSION = [
    'user_id' => int,        // ID από τον αντίστοιχο πίνακα
    'user_role' => string,   // 'driver', 'company', 'admin'
    'user_email' => string,
    'user_name' => string,   // Όνομα ή company name
    'logged_in' => bool
];
```

---

## API Endpoints

### Matching API
- `GET /api/matching/job/candidates/` - Λήψη υποψηφίων για αγγελία
  - Parameters: `job_id`, `limit`
  - Auth: Company session required
  
- `GET /api/matching/driver/jobs/` - Λήψη αγγελιών για οδηγό
  - Parameters: `limit`
  - Auth: Driver session required

### Response Format
```json
{
    "success": true,
    "data": {
        "candidates": [...],
        "count": 5
    }
}
```

---

## Frontend Components

### Company Dashboard Widgets
1. **AI Candidates Widget** (`candidates-widget-v2.php`)
   - Location: `src/Views/companies/partials/`
   - Εμφανίζει προτεινόμενους υποψήφιους ανά αγγελία
   - AJAX calls στο matching API

### Σημαντικά JavaScript Patterns
```javascript
// AJAX call pattern
fetch(`${BASE_URL}api/matching/job/candidates/?job_id=${jobId}&limit=5`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Handle success
        }
    });
```

---

## Σημαντικές Σημειώσεις

### 1. Database Relations
- **ΔΕΝ** υπάρχει σχέση μεταξύ `users` και `companies`/`drivers`
- Κάθε τύπος χρήστη έχει τον δικό του πίνακα με credentials

### 2. File Paths
- Όλα τα paths στο PHP είναι relative στο root του project
- Το `BASE_URL` χρησιμοποιείται για absolute URLs στο frontend

### 3. Error Handling
- API endpoints επιστρέφουν JSON με `success: false` σε περίπτωση σφάλματος
- Web pages κάνουν redirect σε error pages

### 4. Security
- Όλα τα API endpoints ελέγχουν authentication
- CSRF protection μέσω sessions
- SQL injection protection μέσω prepared statements

---

## Debugging Tips

### Check Session
```php
Session::start();
var_dump($_SESSION);
```

### Check Database Connection
```php
$pdo = \Drivejob\Core\Database::getInstance()->getConnection();
```

### API Testing
- Χρήση browser developer tools (Network tab)
- Έλεγχος response headers και status codes
