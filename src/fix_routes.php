<?php

/**
 * Script για τη διόρθωση των διαδρομών (routes) και των controllers
 * 
 * Αυτό το script διορθώνει τα προβλήματα με τις διαδρομές και τους controllers
 * που προκαλούν προβλήματα στην εμφάνιση των προφίλ των οδηγών και των εταιρειών
 */

// Φόρτωση του bootstrap
require_once __DIR__ . '/bootstrap.php';

use Drivejob\Core\Logger;

// Λήψη του PDO από το container
$container = \Drivejob\Core\Container::getInstance();
$pdo = $container->get('pdo');

// Έλεγχος αν υπάρχει ο controller συμβατότητας για τους οδηγούς
$driversCompatibilityControllerPath = __DIR__ . '/Controllers/DriversController.php';
if (!file_exists($driversCompatibilityControllerPath)) {
    // Δημιουργία του controller συμβατότητας για τους οδηγούς
    $driversCompatibilityController = <<<'EOT'
<?php

namespace Drivejob\Controllers;

/**
 * Controller συμβατότητας για τους οδηγούς
 * 
 * Αυτός ο controller χρησιμοποιείται για συμβατότητα με τον παλιό κώδικα
 * και απλώς προωθεί τις κλήσεις στον νέο controller
 */
class DriversController
{
    /**
     * @var \Drivejob\Controllers\Driver\DriversController Ο νέος controller
     */
    private $newController;

    /**
     * Constructor
     *
     * @param PDO|null $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct($pdo = null)
    {
        $this->newController = new \Drivejob\Controllers\Driver\DriversController($pdo);
    }

    /**
     * Προωθεί όλες τις κλήσεις μεθόδων στον νέο controller
     *
     * @param string $name Το όνομα της μεθόδου
     * @param array $arguments Τα ορίσματα της μεθόδου
     * @return mixed Το αποτέλεσμα της κλήσης
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->newController, $name], $arguments);
    }

    /**
     * Προβάλλει τη σελίδα προφίλ του οδηγού
     */
    public function profile()
    {
        return $this->newController->profile();
    }

    /**
     * Προβάλλει τη φόρμα επεξεργασίας προφίλ
     */
    public function edit()
    {
        return $this->newController->edit();
    }

    /**
     * Αποθηκεύει τις αλλαγές στο προφίλ
     */
    public function update()
    {
        return $this->newController->update();
    }

    /**
     * Εμφανίζει το δημόσιο προφίλ ενός οδηγού
     * 
     * @param int $id Το ID του οδηγού
     */
    public function publicProfile($id)
    {
        return $this->newController->publicProfile($id);
    }

    /**
     * Αναζητά οδηγούς με βάση διάφορα κριτήρια
     */
    public function search()
    {
        return $this->newController->search();
    }
}
EOT;

    // Αποθήκευση του controller συμβατότητας για τους οδηγούς
    file_put_contents($driversCompatibilityControllerPath, $driversCompatibilityController);
    echo "Ο controller συμβατότητας για τους οδηγούς δημιουργήθηκε με επιτυχία.\n";
} else {
    echo "Ο controller συμβατότητας για τους οδηγούς υπάρχει ήδη.\n";
}

// Έλεγχος αν υπάρχει ο controller συμβατότητας για τις εταιρείες
$companiesCompatibilityControllerPath = __DIR__ . '/Controllers/CompaniesController.php';
if (!file_exists($companiesCompatibilityControllerPath)) {
    // Δημιουργία του controller συμβατότητας για τις εταιρείες
    $companiesCompatibilityController = <<<'EOT'
<?php

namespace Drivejob\Controllers;

/**
 * Controller συμβατότητας για τις εταιρείες
 * 
 * Αυτός ο controller χρησιμοποιείται για συμβατότητα με τον παλιό κώδικα
 * και απλώς προωθεί τις κλήσεις στον νέο controller
 */
class CompaniesController
{
    /**
     * @var \Drivejob\Controllers\Company\CompaniesController Ο νέος controller
     */
    private $newController;

    /**
     * Constructor
     *
     * @param PDO|null $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct($pdo = null)
    {
        $this->newController = new \Drivejob\Controllers\Company\CompaniesController($pdo);
    }

    /**
     * Προωθεί όλες τις κλήσεις μεθόδων στον νέο controller
     *
     * @param string $name Το όνομα της μεθόδου
     * @param array $arguments Τα ορίσματα της μεθόδου
     * @return mixed Το αποτέλεσμα της κλήσης
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->newController, $name], $arguments);
    }

    /**
     * Προβάλλει τη σελίδα προφίλ της εταιρείας
     */
    public function profile()
    {
        return $this->newController->profile();
    }

    /**
     * Προβάλλει τη φόρμα επεξεργασίας προφίλ
     */
    public function edit()
    {
        return $this->newController->edit();
    }

    /**
     * Αποθηκεύει τις αλλαγές στο προφίλ
     */
    public function update()
    {
        return $this->newController->update();
    }

    /**
     * Εμφανίζει το δημόσιο προφίλ μιας εταιρείας
     * 
     * @param int $id Το ID της εταιρείας
     */
    public function publicProfile($id)
    {
        return $this->newController->publicProfile($id);
    }

    /**
     * Αναζητά εταιρείες με βάση διάφορα κριτήρια
     */
    public function search()
    {
        return $this->newController->search();
    }
}
EOT;

    // Αποθήκευση του controller συμβατότητας για τις εταιρείες
    file_put_contents($companiesCompatibilityControllerPath, $companiesCompatibilityController);
    echo "Ο controller συμβατότητας για τις εταιρείες δημιουργήθηκε με επιτυχία.\n";
} else {
    echo "Ο controller συμβατότητας για τις εταιρείες υπάρχει ήδη.\n";
}

