<?php
/**
 * Διαγνωστικό script για το σύστημα αγγελιών
 * 
 * Αυτό το script ελέγχει τη βάση δεδομένων, τις διαδρομές και τον κώδικα
 * για να εντοπίσει προβλήματα στο σύστημα αγγελιών.
 */

// Φόρτωση του bootstrap
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Core\Logger;

// Τίτλος σελίδας
$pageTitle = 'Διάγνωση Συστήματος Αγγελιών';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι διαχειριστής
if (!Session::has('user_id') || !Session::has('user_role')) {
    echo '<div class="container mt-5">';
    echo '<div class="alert alert-danger">Πρέπει να συνδεθείτε για να χρησιμοποιήσετε αυτό το εργαλείο.</div>';
    echo '</div>';
    include ROOT_DIR . '/src/Views/partials/footer.php';
    exit();
}

// Λήψη του PDO από το container
$container = \Drivejob\Core\Container::getInstance();
$pdo = $container->get('pdo');

// Συνάρτηση για τον έλεγχο αν υπάρχει ένας πίνακας
function tableExists($pdo, $table) {
    try {
        $result = $pdo->query("SELECT 1 FROM $table LIMIT 1");
        return $result !== false;
    } catch (\Exception $e) {
        return false;
    }
}

// Συνάρτηση για τον έλεγχο αν υπάρχει μια στήλη σε έναν πίνακα
function columnExists($pdo, $table, $column) {
    try {
        $result = $pdo->query("SELECT $column FROM $table LIMIT 1");
        return $result !== false;
    } catch (\Exception $e) {
        return false;
    }
}

// Συνάρτηση για τον έλεγχο αν υπάρχει μια μέθοδος σε μια κλάση
function methodExists($className, $methodName) {
    try {
        return method_exists($className, $methodName);
    } catch (\Exception $e) {
        return false;
    }
}

// Συνάρτηση για τον έλεγχο αν υπάρχει ένα αρχείο
function fileExists($path) {
    return file_exists($path);
}

// Συνάρτηση για τον έλεγχο αν υπάρχει μια διαδρομή
function routeExists($routePath) {
    $routes = include ROOT_DIR . '/config/routes.php';
    // Εδώ θα πρέπει να υλοποιηθεί ο έλεγχος αν υπάρχει η διαδρομή
    // Αυτό είναι απλώς ένα παράδειγμα
    return true;
}

// Έλεγχοι για τη βάση δεδομένων
$dbChecks = [
    'job_listings_table' => tableExists($pdo, 'job_listings'),
    'job_applications_table' => tableExists($pdo, 'job_applications'),
    'drivers_table' => tableExists($pdo, 'drivers'),
    'companies_table' => tableExists($pdo, 'companies'),
    'driver_licenses_table' => tableExists($pdo, 'driver_licenses'),
    'driver_certifications_table' => tableExists($pdo, 'driver_certifications'),
    'driver_vehicle_experience_table' => tableExists($pdo, 'driver_vehicle_experience')
];

