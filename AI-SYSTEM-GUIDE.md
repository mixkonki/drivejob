# 🤖 Οδηγός AI Matching System

## 📋 Πώς να χρησιμοποιήσετε τα Test Scripts

### 1. **Debug Script** - Διάγνωση Προβλημάτων
```
http://localhost/drivejob/public/debug-ai-system.php
```

Αυτό το script ελέγχει:
- ✅ Αν υπάρχουν οι απαραίτητοι πίνακες στη βάση
- ✅ Αν υπάρχουν τα αρχεία των services
- ✅ Αν υπάρχουν δεδομένα για testing
- ✅ Αν λειτουργούν τα API endpoints

**Τι να κάνετε:**
1. Ανοίξτε το link
2. Δείτε ποια σημεία έχουν ❌ (κόκκινο)
3. Ακολουθήστε τις οδηγίες για κάθε σφάλμα
4. Πατήστε "Test Driver Matches API" για live test

### 2. **API Test Script** - Έλεγχος Endpoints
```
http://localhost/drivejob/public/test-api-endpoints.php
```

Ελέγχει όλα τα API endpoints:
- Driver Matches
- Job Candidates
- Calculate Match
- Match Insights
- Company Jobs

**Τι να κάνετε:**
1. Πατήστε κάθε "Test" button
2. Δείτε την απάντηση (πράσινο = OK, κόκκινο = πρόβλημα)

### 3. **Widget Test Script** - Προεπισκόπηση Widgets
```
http://localhost/drivejob/public/test-ai-widgets.php
```

Σας επιτρέπει να:
- Συνδεθείτε ως Driver ή Company (mock login)
- Δείτε τα widgets σε action
- Δοκιμάσετε τη λειτουργικότητα

**Τι να κάνετε:**
1. Επιλέξτε "Σύνδεση ως Οδηγός" (ID: 26)
2. Δείτε το widget με τις προτάσεις
3. Ή επιλέξτε "Σύνδεση ως Εταιρεία" για το company widget

## 🔧 Αν κάτι δεν λειτουργεί:

### Πρόβλημα 1: "Ο πίνακας matching_scores ΔΕΝ υπάρχει"
**Λύση:**
```bash
cd c:/wamp64/www/drivejob
php database/migrations/create_ai_matching_tables.php
```

### Πρόβλημα 2: "Ο πίνακας drivers ΔΕΝ έχει τα πεδία location"
**Λύση:**
```bash
cd c:/wamp64/www/drivejob
php database/migrations/add_driver_fields.php
```

### Πρόβλημα 3: "404 Error στα API calls"
**Λύση:** Ελέγξτε το αρχείο `routes/api.php` - πρέπει να περιέχει:
```php
// AI Matching Routes
$router->get('/api/matching/driver/matches', 'MatchingController@getDriverMatches');
$router->get('/api/matching/job/candidates', 'MatchingController@getJobCandidates');
$router->get('/api/matching/calculate', 'MatchingController@calculateMatch');
$router->get('/api/matching/insights', 'MatchingController@getMatchInsights');
```

### Πρόβλημα 4: "Σφάλμα κατά τη φόρτωση των προτάσεων"
**Πιθανές αιτίες:**
1. Δεν υπάρχουν active drivers ή jobs στη βάση
2. Ο driver δεν έχει συντεταγμένες (latitude/longitude)
3. Τα jobs δεν έχουν location data

## 📍 Σωστά URLs για Production:

- **Driver Profile**: `http://localhost/drivejob/public/drivers/profile`
- **Job Matches Page**: `http://localhost/drivejob/public/drivers/job-matches`
- **Company Profile**: `http://localhost/drivejob/public/companies/company-profile`

## 🎯 Τι κάνει το AI Matching System:

1. **Για Οδηγούς:**
   - Βρίσκει τις καλύτερες θέσεις εργασίας βάσει:
     - Δεξιοτήτων (35%)
     - Τοποθεσίας (25%)
     - Εμπειρίας (25%)
     - Διαθεσιμότητας (15%)

2. **Για Εταιρείες:**
   - Βρίσκει τους καλύτερους υποψήφιους για κάθε θέση
   - Εμφανίζει match percentage
   - Παρέχει insights για κάθε match

## 💡 Tips:

1. Χρησιμοποιήστε πρώτα το `debug-ai-system.php` για να δείτε αν όλα είναι OK
2. Αν όλα είναι πράσινα, δοκιμάστε το `test-ai-widgets.php`
3. Για production, τα widgets είναι ήδη ενσωματωμένα στα dashboards

## ❓ Συχνές Ερωτήσεις:

**Ε: Γιατί δεν βλέπω προτάσεις;**
Α: Ελέγξτε αν:
- Υπάρχουν active job listings
- Ο driver έχει συμπληρωμένο προφίλ
- Έχουν τρέξει τα migrations

**Ε: Πώς προσθέτω test data?**
Α: Χρησιμοποιήστε το phpMyAdmin ή δημιουργήστε νέες εγγραφές μέσω της εφαρμογής

**Ε: Μπορώ να αλλάξω τα weights του algorithm?**
Α: Ναι, στο αρχείο `src/Services/AI/ScoreCalculator.php`
