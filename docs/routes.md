# Τεκμηρίωση Διαδρομών (Routes) του Συστήματος DriveJob

Αυτό το αρχείο περιέχει μια τεκμηρίωση των διαδρομών (routes) που χρησιμοποιούνται στο σύστημα DriveJob. Οι διαδρομές αυτές ορίζονται στο αρχείο `config/routes.php` και χρησιμοποιούνται για την πλοήγηση στο σύστημα.

## Γενικές Διαδρομές

| Διαδρομή | Μέθοδος | Controller | Action | Περιγραφή |
|----------|---------|------------|--------|-----------|
| `/` | GET | HomeController | renderHomePage | Αρχική σελίδα |
| `/about` | GET | HomeController | about | Σελίδα "Σχετικά με εμάς" |
| `/contact` | GET | HomeController | contact | Σελίδα επικοινωνίας |
| `/contact` | POST | HomeController | submitContactForm | Υποβολή φόρμας επικοινωνίας |
| `/terms` | GET | HomeController | terms | Όροι χρήσης |
| `/privacy` | GET | HomeController | privacy | Πολιτική απορρήτου |
| `/faq` | GET | HomeController | faq | Συχνές ερωτήσεις |

## Διαδρομές Αυθεντικοποίησης

| Διαδρομή | Μέθοδος | Controller | Action | Περιγραφή |
|----------|---------|------------|--------|-----------|
| `/login` | GET | AuthController | showLoginForm | Εμφάνιση φόρμας σύνδεσης |
| `/login` | POST | AuthController | login | Σύνδεση χρήστη |
| `/logout` | GET | AuthController | logout | Αποσύνδεση χρήστη |
| `/verify` | GET | AuthController | verify | Επαλήθευση λογαριασμού |
| `/password-reset` | GET | AuthController | showPasswordResetForm | Εμφάνιση φόρμας επαναφοράς κωδικού |
| `/password-reset` | POST | AuthController | sendPasswordResetLink | Αποστολή συνδέσμου επαναφοράς κωδικού |
| `/password-reset/{token}` | GET | AuthController | showResetPasswordForm | Εμφάνιση φόρμας επαναφοράς κωδικού με token |
| `/password-reset/{token}` | POST | AuthController | resetPassword | Επαναφορά κωδικού |
| `/access-denied` | GET | AuthController | accessDenied | Σελίδα απαγόρευσης πρόσβασης |
| `/verification-required` | GET | AuthController | verificationRequired | Σελίδα απαίτησης επαλήθευσης |

## Διαδρομές Αγγελιών

| Διαδρομή | Μέθοδος | Controller | Action | Περιγραφή |
|----------|---------|------------|--------|-----------|
| `/job-listings` | GET | UnifiedJobListingController | index | Λίστα αγγελιών |
| `/job-listings/show/{id}` | GET | UnifiedJobListingController | show | Προβολή αγγελίας |
| `/job-listings/company/{id}` | GET | UnifiedJobListingController | companyListings | Αγγελίες εταιρείας |
| `/job-listings/driver/{id}` | GET | UnifiedJobListingController | driverListings | Αγγελίες οδηγού |
| `/job-listings/my-listings` | GET | UnifiedJobListingController | myListings | Οι αγγελίες μου |
| `/job-listings/create` | GET | UnifiedJobListingController | create | Δημιουργία αγγελίας |
| `/job-listings/store` | POST | UnifiedJobListingController | store | Αποθήκευση αγγελίας |
| `/job-listings/edit/{id}` | GET | UnifiedJobListingController | edit | Επεξεργασία αγγελίας |
| `/job-listings/update/{id}` | POST | UnifiedJobListingController | update | Ενημέρωση αγγελίας |
| `/job-listings/delete/{id}` | GET | UnifiedJobListingController | delete | Διαγραφή αγγελίας (επιβεβαίωση) |
| `/job-listings/destroy/{id}` | POST | UnifiedJobListingController | destroy | Διαγραφή αγγελίας (οριστική) |

## Διαδρομές Οδηγών

| Διαδρομή | Μέθοδος | Controller | Action | Περιγραφή |
|----------|---------|------------|--------|-----------|
| `/drivers/register` | GET | DriversController | showRegistrationForm | Εμφάνιση φόρμας εγγραφής οδηγού |
| `/drivers/register` | POST | DriversController | register | Εγγραφή οδηγού |
| `/drivers/profile` | GET | DriversController | profile | Προφίλ οδηγού |
| `/drivers/profile/{id}` | GET | DriversController | publicProfile | Δημόσιο προφίλ οδηγού |
| `/drivers/edit-profile` | GET | DriversController | edit | Επεξεργασία προφίλ οδηγού |
| `/drivers/update-profile` | POST | DriversController | update | Ενημέρωση προφίλ οδηγού |
| `/drivers/change-password` | POST | DriversController | changePassword | Αλλαγή κωδικού οδηγού |
| `/drivers/search` | GET | DriversController | search | Αναζήτηση οδηγών |
| `/drivers/top-rated` | GET | DriversController | topRated | Κορυφαίοι οδηγοί |
| `/drivers/recently-available` | GET | DriversController | recentlyAvailable | Πρόσφατα διαθέσιμοι οδηγοί |
| `/drivers/add-rating/{id}` | POST | DriversController | addRating | Προσθήκη αξιολόγησης οδηγού |
| `/drivers/welcome` | GET | DriversController | welcome | Σελίδα καλωσορίσματος οδηγού |
| `/drivers/complete-profile` | POST | DriversController | completeProfile | Ολοκλήρωση προφίλ οδηγού |
| `/drivers/update-assessment` | GET | DriversController | updateAssessment | Ενημέρωση αξιολόγησης οδηγού |
| `/drivers/update-assessment` | POST | DriversController | updateAssessment | Ενημέρωση αξιολόγησης οδηγού |
| `/drivers/save-assessment` | POST | DriversController | saveAssessment | Αποθήκευση αξιολόγησης οδηγού |
| `/drivers/toggle-availability` | POST | DriversController | toggleAvailability | Εναλλαγή διαθεσιμότητας οδηγού |
| `/drivers/driver-rating` | GET | DriversController | driverRating | Αξιολόγηση οδηγού |
| `/drivers/refresh-rating` | GET | DriversController | refreshRating | Ανανέωση αξιολόγησης οδηγού |
| `/drivers/incident-history` | GET | DriversController | incidentHistory | Ιστορικό συμβάντων οδηγού |
| `/drivers/report-incident` | GET | DriversController | reportIncident | Αναφορά συμβάντος οδηγού |
| `/drivers/save-incident` | POST | DriversController | saveIncident | Αποθήκευση συμβάντος οδηγού |
| `/drivers/edit-resume` | GET | DriverResumeController | editResume | Επεξεργασία βιογραφικού οδηγού |
| `/drivers/update-resume` | POST | DriverResumeController | updateResume | Ενημέρωση βιογραφικού οδηγού |