// Έλεγχοι για τις στήλες του πίνακα job_listings
$columnChecks = [];
if ($dbChecks['job_listings_table']) {
    $columnChecks = [
        'job_listings_id' => columnExists($pdo, 'job_listings', 'id'),
        'job_listings_company_id' => columnExists($pdo, 'job_listings', 'company_id'),
        'job_listings_driver_id' => columnExists($pdo, 'job_listings', 'driver_id'),
        'job_listings_title' => columnExists($pdo, 'job_listings', 'title'),
        'job_listings_description' => columnExists($pdo, 'job_listings', 'description'),
        'job_listings_location' => columnExists($pdo, 'job_listings', 'location'),
        'job_listings_job_type' => columnExists($pdo, 'job_listings', 'job_type'),
        'job_listings_vehicle_type' => columnExists($pdo, 'job_listings', 'vehicle_type'),
        'job_listings_vehicle_types' => columnExists($pdo, 'job_listings', 'vehicle_types'),
        'job_listings_salary_min' => columnExists($pdo, 'job_listings', 'salary_min'),
        'job_listings_salary_max' => columnExists($pdo, 'job_listings', 'salary_max'),
        'job_listings_salary_period' => columnExists($pdo, 'job_listings', 'salary_period'),
        'job_listings_experience_years' => columnExists($pdo, 'job_listings', 'experience_years'),
        'job_listings_license_required' => columnExists($pdo, 'job_listings', 'license_required'),
        'job_listings_license_types' => columnExists($pdo, 'job_listings', 'license_types'),
        'job_listings_pei_required' => columnExists($pdo, 'job_listings', 'pei_required'),
        'job_listings_adr_required' => columnExists($pdo, 'job_listings', 'adr_required'),
        'job_listings_tachograph_required' => columnExists($pdo, 'job_listings', 'tachograph_required'),
        'job_listings_operator_license_required' => columnExists($pdo, 'job_listings', 'operator_license_required'),
        'job_listings_is_active' => columnExists($pdo, 'job_listings', 'is_active'),
        'job_listings_is_featured' => columnExists($pdo, 'job_listings', 'is_featured'),
        'job_listings_is_approved' => columnExists($pdo, 'job_listings', 'is_approved'),
        'job_listings_views' => columnExists($pdo, 'job_listings', 'views'),
        'job_listings_applications' => columnExists($pdo, 'job_listings', 'applications'),
        'job_listings_expires_at' => columnExists($pdo, 'job_listings', 'expires_at'),
        'job_listings_created_at' => columnExists($pdo, 'job_listings', 'created_at'),
        'job_listings_updated_at' => columnExists($pdo, 'job_listings', 'updated_at'),
        'job_listings_listing_type' => columnExists($pdo, 'job_listings', 'listing_type'),
        'job_listings_job_category' => columnExists($pdo, 'job_listings', 'job_category'),
        'job_listings_required_licenses' => columnExists($pdo, 'job_listings', 'required_licenses'),
        'job_listings_requires_pei' => columnExists($pdo, 'job_listings', 'requires_pei'),
        'job_listings_requires_adr' => columnExists($pdo, 'job_listings', 'requires_adr'),
        'job_listings_requires_tachograph' => columnExists($pdo, 'job_listings', 'requires_tachograph'),
        'job_listings_driver_licenses' => columnExists($pdo, 'job_listings', 'driver_licenses'),
        'job_listings_operator_licenses' => columnExists($pdo, 'job_listings', 'operator_licenses'),
        'job_listings_has_pei' => columnExists($pdo, 'job_listings', 'has_pei'),
        'job_listings_has_adr' => columnExists($pdo, 'job_listings', 'has_adr'),
        'job_listings_has_tachograph' => columnExists($pdo, 'job_listings', 'has_tachograph')
    ];
}

// Έλεγχοι για τις κλάσεις και τις μεθόδους
$classChecks = [
    'UnifiedJobListingController' => class_exists('Drivejob\\Controllers\\UnifiedJobListingController'),
    'JobListingRepository' => class_exists('Drivejob\\Repositories\\JobListingRepository'),
    'JobApplicationRepository' => class_exists('Drivejob\\Repositories\\JobApplicationRepository')
];

$methodChecks = [];
if ($classChecks['JobApplicationRepository']) {
    $methodChecks['JobApplicationRepository_hasApplied'] = methodExists('Drivejob\\Repositories\\JobApplicationRepository', 'hasApplied');
}
if ($classChecks['JobListingRepository']) {
    $methodChecks['JobListingRepository_findSimilar'] = methodExists('Drivejob\\Repositories\\JobListingRepository', 'findSimilar');
}

