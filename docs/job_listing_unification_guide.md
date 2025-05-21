# Οδηγός Ενοποίησης Controllers Αγγελιών

## Εισαγωγή

Αυτός ο οδηγός περιγράφει τα βήματα για την ενοποίηση των controllers αγγελιών (`JobListingController`) που χρησιμοποιούνται από τους οδηγούς και τις εταιρείες. Η ενοποίηση θα απλοποιήσει τον κώδικα, θα μειώσει την επανάληψη και θα διευκολύνει τη συντήρηση του συστήματος.

## Υπάρχουσα Κατάσταση

Αυτή τη στιγμή, το σύστημα χρησιμοποιεί τρεις διαφορετικούς controllers για τις αγγελίες:

1. `Drivejob\Controllers\Company\JobListingController`: Διαχειρίζεται τις αγγελίες των εταιρειών
2. `Drivejob\Controllers\Driver\JobListingController`: Διαχειρίζεται τις αγγελίες των οδηγών
3. `Drivejob\Controllers\UnifiedJobListingController`: Ένας ενοποιημένος controller που δεν χρησιμοποιείται πλήρως

## Προτεινόμενες Αλλαγές

### 1. Ενημέρωση του UnifiedJobListingController

Ο `UnifiedJobListingController` περιέχει ήδη τις περισσότερες από τις μεθόδους που χρειάζονται, αλλά μπορεί να χρειάζεται κάποιες προσθήκες ή τροποποιήσεις για να καλύψει όλη τη λειτουργικότητα των ξεχωριστών controllers. Συγκεκριμένα:

- Βεβαιωθείτε ότι η μέθοδος `create()` διαχειρίζεται σωστά τόσο τους οδηγούς όσο και τις εταιρείες
- Βεβαιωθείτε ότι η μέθοδος `store()` διαχειρίζεται σωστά τόσο τις αγγελίες των οδηγών όσο και των εταιρειών
- Βεβαιωθείτε ότι οι μέθοδοι `edit()`, `update()`, `delete()` και `destroy()` διαχειρίζονται σωστά τόσο τις αγγελίες των οδηγών όσο και των εταιρειών

### 2. Ενημέρωση των Διαδρομών (Routes)

Έχει δημιουργηθεί ένα νέο αρχείο `src/unified_routes.php` που περιέχει τις ενοποιημένες διαδρομές για τις αγγελίες. Για να ενσωματώσετε αυτές τις διαδρομές στο υπάρχον σύστημα, ακολουθήστε τα παρακάτω βήματα:

1. Ανοίξτε το αρχείο `config/routes.php`
2. Αφαιρέστε τις παρακάτω γραμμές:

```php
use Drivejob\Controllers\Company\JobListingController as CompanyJobListingController;
use Drivejob\Controllers\Driver\JobListingController as DriverJobListingController;
```

3. Προσθέστε την παρακάτω γραμμή:

```php
use Drivejob\Controllers\UnifiedJobListingController;
```

4. Αφαιρέστε τις παρακάτω διαδρομές:

```php
// Διαδρομές για τις αγγελίες
$router->get('/job-listings', [JobListingController::class, 'index']);
$router->get('/job-listings/show/{id}', [JobListingController::class, 'show']);
$router->get('/job-listings/company/{id}', [JobListingController::class, 'companyListings']);
$router->get('/job-listings/driver/{id}', [JobListingController::class, 'driverListings']);
$router->get('/job-listings/my-listings', [JobListingController::class, 'myListings']);

// Διαδρομές για τις αγγελίες εταιρειών
$router->get('/job-listings/Company/create', [CompanyJobListingController::class, 'create']);
$router->post('/job-listings/Company/store', [CompanyJobListingController::class, 'store']);
$router->get('/job-listings/edit/{id}', [CompanyJobListingController::class, 'edit']);
$router->post('/job-listings/update/{id}', [CompanyJobListingController::class, 'update']);
$router->get('/job-listings/delete/{id}', [CompanyJobListingController::class, 'delete']);
$router->post('/job-listings/destroy/{id}', [CompanyJobListingController::class, 'destroy']);

// Διαδρομές για τις αγγελίες οδηγών
$router->get('/job-listings/Driver/create', [DriverJobListingController::class, 'create']);
$router->post('/job-listings/Driver/store', [DriverJobListingController::class, 'store']);
$router->get('/job-listings/Driver/edit/{id}', [DriverJobListingController::class, 'edit']);
$router->post('/job-listings/Driver/update/{id}', [DriverJobListingController::class, 'update']);
$router->post('/job-listings/Driver/delete/{id}', [DriverJobListingController::class, 'delete']);
```

