<?php
/**
 * Δοκιμαστικό script για τα προφίλ των οδηγών και των εταιρειών
 * 
 * Αυτό το script δοκιμάζει τη λειτουργικότητα των προφίλ των οδηγών και των εταιρειών
 * χρησιμοποιώντας τα διαπιστευτήρια που παρέχονται από τον χρήστη.
 */

// Φόρτωση του bootstrap
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Core\Logger;

// Τίτλος σελίδας
$pageTitle = 'Δοκιμή Προφίλ';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';

// Λήψη του PDO από το container
$container = \Drivejob\Core\Container::getInstance();
$pdo = $container->get('pdo');

// Συνάρτηση για τη δοκιμή σύνδεσης
function testLogin($email, $password) {
    try {
        $pdo = $GLOBALS['pdo'];
        
        // Έλεγχος αν υπάρχει οδηγός με αυτό το email
        $stmt = $pdo->prepare("SELECT * FROM drivers WHERE email = ?");
        $stmt->execute([$email]);
        $driver = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($driver) {
            // Έλεγχος του κωδικού
            if (password_verify($password, $driver['password'])) {
                return [
                    'success' => true,
                    'user_type' => 'driver',
                    'user_id' => $driver['id'],
                    'user_name' => $driver['first_name'] . ' ' . $driver['last_name']
                ];
            }
        }
        
        // Έλεγχος αν υπάρχει εταιρεία με αυτό το email
        $stmt = $pdo->prepare("SELECT * FROM companies WHERE email = ?");
        $stmt->execute([$email]);
        $company = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($company) {
            // Έλεγχος του κωδικού
            if (password_verify($password, $company['password'])) {
                return [
                    'success' => true,
                    'user_type' => 'company',
                    'user_id' => $company['id'],
                    'user_name' => $company['company_name']
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'Λάθος email ή κωδικός πρόσβασης'
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Σφάλμα κατά τη σύνδεση: ' . $e->getMessage()
        ];
    }
}

// Συνάρτηση για τη δοκιμή προβολής προφίλ οδηγού
function testDriverProfile($driverId) {
    try {
        $pdo = $GLOBALS['pdo'];
        
        // Δημιουργία του controller
        $controller = new \Drivejob\Controllers\DriversController($pdo);
        
        // Αποθήκευση του ID του οδηγού στο session
        $_SESSION['user_id'] = $driverId;
        $_SESSION['role'] = 'driver';
        
        // Κλήση της μεθόδου profile
        ob_start();
        $controller->profile();
        $output = ob_get_clean();
        
        return [
            'success' => true,
            'output' => $output
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Σφάλμα κατά την προβολή του προφίλ οδηγού: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

// Συνάρτηση για τη δοκιμή προβολής προφίλ εταιρείας
function testCompanyProfile($companyId) {
    try {
        $pdo = $GLOBALS['pdo'];
        
        // Δημιουργία του controller
        $controller = new \Drivejob\Controllers\CompaniesController($pdo);
        
        // Αποθήκευση του ID της εταιρείας στο session
        $_SESSION['user_id'] = $companyId;
        $_SESSION['role'] = 'company';
        
        // Κλήση της μεθόδου profile
        ob_start();
        $controller->profile();
        $output = ob_get_clean();
        
        return [
            'success' => true,
            'output' => $output
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Σφάλμα κατά την προβολή του προφίλ εταιρείας: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

// Συνάρτηση για τη δοκιμή προβολής αγγελιών οδηγού
function testDriverListings($driverId) {
    try {
        $pdo = $GLOBALS['pdo'];
        
        // Δημιουργία του controller
        $controller = new \Drivejob\Controllers\UnifiedJobListingController($pdo);
        
        // Αποθήκευση του ID του οδηγού στο session
        $_SESSION['user_id'] = $driverId;
        $_SESSION['role'] = 'driver';
        
        // Κλήση της μεθόδου myListings
        ob_start();
        $controller->myListings();
        $output = ob_get_clean();
        
        return [
            'success' => true,
            'output' => $output
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Σφάλμα κατά την προβολή των αγγελιών οδηγού: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

// Συνάρτηση για τη δοκιμή προβολής αγγελιών εταιρείας
function testCompanyListings($companyId) {
    try {
        $pdo = $GLOBALS['pdo'];
        
        // Δημιουργία του controller
        $controller = new \Drivejob\Controllers\UnifiedJobListingController($pdo);
        
        // Αποθήκευση του ID της εταιρείας στο session
        $_SESSION['user_id'] = $companyId;
        $_SESSION['role'] = 'company';
        
        // Κλήση της μεθόδου myListings
        ob_start();
        $controller->myListings();
        $output = ob_get_clean();
        
        return [
            'success' => true,
            'output' => $output
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Σφάλμα κατά την προβολή των αγγελιών εταιρείας: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

// Εκτέλεση των δοκιμών
$results = [];

// Δοκιμή σύνδεσης ως οδηγός
$driverLoginResult = testLogin('kostas.michailidis@hotmail.gr', '123456');
$results['driver_login'] = $driverLoginResult;

// Δοκιμή σύνδεσης ως εταιρεία
$companyLoginResult = testLogin('info@thessdrive.gr', '123456');
$results['company_login'] = $companyLoginResult;

// Δοκιμή προβολής προφίλ οδηγού
if ($driverLoginResult['success']) {
    $driverProfileResult = testDriverProfile($driverLoginResult['user_id']);
    $results['driver_profile'] = $driverProfileResult;
}

// Δοκιμή προβολής προφίλ εταιρείας
if ($companyLoginResult['success']) {
    $companyProfileResult = testCompanyProfile($companyLoginResult['user_id']);
    $results['company_profile'] = $companyProfileResult;
}

// Δοκιμή προβολής αγγελιών οδηγού
if ($driverLoginResult['success']) {
    $driverListingsResult = testDriverListings($driverLoginResult['user_id']);
    $results['driver_listings'] = $driverListingsResult;
}

// Δοκιμή προβολής αγγελιών εταιρείας
if ($companyLoginResult['success']) {
    $companyListingsResult = testCompanyListings($companyLoginResult['user_id']);
    $results['company_listings'] = $companyListingsResult;
}

// Εμφάνιση των αποτελεσμάτων
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4>Δοκιμή Προφίλ</h4>
                </div>
                <div class="card-body">
                    <h5>Αποτελέσματα Δοκιμών</h5>
                    
                    <h6>Σύνδεση ως Οδηγός</h6>
                    <div class="alert <?php echo $driverLoginResult['success'] ? 'alert-success' : 'alert-danger'; ?>">
                        <?php if ($driverLoginResult['success']) : ?>
                            <p>Επιτυχής σύνδεση ως οδηγός: <?php echo $driverLoginResult['user_name']; ?> (ID: <?php echo $driverLoginResult['user_id']; ?>)</p>
                        <?php else : ?>
                            <p>Αποτυχία σύνδεσης ως οδηγός: <?php echo $driverLoginResult['message']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <h6>Σύνδεση ως Εταιρεία</h6>
                    <div class="alert <?php echo $companyLoginResult['success'] ? 'alert-success' : 'alert-danger'; ?>">
                        <?php if ($companyLoginResult['success']) : ?>
                            <p>Επιτυχής σύνδεση ως εταιρεία: <?php echo $companyLoginResult['user_name']; ?> (ID: <?php echo $companyLoginResult['user_id']; ?>)</p>
                        <?php else : ?>
                            <p>Αποτυχία σύνδεσης ως εταιρεία: <?php echo $companyLoginResult['message']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isset($results['driver_profile'])) : ?>
                        <h6>Προβολή Προφίλ Οδηγού</h6>
                        <div class="alert <?php echo $results['driver_profile']['success'] ? 'alert-success' : 'alert-danger'; ?>">
                            <?php if ($results['driver_profile']['success']) : ?>
                                <p>Επιτυχής προβολή προφίλ οδηγού</p>
                            <?php else : ?>
                                <p>Αποτυχία προβολής προφίλ οδηγού: <?php echo $results['driver_profile']['message']; ?></p>
                                <?php if (isset($results['driver_profile']['trace'])) : ?>
                                    <pre><?php echo $results['driver_profile']['trace']; ?></pre>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($results['company_profile'])) : ?>
                        <h6>Προβολή Προφίλ Εταιρείας</h6>
                        <div class="alert <?php echo $results['company_profile']['success'] ? 'alert-success' : 'alert-danger'; ?>">
                            <?php if ($results['company_profile']['success']) : ?>
                                <p>Επιτυχής προβολή προφίλ εταιρείας</p>
                            <?php else : ?>
                                <p>Αποτυχία προβολής προφίλ εταιρείας: <?php echo $results['company_profile']['message']; ?></p>
                                <?php if (isset($results['company_profile']['trace'])) : ?>
                                    <pre><?php echo $results['company_profile']['trace']; ?></pre>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($results['driver_listings'])) : ?>
                        <h6>Προβολή Αγγελιών Οδηγού</h6>
                        <div class="alert <?php echo $results['driver_listings']['success'] ? 'alert-success' : 'alert-danger'; ?>">
                            <?php if ($results['driver_listings']['success']) : ?>
                                <p>Επιτυχής προβολή αγγελιών οδηγού</p>
                            <?php else : ?>
                                <p>Αποτυχία προβολής αγγελιών οδηγού: <?php echo $results['driver_listings']['message']; ?></p>
                                <?php if (isset($results['driver_listings']['trace'])) : ?>
                                    <pre><?php echo $results['driver_listings']['trace']; ?></pre>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($results['company_listings'])) : ?>
                        <h6>Προβολή Αγγελιών Εταιρείας</h6>
                        <div class="alert <?php echo $results['company_listings']['success'] ? 'alert-success' : 'alert-danger'; ?>">
                            <?php if ($results['company_listings']['success']) : ?>
                                <p>Επιτυχής προβολή αγγελιών εταιρείας</p>
                            <?php else : ?>
                                <p>Αποτυχία προβολής αγγελιών εταιρείας: <?php echo $results['company_listings']['message']; ?></p>
                                <?php if (isset($results['company_listings']['trace'])) : ?>
                                    <pre><?php echo $results['company_listings']['trace']; ?></pre>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="<?php echo BASE_URL; ?>" class="btn btn-primary">Επιστροφή στην Αρχική</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>