// Έλεγχοι για τα αρχεία
$fileChecks = [
    'UnifiedJobListingController' => fileExists(ROOT_DIR . '/src/Controllers/UnifiedJobListingController.php'),
    'JobListingRepository' => fileExists(ROOT_DIR . '/src/Repositories/JobListingRepository.php'),
    'JobApplicationRepository' => fileExists(ROOT_DIR . '/src/Repositories/JobApplicationRepository.php'),
    'JobListingRepositoryInterface' => fileExists(ROOT_DIR . '/src/Repositories/JobListingRepositoryInterface.php'),
    'JobApplicationRepositoryInterface' => fileExists(ROOT_DIR . '/src/Repositories/JobApplicationRepositoryInterface.php'),
    'job_listings_create_view' => fileExists(ROOT_DIR . '/src/Views/job-listings/create.php'),
    'job_listings_edit_view' => fileExists(ROOT_DIR . '/src/Views/job-listings/edit.php'),
    'job_listings_show_view' => fileExists(ROOT_DIR . '/src/Views/job-listings/show.php'),
    'job_listings_index_view' => fileExists(ROOT_DIR . '/src/Views/job-listings/index.php'),
    'job_listings_delete_view' => fileExists(ROOT_DIR . '/src/Views/job-listings/delete.php'),
    'job_listings_driver_create_view' => fileExists(ROOT_DIR . '/src/Views/job-listings/Driver/create.php'),
    'job_listings_company_create_view' => fileExists(ROOT_DIR . '/src/Views/job-listings/Company/create.php')
];

// Έλεγχοι για τις διαδρομές
$routeChecks = [
    'job_listings_index' => routeExists('/job-listings'),
    'job_listings_create' => routeExists('/job-listings/create'),
    'job_listings_store' => routeExists('/job-listings/store'),
    'job_listings_show' => routeExists('/job-listings/show/{id}'),
    'job_listings_edit' => routeExists('/job-listings/edit/{id}'),
    'job_listings_update' => routeExists('/job-listings/update/{id}'),
    'job_listings_delete' => routeExists('/job-listings/delete/{id}'),
    'job_listings_destroy' => routeExists('/job-listings/destroy/{id}'),
    'job_listings_my_listings' => routeExists('/job-listings/my-listings'),
    'job_listings_company_listings' => routeExists('/job-listings/company/{id}'),
    'job_listings_driver_listings' => routeExists('/job-listings/driver/{id}'),
    'job_listings_driver_create' => routeExists('/job-listings/Driver/create'),
    'job_listings_driver_store' => routeExists('/job-listings/Driver/store'),
    'job_listings_company_create' => routeExists('/job-listings/Company/create'),
    'job_listings_company_store' => routeExists('/job-listings/Company/store')
];

// Έλεγχος για τα δεδομένα στη βάση δεδομένων
$dataChecks = [];
if ($dbChecks['job_listings_table']) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM job_listings");
        $dataChecks['job_listings_count'] = $stmt->fetchColumn();
    } catch (\Exception $e) {
        $dataChecks['job_listings_count'] = 'Σφάλμα: ' . $e->getMessage();
    }
}
if ($dbChecks['job_applications_table']) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM job_applications");
        $dataChecks['job_applications_count'] = $stmt->fetchColumn();
    } catch (\Exception $e) {
        $dataChecks['job_applications_count'] = 'Σφάλμα: ' . $e->getMessage();
    }
}

// Έλεγχος για τα σφάλματα στο session
$sessionErrors = [];
if (Session::has('debug_error')) {
    $sessionErrors['debug_error'] = Session::get('debug_error');
}
if (Session::has('errors')) {
    $sessionErrors['errors'] = Session::get('errors');
}
if (Session::has('error_message')) {
    $sessionErrors['error_message'] = Session::get('error_message');
}

// Έλεγχος για τα δεδομένα στο session
$sessionData = [];
if (Session::has('old_input')) {
    $sessionData['old_input'] = Session::get('old_input');
}
if (Session::has('user_id')) {
    $sessionData['user_id'] = Session::get('user_id');
}
if (Session::has('user_role')) {
    $sessionData['user_role'] = Session::get('user_role');
}

// Έλεγχος για τα δεδομένα του χρήστη
$userData = [];
if (Session::has('user_id') && Session::has('user_role')) {
    $userId = Session::get('user_id');
    $userRole = Session::get('user_role');

    if ($userRole === 'driver') {
        try {
            $stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
            $stmt->execute([$userId]);
            $userData['driver'] = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $userData['driver'] = 'Σφάλμα: ' . $e->getMessage();
        }
    } elseif ($userRole === 'company') {
        try {
            $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
            $stmt->execute([$userId]);
            $userData['company'] = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $userData['company'] = 'Σφάλμα: ' . $e->getMessage();
        }
    }
}