5. Προσθέστε τις παρακάτω διαδρομές:

```php
// Διαδρομές για τις αγγελίες (χρησιμοποιώντας τον ενοποιημένο controller)
$router->get('/job-listings', [UnifiedJobListingController::class, 'index']);
$router->get('/job-listings/show/{id}', [UnifiedJobListingController::class, 'show']);
$router->get('/job-listings/company/{id}', [UnifiedJobListingController::class, 'companyListings']);
$router->get('/job-listings/driver/{id}', [UnifiedJobListingController::class, 'driverListings']);
$router->get('/job-listings/my-listings', [UnifiedJobListingController::class, 'myListings']);

// Διαδρομές για τη δημιουργία αγγελιών (ενοποιημένες)
$router->get('/job-listings/create', [UnifiedJobListingController::class, 'create']);
$router->post('/job-listings/store', [UnifiedJobListingController::class, 'store']);

// Διαδρομές για την επεξεργασία αγγελιών (ενοποιημένες)
$router->get('/job-listings/edit/{id}', [UnifiedJobListingController::class, 'edit']);
$router->post('/job-listings/update/{id}', [UnifiedJobListingController::class, 'update']);

// Διαδρομές για τη διαγραφή αγγελιών (ενοποιημένες)
$router->get('/job-listings/delete/{id}', [UnifiedJobListingController::class, 'delete']);
$router->post('/job-listings/destroy/{id}', [UnifiedJobListingController::class, 'destroy']);
```

### 3. Ενημέρωση των Views

Τα views για τις αγγελίες θα πρέπει να ενημερωθούν για να χρησιμοποιούν τις νέες διαδρομές. Συγκεκριμένα:

- Ενημερώστε τα views `src/Views/job-listings/Company/create.php` και `src/Views/job-listings/Driver/create.php` για να χρησιμοποιούν τη διαδρομή `/job-listings/store` αντί για `/job-listings/Company/store` και `/job-listings/Driver/store`
- Ενημερώστε τα views `src/Views/job-listings/edit.php` και `src/Views/job-listings/edit-driver.php` για να χρησιμοποιούν τη διαδρομή `/job-listings/update/{id}` αντί για `/job-listings/update/{id}` και `/job-listings/Driver/update/{id}`
- Ενημερώστε τα views `src/Views/job-listings/delete.php` για να χρησιμοποιούν τη διαδρομή `/job-listings/destroy/{id}` αντί για `/job-listings/destroy/{id}` και `/job-listings/Driver/delete/{id}`

### 4. Δοκιμή

Μετά την ενημέρωση των controllers, των διαδρομών και των views, θα πρέπει να δοκιμάσετε τη λειτουργικότητα του συστήματος για να βεβαιωθείτε ότι όλα λειτουργούν σωστά. Συγκεκριμένα:

- Δοκιμάστε τη δημιουργία αγγελιών από οδηγούς και εταιρείες
- Δοκιμάστε την επεξεργασία αγγελιών από οδηγούς και εταιρείες
- Δοκιμάστε τη διαγραφή αγγελιών από οδηγούς και εταιρείες
- Δοκιμάστε την προβολή αγγελιών από οδηγούς και εταιρείες

## Πλεονεκτήματα της Ενοποίησης

- **Απλοποίηση του κώδικα**: Ένας controller αντί για τρεις
- **Μείωση της επανάληψης**: Κοινός κώδικας για τις αγγελίες των οδηγών και των εταιρειών
- **Ευκολότερη συντήρηση**: Οι αλλαγές γίνονται σε ένα μόνο σημείο
- **Καλύτερη οργάνωση**: Ενιαία διαχείριση των αγγελιών
- **Ευκολότερη επέκταση**: Προσθήκη νέων λειτουργιών σε ένα μόνο σημείο

## Επόμενα Βήματα

Μετά την ενοποίηση των controllers αγγελιών, μπορείτε να προχωρήσετε στα επόμενα βήματα βελτίωσης του συστήματος:

1. **Βελτίωση του αλγορίθμου ταιριάσματος αγγελιών**: Προσθήκη περισσότερων κριτηρίων όπως εμπειρία, δεξιότητες, προτιμώμενο ωράριο, κλπ.
2. **Εφαρμογή του Service Layer Pattern**: Δημιουργία `JobListingService` για τη διαχείριση των αγγελιών
