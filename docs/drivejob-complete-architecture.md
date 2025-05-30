# DriveJob - Πλήρης Οδηγός Αρχιτεκτονικής

## Πίνακας Περιεχομένων
1. [Δομή Φακέλων](#δομή-φακέλων)
2. [Δομή Βάσης Δεδομένων](#δομή-βάσης-δεδομένων)
3. [Σύστημα Authentication - Λεπτομερής Ανάλυση](#σύστημα-authentication)
4. [API Architecture](#api-architecture)
5. [Session Management](#session-management)
6. [Routing System](#routing-system)
7. [Frontend Components](#frontend-components)
8. [Security Measures](#security-measures)

---

## Δομή Φακέλων

```
drivejob/
├── config/                 # Αρχεία ρυθμίσεων
│   ├── database.php       # Database configuration
│   ├── routes.php         # Route definitions
│   └── services.php       # Service container bindings
│
├── database/              
│   └── migrations/        # Database migrations
│
├── docs/                  # Project documentation
│
├── public/                # Publicly accessible files
│   ├── index.php         # Main entry point
│   ├── api/              # API endpoints
│   │   ├── matching/     # AI matching endpoints
│   │   │   └── job/
│   │   │       └── candidates/
│   │   │           └── index.php
│   │   └── test-session.php
│   ├── companies/        # Company pages
│   ├── drivers/          # Driver pages
│   ├── admin/            # Admin panel
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   └── uploads/          # User uploads
│
├── src/                   # Source code
│   ├── Controllers/       
│   │   ├── AuthController.php
│   │   ├── BaseUserController.php
│   │   ├── Api/          
│   │   │   └── MatchingController.php
│   │   ├── Admin/        
│   │   ├── Company/      
│   │   │   └── CompaniesController.php
│   │   └── Driver/       
│   │       └── DriversController.php
│   ├── Core/             
│   │   ├── Database.php
│   │   ├── Router.php
│   │   ├── Session.php
│   │   ├── JsonResponse.php
│   │   └── CSRF.php
│   ├── Middleware/       
│   │   ├── AuthMiddleware.php
│   │   └── ApiAuthMiddleware.php
│   ├── Models/           
│   │   ├── AuthModel.php
│   │   ├── Driver/
│   │   │   └── ProfileModel.php
│   │   └── Company/
│   │       └── CompaniesModel.php
│   ├── Services/         
│   │   └── AI/           
│   │       ├── MatchingService.php
│   │       └── MatchingCacheService.php
│   └── Views/            
│       ├── companies/    
│       │   ├── company-profile.php
│       │   └── partials/
│       │       └── candidates-widget-v2.php
│       └── drivers/      
└── templates/            # Email templates
```

---

## Δομή Βάσης Δεδομένων

### 1. Πίνακας `users` (ΜΟΝΟ για admins)
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    role ENUM('driver', 'company', 'admin') DEFAULT 'admin',
    role_id INT,
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME,
    login_attempts INT DEFAULT 0,
    locked_until DATETIME
);
```

### 2. Πίνακας `companies` (Αυτόνομος για εταιρείες)
```sql
CREATE TABLE companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(191) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    is_verified TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP,
    city VARCHAR(100),
    country VARCHAR(100),
    vat_number VARCHAR(50),
    rating DECIMAL(3,2),
    status ENUM('active', 'inactive', 'pending', 'suspended') DEFAULT 'pending',
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_token_expires_at DATETIME,
    -- Πολλά άλλα πεδία για fleet management, etc.
);
```

### 3. Πίνακας `drivers` (Αυτόνομος για οδηγούς)
```sql
CREATE TABLE drivers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(191) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP,
    city VARCHAR(100),
    experience_years INT,
    available_for_work TINYINT(1) DEFAULT 1,
    rating DECIMAL(3,2),
    license_number VARCHAR(30),
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_token_expires_at DATETIME,
    -- Πολλά άλλα πεδία για skills, certifications, etc.
);
```

### 4. Πίνακας `job_listings`
```sql
CREATE TABLE job_listings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id)
);
```

### 5. Πίνακας `matching_scores`
```sql
CREATE TABLE matching_scores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    driver_id INT NOT NULL,
    job_id INT NOT NULL,
    overall_score DECIMAL(5,4),
    skill_match_score DECIMAL(5,4),
    location_match_score DECIMAL(5,4),
    experience_match_score DECIMAL(5,4),
    availability_match_score DECIMAL(5,4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id),
    FOREIGN KEY (job_id) REFERENCES job_listings(id),
    UNIQUE KEY unique_match (driver_id, job_id)
);
```

---

## Σύστημα Authentication

### Αρχιτεκτονική Authentication

#### 1. Controllers Hierarchy
```
BaseController
    └── BaseUserController
            └── AuthController
```

#### 2. AuthModel - Κεντρικό Σύστημα Authentication

**Κύρια Μέθοδος:**
```php
public function authenticate($email, $password, $role = null)
{
    // Ελέγχει και τους 3 πίνακες με την εξής σειρά:
    // 1. drivers (αν $role = 'driver' ή null)
    // 2. companies (αν $role = 'company' ή null)  
    // 3. users (αν $role = 'admin' ή null)
    
    // Επιστρέφει:
    return [
        'user_id' => $id,           // ID από τον αντίστοιχο πίνακα
        'role' => 'driver|company|admin',
        'email' => $email,
        'name' => $name,            // Full name ή company name
        'is_verified' => bool,
        'is_active' => bool
    ];
}
```

#### 3. Login Flow

1. **Route:** `/auth/login` (GET/POST)
2. **Controller:** `AuthController::login()`
3. **Process:**
   ```
   User Input → AuthController → AuthModel::authenticate()
                                        ↓
                              Check drivers table
                                        ↓
                              Check companies table
                                        ↓
                              Check users table (admins)
                                        ↓
                              Create Session → Redirect
   ```

#### 4. Session Creation
```php
// Μετά από επιτυχή authentication:
Session::set('user_id', $user['user_id']);      // ID από τον αντίστοιχο πίνακα
Session::set('user_role', $user['role']);        // 'driver', 'company', 'admin'
Session::set('user_name', $user['name']);        
Session::set('user_email', $user['email']);      // Προστέθηκε από τον controller
```

#### 5. Redirects μετά Login
- **Drivers:** `/drivers/profile`
- **Companies:** `/companies/profile`
- **Admins:** `/admin/monitoring/dashboard`

---

## Session Management

### Session Structure
```php
$_SESSION = [
    // Core authentication
    'user_id' => int,           // ID από drivers/companies/users table
    'user_role' => string,      // 'driver', 'company', 'admin'
    'user_email' => string,     
    'user_name' => string,      // Full name ή company name
    
    // Security fields
    '_user_ip' => string,       // IP address για security
    '_user_agent' => string,    // Browser user agent
    '_last_activity' => int,    // Unix timestamp
    
    // CSRF Protection
    'csrf_token' => string,     // CSRF token
    
    // Temporary data
    'error_message' => string,  // Flash messages
    'success_message' => string,
    'redirect_after_login' => string
];
```

### Session Security
- Session regeneration μετά login
- IP και User Agent validation
- Activity timeout checks
- CSRF token per session

---

## API Architecture

### API Endpoints Structure

#### 1. Matching API για Companies
**Endpoint:** `/api/matching/job/candidates/index.php`
- **Method:** GET
- **Auth:** Company session required
- **Parameters:** 
  - `job_id` (required)
  - `limit` (optional, default: 5)
- **Response:**
```json
{
    "success": true,
    "data": {
        "candidates": [
            {
                "driver_id": 1,
                "name": "John Doe",
                "email": "john@example.com",
                "city": "Athens",
                "experience_years": 5,
                "rating": 4.5,
                "match_score": 85.5,
                "recommendation": "Εξαιρετική αντιστοιχία!"
            }
        ],
        "count": 5
    }
}
```

#### 2. API Authentication Check
```php
// Κάθε API endpoint ελέγχει:
if (!Session::has('user_id') || Session::get('user_role') !== 'company') {
    JsonResponse::error('Unauthorized', 401);
    exit;
}
```

---

## Routing System

### Route Definitions (config/routes.php)

#### 1. Authentication Routes
```php
$router->group(['prefix' => 'auth'], function ($router) {
    $router->get('/login', [AuthController::class, 'showLoginForm']);
    $router->post('/login', [AuthController::class, 'login']);
    $router->get('/logout', [AuthController::class, 'logout']);
    $router->get('/verify/{token}', [AuthController::class, 'verify']);
    // ... more auth routes
});
```

#### 2. Company Routes
```php
$router->group(['prefix' => 'companies'], function ($router) {
    $router->get('/profile', [CompaniesController::class, 'profile']);
    $router->get('/edit-profile', [CompaniesController::class, 'edit']);
    // ... more company routes
});
```

---

## Frontend Components

### AI Candidates Widget

**File:** `src/Views/companies/partials/candidates-widget-v2.php`

**Features:**
- Dropdown για επιλογή αγγελίας
- AJAX loading των υποψηφίων
- Display matching scores
- Responsive design

**JavaScript Integration:**
```javascript
// Widget initialization
document.addEventListener('DOMContentLoaded', function() {
    const jobSelect = document.getElementById('jobSelect');
    const BASE_URL = '<?php echo BASE_URL; ?>';
    
    jobSelect.addEventListener('change', function() {
        const jobId = this.value;
        if (jobId) {
            loadCandidates(jobId);
        }
    });
    
    function loadCandidates(jobId) {
        fetch(`${BASE_URL}api/matching/job/candidates/?job_id=${jobId}&limit=5`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayCandidates(data.data.candidates);
                }
            });
    }
});
```

---

## Security Measures

### 1. SQL Injection Protection
- Όλα τα queries χρησιμοποιούν prepared statements
- PDO με parameterized queries

### 2. XSS Protection
- Output escaping με `htmlspecialchars()`
- Content-Type headers σε API responses

### 3. CSRF Protection
- CSRF tokens σε όλες τις forms
- Token validation στο backend

### 4. Session Security
- Session regeneration
- IP validation
- User agent checking
- Activity timeouts

### 5. Password Security
- Bcrypt hashing με `password_hash()`
- Minimum 8 characters requirement

---

## Common Issues & Solutions

### Issue 1: API Returns 500 Error
**Αιτία:** Session mismatch ή λάθος authentication
**Λύση:** Έλεγχος session data και user role

### Issue 2: Cannot Find User After Login
**Αιτία:** Αναζήτηση σε λάθος πίνακα
**Λύση:** 
- Companies → `companies` table
- Drivers → `drivers` table
- Admins → `users` table

### Issue 3: Widget Not Loading Candidates
**Αιτία:** Job δεν ανήκει στην εταιρεία
**Λύση:** Έλεγχος `job_listings.company_id = $_SESSION['user_id']`

---

## Development Guidelines

1. **Νέα API Endpoints:** Πάντα έλεγχος authentication
2. **Database Queries:** Χρήση prepared statements
3. **Session Access:** Πάντα `Session::start()` πρώτα
4. **Error Handling:** Log errors, return user-friendly messages
5. **Testing:** Δημιουργία test scripts στο public/ για debugging