## Διαδρομές Εταιρειών

| Διαδρομή | Μέθοδος | Controller | Action | Περιγραφή |
|----------|---------|------------|--------|-----------|
| `/companies/register` | GET | CompaniesController | showRegistrationForm | Εμφάνιση φόρμας εγγραφής εταιρείας |
| `/companies/register` | POST | CompaniesController | register | Εγγραφή εταιρείας |
| `/companies/profile` | GET | CompaniesController | profile | Προφίλ εταιρείας |
| `/companies/profile/{id}` | GET | CompaniesController | publicProfile | Δημόσιο προφίλ εταιρείας |
| `/companies/edit-profile` | GET | CompaniesController | edit | Επεξεργασία προφίλ εταιρείας |
| `/companies/update-profile` | POST | CompaniesController | update | Ενημέρωση προφίλ εταιρείας |
| `/companies/change-password` | POST | CompaniesController | changePassword | Αλλαγή κωδικού εταιρείας |
| `/companies/search` | GET | CompaniesController | search | Αναζήτηση εταιρειών |
| `/companies/add-review/{id}` | POST | CompaniesController | addReview | Προσθήκη κριτικής εταιρείας |

## Διαδρομές Ταιριασμάτων

| Διαδρομή | Μέθοδος | Controller | Action | Περιγραφή |
|----------|---------|------------|--------|-----------|
| `/matching/driver-matches` | GET | MatchingController | driverMatches | Ταιριάσματα οδηγού |
| `/matching/company-matches` | GET | MatchingController | companyMatches | Ταιριάσματα εταιρείας |
| `/matching/job-listing-matches/{id}` | GET | MatchingController | jobListingMatches | Ταιριάσματα αγγελίας |
| `/matching/preferences` | GET | MatchingController | preferences | Προτιμήσεις ταιριάσματος |
| `/matching/save-preferences` | POST | MatchingController | savePreferences | Αποθήκευση προτιμήσεων ταιριάσματος |
| `/matching/log-action` | POST | MatchingController | logAction | Καταγραφή ενέργειας ταιριάσματος |

## Διαδρομές Αιτήσεων Εργασίας

| Διαδρομή | Μέθοδος | Controller | Action | Περιγραφή |
|----------|---------|------------|--------|-----------|
| `/job-applications/apply/{id}` | POST | JobApplicationController | apply | Υποβολή αίτησης εργασίας |
| `/job-applications/my-applications` | GET | JobApplicationController | myApplications | Οι αιτήσεις μου |
| `/job-applications/view/{id}` | GET | JobApplicationController | view | Προβολή αίτησης |
| `/job-applications/withdraw/{id}` | POST | JobApplicationController | withdraw | Απόσυρση αίτησης |

## Διαδρομές Προσφορών Εργασίας

| Διαδρομή | Μέθοδος | Controller | Action | Περιγραφή |
|----------|---------|------------|--------|-----------|
| `/job-offers/send/{id}` | POST | JobOfferController | send | Αποστολή προσφοράς εργασίας |
| `/job-offers/my-offers` | GET | JobOfferController | myOffers | Οι προσφορές μου |
| `/job-offers/view/{id}` | GET | JobOfferController | view | Προβολή προσφοράς |
| `/job-offers/accept/{id}` | POST | JobOfferController | accept | Αποδοχή προσφοράς |
| `/job-offers/reject/{id}` | POST | JobOfferController | reject | Απόρριψη προσφοράς |

## Σημειώσεις

1. Οι διαδρομές για τη δημιουργία, επεξεργασία και διαγραφή αγγελιών έχουν ενοποιηθεί και χρησιμοποιούν τον `UnifiedJobListingController`.
2. Οι διαδρομές `/job-listings/create`, `/job-listings/edit/{id}` και `/job-listings/delete/{id}` χρησιμοποιούνται τόσο για τους οδηγούς όσο και για τις εταιρείες.
3. Ο `UnifiedJobListingController` προσαρμόζει τη συμπεριφορά του ανάλογα με τον ρόλο του χρήστη (οδηγός ή εταιρεία).