// Έλεγχος αν υπάρχει ο controller για τις εταιρείες
$companiesControllerPath = __DIR__ . '/Controllers/Company/CompaniesController.php';
if (!file_exists($companiesControllerPath)) {
    echo "ΠΡΟΣΟΧΗ: Ο controller για τις εταιρείες δεν υπάρχει. Πρέπει να δημιουργηθεί.\n";
}

// Προσθήκη της διαδρομής /my-listings στο αρχείο routes.php
$routesPath = __DIR__ . '/../config/routes.php';
$routesContent = file_get_contents($routesPath);

// Έλεγχος αν υπάρχει ήδη η διαδρομή /my-listings
if (strpos($routesContent, "'my-listings'") === false) {
    // Προσθήκη της διαδρομής /my-listings
    $routesContent = str_replace(
        "// Διαδρομές για τις αγγελίες",
        "// Διαδρομές για τις αγγελίες\n    'my-listings' => ['controller' => 'UnifiedJobListingController', 'action' => 'myListings'],",
        $routesContent
    );

    // Αποθήκευση του αρχείου routes.php
    file_put_contents($routesPath, $routesContent);
    echo "Η διαδρομή /my-listings προστέθηκε με επιτυχία.\n";
} else {
    echo "Η διαδρομή /my-listings υπάρχει ήδη.\n";
}

// Έλεγχος αν υπάρχει ο controller UnifiedJobListingController
$unifiedJobListingControllerPath = __DIR__ . '/Controllers/UnifiedJobListingController.php';
if (!file_exists($unifiedJobListingControllerPath)) {
    echo "ΠΡΟΣΟΧΗ: Ο controller UnifiedJobListingController δεν υπάρχει. Πρέπει να δημιουργηθεί.\n";
} else {
    // Έλεγχος αν υπάρχει η μέθοδος myListings
    $unifiedJobListingControllerContent = file_get_contents($unifiedJobListingControllerPath);
    if (strpos($unifiedJobListingControllerContent, "function myListings") === false) {
        // Προσθήκη της μεθόδου myListings
        $unifiedJobListingControllerContent = str_replace(
            "}\n?>",
            "    /**\n     * Εμφανίζει τις αγγελίες του χρήστη\n     */\n    public function myListings()\n    {\n        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος\n        if (!isset(\$_SESSION['user_id']) || !isset(\$_SESSION['role'])) {\n            header('Location: ' . BASE_URL . 'login.php');\n            exit();\n        }\n\n        // Λήψη του ID και του ρόλου του χρήστη\n        \$userId = \$_SESSION['user_id'];\n        \$userRole = \$_SESSION['role'];\n\n        // Λήψη των αγγελιών του χρήστη\n        \$jobListingRepository = new \\Drivejob\\Repositories\\JobListingRepository(\$this->pdo);\n        \$listings = [];\n\n        if (\$userRole === 'driver') {\n            \$listings = \$jobListingRepository->searchListings(['driver_id' => \$userId], 1, 10);\n            include ROOT_DIR . '/src/Views/job-listings/Driver/my-listings.php';\n        } else if (\$userRole === 'company') {\n            \$listings = \$jobListingRepository->searchListings(['company_id' => \$userId], 1, 10);\n            include ROOT_DIR . '/src/Views/job-listings/Company/my-listings.php';\n        } else {\n            header('Location: ' . BASE_URL);\n            exit();\n        }\n    }\n}\n?>",
            $unifiedJobListingControllerContent
        );

        // Αποθήκευση του controller UnifiedJobListingController
        file_put_contents($unifiedJobListingControllerPath, $unifiedJobListingControllerContent);
        echo "Η μέθοδος myListings προστέθηκε με επιτυχία στον controller UnifiedJobListingController.\n";
    } else {
        echo "Η μέθοδος myListings υπάρχει ήδη στον controller UnifiedJobListingController.\n";
    }
}