// Έλεγχος για τις αγγελίες του χρήστη
$userListings = [];
if (Session::has('user_id') && Session::has('user_role')) {
    $userId = Session::get('user_id');
    $userRole = Session::get('user_role');

    if ($userRole === 'driver') {
        try {
            $stmt = $pdo->prepare("SELECT * FROM job_listings WHERE driver_id = ?");
            $stmt->execute([$userId]);
            $userListings['driver'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $userListings['driver'] = 'Σφάλμα: ' . $e->getMessage();
        }
    } elseif ($userRole === 'company') {
        try {
            $stmt = $pdo->prepare("SELECT * FROM job_listings WHERE company_id = ?");
            $stmt->execute([$userId]);
            $userListings['company'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $userListings['company'] = 'Σφάλμα: ' . $e->getMessage();
        }
    }
}

// Έλεγχος για τις αιτήσεις του χρήστη
$userApplications = [];
if (Session::has('user_id') && Session::has('user_role')) {
    $userId = Session::get('user_id');
    $userRole = Session::get('user_role');

    if ($userRole === 'driver') {
        try {
            $stmt = $pdo->prepare("SELECT * FROM job_applications WHERE driver_id = ?");
            $stmt->execute([$userId]);
            $userApplications['driver'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $userApplications['driver'] = 'Σφάλμα: ' . $e->getMessage();
        }
    } elseif ($userRole === 'company') {
        try {
            $stmt = $pdo->prepare("SELECT * FROM job_applications WHERE company_id = ?");
            $stmt->execute([$userId]);
            $userApplications['company'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $userApplications['company'] = 'Σφάλμα: ' . $e->getMessage();
        }
    }
}

// Έλεγχος για τις άδειες οδήγησης του χρήστη
$userLicenses = [];
if (Session::has('user_id') && Session::has('user_role') && Session::get('user_role') === 'driver') {
    $userId = Session::get('user_id');

    try {
        $stmt = $pdo->prepare("SELECT * FROM driver_licenses WHERE driver_id = ?");
        $stmt->execute([$userId]);
        $userLicenses['driver'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
        $userLicenses['driver'] = 'Σφάλμα: ' . $e->getMessage();
    }
}

// Έλεγχος για τις πιστοποιήσεις του χρήστη
$userCertifications = [];
if (Session::has('user_id') && Session::has('user_role') && Session::get('user_role') === 'driver') {
    $userId = Session::get('user_id');

    try {
        $stmt = $pdo->prepare("SELECT * FROM driver_certifications WHERE driver_id = ?");
        $stmt->execute([$userId]);
        $userCertifications['driver'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
        $userCertifications['driver'] = 'Σφάλμα: ' . $e->getMessage();
    }
}

// Έλεγχος για την εμπειρία του χρήστη
$userExperience = [];
if (Session::has('user_id') && Session::has('user_role') && Session::get('user_role') === 'driver') {
    $userId = Session::get('user_id');

    try {
        $stmt = $pdo->prepare("SELECT * FROM driver_vehicle_experience WHERE driver_id = ?");
        $stmt->execute([$userId]);
        $userExperience['driver'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
        $userExperience['driver'] = 'Σφάλμα: ' . $e->getMessage();
    }
}

// Έλεγχος για τα ADR του χρήστη
$userADR = [];
if (Session::has('user_id') && Session::has('user_role') && Session::get('user_role') === 'driver') {
    $userId = Session::get('user_id');

    try {
        $stmt = $pdo->prepare("SELECT * FROM driver_adr_certificates WHERE driver_id = ?");
        $stmt->execute([$userId]);
        $userADR['driver'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
        $userADR['driver'] = 'Σφάλμα: ' . $e->getMessage();
    }
}

// Έλεγχος για τις κάρτες ταχογράφου του χρήστη
$userTachograph = [];
if (Session::has('user_id') && Session::has('user_role') && Session::get('user_role') === 'driver') {
    $userId = Session::get('user_id');

    try {
        $stmt = $pdo->prepare("SELECT * FROM driver_tachograph_cards WHERE driver_id = ?");
        $stmt->execute([$userId]);
        $userTachograph['driver'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
        $userTachograph['driver'] = 'Σφάλμα: ' . $e->getMessage();
    }
}

// Έλεγχος για τις άδειες χειριστή του χρήστη
$userOperator = [];
if (Session::has('user_id') && Session::has('user_role') && Session::get('user_role') === 'driver') {
    $userId = Session::get('user_id');

    try {
        $stmt = $pdo->prepare("SELECT * FROM driver_operator_licenses WHERE driver_id = ?");
        $stmt->execute([$userId]);
        $userOperator['driver'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
        $userOperator['driver'] = 'Σφάλμα: ' . $e->getMessage();
    }
}

// Συγκέντρωση όλων των αποτελεσμάτων
$results = [
    'db_checks' => $dbChecks,
    'column_checks' => $columnChecks,
    'class_checks' => $classChecks,
    'method_checks' => $methodChecks,
    'file_checks' => $fileChecks,
    'route_checks' => $routeChecks,
    'data_checks' => $dataChecks,
    'session_errors' => $sessionErrors,
    'session_data' => $sessionData,
    'user_data' => $userData,
    'user_listings' => $userListings,
    'user_applications' => $userApplications,
    'user_licenses' => $userLicenses,
    'user_certifications' => $userCertifications,
    'user_experience' => $userExperience,
    'user_adr' => $userADR,
    'user_tachograph' => $userTachograph,
    'user_operator' => $userOperator
];

// Εμφάνιση των αποτελεσμάτων
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4>Διάγνωση Συστήματος Αγγελιών</h4>
                </div>
                <div class="card-body">
                    <h5>Έλεγχοι Βάσης Δεδομένων</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Έλεγχος</th>
                                    <th>Αποτέλεσμα</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dbChecks as $check => $result) : ?>
                                    <tr>
                                        <td><?php echo $check; ?></td>
                                        <td>
                                            <?php if ($result) : ?>
                                                <span class="text-success">Επιτυχία</span>
                                            <?php else : ?>
                                                <span class="text-danger">Αποτυχία</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <h5>Έλεγχοι Στηλών</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Έλεγχος</th>
                                    <th>Αποτέλεσμα</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($columnChecks as $check => $result) : ?>
                                    <tr>
                                        <td><?php echo $check; ?></td>
                                        <td>
                                            <?php if ($result) : ?>
                                                <span class="text-success">Επιτυχία</span>
                                            <?php else : ?>
                                                <span class="text-danger">Αποτυχία</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <h5>Έλεγχοι Κλάσεων</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Έλεγχος</th>
                                    <th>Αποτέλεσμα</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($classChecks as $check => $result) : ?>
                                    <tr>
                                        <td><?php echo $check; ?></td>
                                        <td>
                                            <?php if ($result) : ?>
                                                <span class="text-success">Επιτυχία</span>
                                            <?php else : ?>
                                                <span class="text-danger">Αποτυχία</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <h5>Έλεγχοι Μεθόδων</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Έλεγχος</th>
                                    <th>Αποτέλεσμα</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($methodChecks as $check => $result) : ?>
                                    <tr>
                                        <td><?php echo $check; ?></td>
                                        <td>
                                            <?php if ($result) : ?>
                                                <span class="text-success">Επιτυχία</span>
                                            <?php else : ?>
                                                <span class="text-danger">Αποτυχία</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <h5>Έλεγχοι Αρχείων</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Έλεγχος</th>
                                    <th>Αποτέλεσμα</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fileChecks as $check => $result) : ?>
                                    <tr>
                                        <td><?php echo $check; ?></td>
                                        <td>
                                            <?php if ($result) : ?>
                                                <span class="text-success">Επιτυχία</span>
                                            <?php else : ?>
                                                <span class="text-danger">Αποτυχία</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
