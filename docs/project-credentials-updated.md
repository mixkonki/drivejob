# DriveJob - Διαπιστευτήρια και Σημαντικές Πληροφορίες (Ενημερωμένο)

## Διαπιστευτήρια Σύνδεσης

### Εταιρείες
1. **ThessDrive IKE**
   - Email: info@thessdrive.gr
   - Password: password123
   - Company ID: 2

2. **Test Company**
   - Email: test-company@example.com
   - Password: password123
   - Company ID: 3

### Οδηγοί
1. **Γιώργος Παπαδόπουλος**
   - Email: george.papadopoulos@email.com
   - Password: password123
   - Driver ID: 1

## Σημαντικά Job IDs
- Job ID 15: Ανήκει στην ThessDrive IKE (Company ID: 2)

## API Endpoints
- AI Matching Candidates: `/api/matching/job/candidates/index.php`
- Parameters: `?job_id={id}&limit={number}`

## Γνωστά Προβλήματα και Λύσεις
1. **Session Management**: Το bootstrap.php ξεκινάει το session αυτόματα
2. **AJAX Requests**: Χρειάζεται `credentials: 'same-origin'` για να στέλνονται τα cookies
3. **API Headers**: Χρειάζεται `Access-Control-Allow-Credentials: true`
4. **User Role**: Η στήλη user_role μπορεί να μην υπάρχει στον πίνακα users

## Δομή Βάσης Δεδομένων
- Πίνακας `companies`: id, company_name, email, is_active
- Πίνακας `users`: id, email, password, is_active (προσοχή: user_role μπορεί να μην υπάρχει)
- Πίνακας `drivers`: id, first_name, last_name, email, available_for_work
- Πίνακας `job_listings`: id, company_id, title, is_active
- Πίνακας `matching_scores`: job_id, driver_id, overall_score

## Τρέχουσα Κατάσταση AI Widget
- Αρχείο: `src/Views/companies/partials/candidates-widget-final.php`
- Χρησιμοποιείται στο: `src/Views/companies/company-profile.php`
- API Endpoint: `/api/matching/job/candidates/index.php`
- Κατάσταση: Λειτουργικό με σωστά credentials

## URLs για Testing
- Login: http://localhost/drivejob/public/login.php
- Company Profile: http://localhost/drivejob/public/companies/profile
- API Test: http://localhost/drivejob/public/api/matching/job/candidates/index.php?job_id=15&limit=5

## Σημειώσεις
- Τα passwords στη βάση είναι hashed, οπότε το "password123" πρέπει να έχει εισαχθεί σωστά κατά τη δημιουργία του χρήστη
- Το login σύστημα ελέγχει τον πίνακα companies για company logins