// Δημιουργία των views για τις αγγελίες του χρήστη
$driverMyListingsViewPath = __DIR__ . '/Views/job-listings/Driver/my-listings.php';
if (!file_exists($driverMyListingsViewPath)) {
    // Δημιουργία του φακέλου αν δεν υπάρχει
    if (!is_dir(dirname($driverMyListingsViewPath))) {
        mkdir(dirname($driverMyListingsViewPath), 0755, true);
    }

    // Δημιουργία του view για τις αγγελίες του οδηγού
    $driverMyListingsView = <<<'EOT'
<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/job-listings.css">

<main>
    <div class="container">
        <h1>Οι Αγγελίες μου</h1>

        <?php if (isset($_SESSION['success_message'])) : ?>
            <div class="success-message">
                <?php echo $_SESSION['success_message']; ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])) : ?>
            <div class="error-message">
                <?php echo $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <div class="listings-actions">
            <a href="<?php echo BASE_URL; ?>job-listings/Driver/create" class="btn-primary">Νέα Αγγελία</a>
        </div>

        <?php if (isset($listings) && count($listings['results']) > 0) : ?>
            <div class="listings-container">
                <?php foreach ($listings['results'] as $listing) : ?>
                    <div class="listing-card">
                        <div class="listing-header">
                            <h2><a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h2>
                            <span class="listing-type <?php echo $listing['listing_type']; ?>">
                                <?php echo $listing['listing_type'] === 'job_offer' ? 'Προσφορά Εργασίας' : 'Αναζήτηση Εργασίας'; ?>
                            </span>
                        </div>

                        <div class="listing-details">
                            <div class="listing-meta">
                                <div class="meta-item">
                                    <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                                    <span><?php echo htmlspecialchars($listing['location']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <img src="<?php echo BASE_URL; ?>img/date_icon.png" alt="Ημερομηνία">
                                    <span>Δημοσιεύτηκε: <?php echo date('d/m/Y', strtotime($listing['created_at'])); ?></span>
                                </div>
                                <div class="meta-item">
                                    <img src="<?php echo BASE_URL; ?>img/car_icon.png" alt="Τύπος Οχήματος">
                                    <span>
                                        <?php
                                        $vehicleTypes = [
                                            'car' => 'Αυτοκίνητο',
                                            'van' => 'Βαν',
                                            'truck' => 'Φορτηγό',
                                            'bus' => 'Λεωφορείο',
                                            'taxi' => 'Ταξί',
                                            'motorcycle' => 'Μοτοσυκλέτα',
                                            'special' => 'Ειδικό Όχημα'
                                        ];
                                        echo isset($vehicleTypes[$listing['vehicle_type']]) ? $vehicleTypes[$listing['vehicle_type']] : $listing['vehicle_type'];
                                        ?>
                                    </span>
                                </div>
                            </div>

                            <div class="listing-description">
                                <?php echo substr(htmlspecialchars($listing['description']), 0, 200) . '...'; ?>
                            </div>

                            <div class="listing-status">
                                <span class="status-label">Κατάσταση:</span>
                                <span class="status-value <?php echo $listing['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $listing['is_active'] ? 'Ενεργή' : 'Ανενεργή'; ?>
                                </span>
                            </div>

                            <div class="listing-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Προβολές:</span>
                                    <span class="stat-value"><?php echo $listing['views']; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Αιτήσεις:</span>
                                    <span class="stat-value"><?php echo $listing['applications']; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="listing-actions">
                            <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>" class="btn-secondary">Προβολή</a>
                            <a href="<?php echo BASE_URL; ?>job-listings/edit-driver/<?php echo $listing['id']; ?>" class="btn-secondary">Επεξεργασία</a>
                            <form action="<?php echo BASE_URL; ?>job-listings/delete-driver/<?php echo $listing['id']; ?>" method="post" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo \Drivejob\Core\CSRF::generateToken(); ?>">
                                <button type="submit" class="btn-danger" onclick="return confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή την αγγελία;')">Διαγραφή</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($listings['total_pages'] > 1) : ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $listings['total_pages']; $i++) : ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo $i === $listings['current_page'] ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <div class="no-listings">
                <p>Δεν έχετε δημιουργήσει ακόμα αγγελίες.</p>
                <a href="<?php echo BASE_URL; ?>job-listings/Driver/create" class="btn-primary">Δημιουργία Αγγελίας</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>
EOT;

    // Αποθήκευση του view για τις αγγελίες του οδηγού
    file_put_contents($driverMyListingsViewPath, $driverMyListingsView);
    echo "Το view για τις αγγελίες του οδηγού δημιουργήθηκε με επιτυχία.\n";
} else {
    echo "Το view για τις αγγελίες του οδηγού υπάρχει ήδη.\n";
}

