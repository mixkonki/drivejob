# Παραδείγματα Χρήσης Διαδρομών

Αυτό το αρχείο περιέχει παραδείγματα χρήσης των διαδρομών του συστήματος DriveJob, με έμφαση στις νέες λειτουργίες του συστήματος δρομολόγησης.

## Χρήση Ονομαστικών Διαδρομών

Οι ονομαστικές διαδρομές επιτρέπουν την αναφορά σε διαδρομές με ονόματα αντί για URLs, κάνοντας τον κώδικα πιο ευανάγνωστο και ευκολότερο στη συντήρηση. Για να χρησιμοποιήσετε μια ονομαστική διαδρομή, χρησιμοποιήστε τη μέθοδο `url()` του router:

```php
// Παλιός τρόπος
<a href="<?php echo BASE_URL; ?>job-listings/create">Νέα Αγγελία</a>

// Νέος τρόπος με ονομαστικές διαδρομές
<a href="<?php echo $router->url('job-listings.create'); ?>">Νέα Αγγελία</a>
```

Για διαδρομές με παραμέτρους, περάστε τις παραμέτρους ως δεύτερο όρισμα:

```php
// Παλιός τρόπος
<a href="<?php echo BASE_URL; ?>job-listings/edit/<?php echo $listing['id']; ?>">Επεξεργασία</a>

// Νέος τρόπος με ονομαστικές διαδρομές
<a href="<?php echo $router->url('job-listings.edit', ['id' => $listing['id']]); ?>">Επεξεργασία</a>
```

## Χρήση Ομαδοποιημένων Διαδρομών

Οι ομαδοποιημένες διαδρομές επιτρέπουν τον ορισμό κοινών χαρακτηριστικών για ομάδες διαδρομών, όπως κοινά προθέματα και middlewares. Για να ορίσετε μια ομάδα διαδρομών, χρησιμοποιήστε τη μέθοδο `group()` του router:

```php
// Ομαδοποίηση διαδρομών με κοινό πρόθεμα
$router->group(['prefix' => 'job-listings'], function ($router) {
    $router->get('/', [UnifiedJobListingController::class, 'index'])->name('job-listings.index');
    $router->get('/show/{id}', [UnifiedJobListingController::class, 'show'])->name('job-listings.show');
    $router->get('/create', [UnifiedJobListingController::class, 'create'])->name('job-listings.create');
    $router->post('/store', [UnifiedJobListingController::class, 'store'])->name('job-listings.store');
});
```

Μπορείτε επίσης να ορίσετε κοινά middlewares για μια ομάδα διαδρομών:

```php
// Ομαδοποίηση διαδρομών με κοινό middleware
$router->group(['middleware' => \Drivejob\Core\AuthMiddleware::class], function ($router) {
    $router->get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    $router->get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    $router->post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
});
```

## Χρήση Middlewares

Τα middlewares επιτρέπουν την εκτέλεση κώδικα πριν από την εκτέλεση του controller. Για να ορίσετε ένα middleware για μια διαδρομή, περάστε το ως τρίτο όρισμα στη μέθοδο της διαδρομής:

```php
// Ορισμός middleware για μια διαδρομή
$router->get('/profile', [ProfileController::class, 'show'], [
    function () {
        \Drivejob\Core\AuthMiddleware::hasRole('driver');
        return null;
    }
])->name('profile.show');
```

Μπορείτε επίσης να ορίσετε πολλαπλά middlewares για μια διαδρομή:

```php
// Ορισμός πολλαπλών middlewares για μια διαδρομή
$router->get('/profile', [ProfileController::class, 'show'], [
    function () {
        \Drivejob\Core\AuthMiddleware::hasRole('driver');
        return null;
    },
    function () {
        \Drivejob\Core\AuthMiddleware::isVerified();
        return null;
    }
])->name('profile.show');
```

## Χρήση Διαδρομών με Πολλαπλές Μεθόδους HTTP

Για να ορίσετε μια διαδρομή που ταιριάζει με περισσότερες από μία μεθόδους HTTP, μπορείτε να ορίσετε ξεχωριστές διαδρομές για κάθε μέθοδο:

```php
// Ορισμός διαδρομής για GET και POST
$router->get('/profile', [ProfileController::class, 'show'])->name('profile.show');
$router->post('/profile', [ProfileController::class, 'update'])->name('profile.update');
```

## Χρήση Διαδρομών με Προαιρετικές Παραμέτρους

Για να ορίσετε μια διαδρομή με προαιρετικές παραμέτρους, μπορείτε να ορίσετε δύο ξεχωριστές διαδρομές:

```php
// Ορισμός διαδρομής με και χωρίς παράμετρο
$router->get('/users', [UsersController::class, 'index'])->name('users.index');
$router->get('/users/{id}', [UsersController::class, 'show'])->name('users.show');
```

## Χρήση Διαδρομών με Περιορισμούς Παραμέτρων

Για να ορίσετε μια διαδρομή με περιορισμούς παραμέτρων, μπορείτε να χρησιμοποιήσετε κανονικές εκφράσεις στη μέθοδο `convertRouteToRegex()` του router:

```php
// Ορισμός διαδρομής με περιορισμό παραμέτρου
$router->get('/users/{id}', [UsersController::class, 'show'])->name('users.show');
```

Και στη μέθοδο `convertRouteToRegex()`:

```php
private function convertRouteToRegex($route, $caseInsensitive = true)
{
    // Αντικατάσταση παραμέτρων της μορφής {id} με ομάδες regex
    $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route);
    // Προσθήκη ^ και $ για ακριβές ταίριασμα και προετοιμασία για preg_match
    $flags = $caseInsensitive ? 'i' : '';
    return "#^{$pattern}$" . ($flags ? "#{$flags}" : "#");
}
```

## Χρήση Διαδρομών με Προεπιλεγμένες Τιμές Παραμέτρων

Για να ορίσετε μια διαδρομή με προεπιλεγμένες τιμές παραμέτρων, μπορείτε να ελέγξετε αν η παράμετρος υπάρχει στον controller:

```php
// Ορισμός διαδρομής με προεπιλεγμένη τιμή παραμέτρου
$router->get('/users/{page}', [UsersController::class, 'index'])->name('users.index');
```

Και στον controller:

```php
public function index($page = 1)
{
    // Χρήση της παραμέτρου $page
}
```

## Συμπέρασμα

Οι νέες λειτουργίες του συστήματος δρομολόγησης του DriveJob κάνουν τον κώδικα πιο ευανάγνωστο, πιο ευέλικτο και πιο εύκολο στη συντήρηση. Χρησιμοποιώντας ονομαστικές διαδρομές, ομαδοποιημένες διαδρομές και middlewares, μπορείτε να οργανώσετε καλύτερα τις διαδρομές του συστήματος και να κάνετε τον κώδικα πιο ευέλικτο και επαναχρησιμοποιήσιμο.