$companyMyListingsViewPath = __DIR__ . '/Views/job-listings/Company/my-listings.php';
if (!file_exists($companyMyListingsViewPath)) {
    // Δημιουργία του φακέλου αν δεν υπάρχει
    if (!is_dir(dirname($companyMyListingsViewPath))) {
        mkdir(dirname($companyMyListingsViewPath), 0755, true);
    }

    // Δημιουργία του view για τις αγγελίες της εταιρείας
    $companyMyListingsView = <<<'EOT'
<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/job-listings.css">

<main>
    <div class="container">
        <h1>Οι Αγγελίες μας</h1>

        <?php if (isset($_SESSION['success_message'])) : ?>
            <div class="success-message">
                <?php echo $_SESSION['success_message']; ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])) : ?>
            <div class="error-message">
                <?php echo $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <div class="listings-actions">
            <a href="<?php echo BASE_URL; ?>job-listings/Company/create" class="btn-primary">Νέα Αγγελία</a>
        </div>

        <?php if (isset($listings) && count($listings['results']) > 0) : ?>
            <div class="listings-container">
                <?php foreach ($listings['results'] as $listing) : ?>
                    <div class="listing-card">
                        <div class="listing-header">
                            <h2><a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h2>
                            <span class="listing-type <?php echo $listing['listing_type']; ?>">
                                <?php echo $listing['listing_type'] === 'job_offer' ? 'Προσφορά Εργασίας' : 'Αναζήτηση Εργασίας'; ?>
                            </span>
                        </div>

                        <div class="listing-details">
                            <div class="listing-meta">
                                <div class="meta-item">
                                    <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                                    <span><?php echo htmlspecialchars($listing['location']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <img src="<?php echo BASE_URL; ?>img/date_icon.png" alt="Ημερομηνία">
                                    <span>Δημοσιεύτηκε: <?php echo date('d/m/Y', strtotime($listing['created_at'])); ?></span>
                                </div>
                                <div class="meta-item">
                                    <img src="<?php echo BASE_URL; ?>img/car_icon.png" alt="Τύπος Οχήματος">
                                    <span>
                                        <?php
                                        $vehicleTypes = [
                                            'car' => 'Αυτοκίνητο',
                                            'van' => 'Βαν',
                                            'truck' => 'Φορτηγό',
                                            'bus' => 'Λεωφορείο',
                                            'taxi' => 'Ταξί',
                                            'motorcycle' => 'Μοτοσυκλέτα',
                                            'special' => 'Ειδικό Όχημα'
                                        ];
                                        echo isset($vehicleTypes[$listing['vehicle_type']]) ? $vehicleTypes[$listing['vehicle_type']] : $listing['vehicle_type'];
                                        ?>
                                    </span>
                                </div>
                            </div>

                            <div class="listing-description">
                                <?php echo substr(htmlspecialchars($listing['description']), 0, 200) . '...'; ?>
                            </div>

                            <div class="listing-status">
                                <span class="status-label">Κατάσταση:</span>
                                <span class="status-value <?php echo $listing['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $listing['is_active'] ? 'Ενεργή' : 'Ανενεργή'; ?>
                                </span>
                            </div>

                            <div class="listing-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Προβολές:</span>
                                    <span class="stat-value"><?php echo $listing['views']; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Αιτήσεις:</span>
                                    <span class="stat-value"><?php echo $listing['applications']; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="listing-actions">
                            <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>" class="btn-secondary">Προβολή</a>
                            <a href="<?php echo BASE_URL; ?>job-listings/edit-company/<?php echo $listing['id']; ?>" class="btn-secondary">Επεξεργασία</a>
                            <form action="<?php echo BASE_URL; ?>job-listings/delete-company/<?php echo $listing['id']; ?>" method="post" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo \Drivejob\Core\CSRF::generateToken(); ?>">
                                <button type="submit" class="btn-danger" onclick="return confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή την αγγελία;')">Διαγραφή</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($listings['total_pages'] > 1) : ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $listings['total_pages']; $i++) : ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo $i === $listings['current_page'] ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <div class="no-listings">
                <p>Δεν έχετε δημιουργήσει ακόμα αγγελίες.</p>
                <a href="<?php echo BASE_URL; ?>job-listings/Company/create" class="btn-primary">Δημιουργία Αγγελίας</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>
EOT;

    // Αποθήκευση του view για τις αγγελίες της εταιρείας
    file_put_contents($companyMyListingsViewPath, $companyMyListingsView);
    echo "Το view για τις αγγελίες της εταιρείας δημιουργήθηκε με επιτυχία.\n";
} else {
    echo "Το view για τις αγγελίες της εταιρείας υπάρχει ήδη.\n";
}

echo "Η διόρθωση των διαδρομών και των controllers ολοκληρώθηκε με επιτυχία.\n";
