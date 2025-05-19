<?php

/**
 * Δοκιμαστικό script για το σύστημα αγγελιών
 * 
 * Αυτό το script δοκιμάζει τη λειτουργικότητα του συστήματος αγγελιών
 * χρησιμοποιώντας τα διαπιστευτήρια που παρέχονται από τον χρήστη.
 */

// Φόρτωση του bootstrap
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Core\Logger;

// Τίτλος σελίδας
$pageTitle = 'Δοκιμή Συστήματος Αγγελιών';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';

// Λήψη του PDO από το container
$container = \Drivejob\Core\Container::getInstance();
$pdo = $container->get('pdo');

// Συνάρτηση για τη μετατροπή δεδομένων σε JSON
function jsonEncode($data)
{
    if (function_exists('json_encode')) {
        return json_encode($data);
    } else {
        // Απλή υλοποίηση για πίνακες
        if (is_array($data)) {
            $json = '{';
            $first = true;
            foreach ($data as $key => $value) {
                if (!$first) {
                    $json .= ',';
                }
                $first = false;
                $json .= '"' . $key . '":';
                if (is_array($value)) {
                    $json .= jsonEncode($value);
                } elseif (is_string($value)) {
                    $json .= '"' . str_replace('"', '\"', $value) . '"';
                } elseif (is_bool($value)) {
                    $json .= $value ? 'true' : 'false';
                } elseif (is_null($value)) {
                    $json .= 'null';
                } else {
                    $json .= $value;
                }
            }
            $json .= '}';
            return $json;
        } elseif (is_string($data)) {
            return '"' . str_replace('"', '\"', $data) . '"';
        } elseif (is_bool($data)) {
            return $data ? 'true' : 'false';
        } elseif (is_null($data)) {
            return 'null';
        } else {
            return $data;
        }
    }
}

// Συνάρτηση για τη δοκιμή σύνδεσης
function testLogin($email, $password)
{
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

// Συνάρτηση για τη δοκιμή δημιουργίας αγγελίας από οδηγό
function testCreateDriverListing($driverId)
{
    try {
        $pdo = $GLOBALS['pdo'];

        // Λήψη των στοιχείων του οδηγού
        $stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
        $stmt->execute([$driverId]);
        $driver = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$driver) {
            return [
                'success' => false,
                'message' => 'Ο οδηγός δεν βρέθηκε'
            ];
        }

        // Δημιουργία των δεδομένων της αγγελίας
        $data = [
            'driver_id' => $driverId,
            'company_id' => null,
            'title' => 'Αναζήτηση εργασίας ως οδηγός',
            'description' => 'Αναζητώ εργασία ως οδηγός. Έχω εμπειρία σε μεταφορές εμπορευμάτων και επιβατών.',
            'location' => 'Αθήνα',
            'job_type' => 'full_time',
            'vehicle_type' => 'truck',
            'vehicle_types' => 'truck,bus',
            'salary_min' => 1000,
            'salary_max' => 1500,
            'salary_period' => 'monthly',
            'experience_years' => 5,
            'listing_type' => 'job_search',
            'is_active' => 1,
            'is_approved' => 1,
            'views' => 0,
            'applications' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Λήψη των αδειών οδήγησης του οδηγού
        $stmt = $pdo->prepare("SELECT * FROM driver_licenses WHERE driver_id = ?");
        $stmt->execute([$driverId]);
        $licenses = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (!empty($licenses)) {
            $data['driver_licenses'] = jsonEncode($licenses);

            // Έλεγχος αν ο οδηγός έχει ΠΕΙ
            $hasPEI = false;
            foreach ($licenses as $license) {
                if (isset($license['has_pei']) && $license['has_pei']) {
                    $hasPEI = true;
                    break;
                }
            }
            $data['has_pei'] = $hasPEI ? 1 : 0;
        }

        // Λήψη των ADR του οδηγού
        $stmt = $pdo->prepare("SELECT * FROM driver_adr_certificates WHERE driver_id = ?");
        $stmt->execute([$driverId]);
        $adr = $stmt->fetch(\PDO::FETCH_ASSOC);

        $data['has_adr'] = $adr ? 1 : 0;

        // Λήψη των καρτών ταχογράφου του οδηγού
        $stmt = $pdo->prepare("SELECT * FROM driver_tachograph_cards WHERE driver_id = ?");
        $stmt->execute([$driverId]);
        $tachograph = $stmt->fetch(\PDO::FETCH_ASSOC);

        $data['has_tachograph'] = $tachograph ? 1 : 0;

        // Εισαγωγή της αγγελίας στη βάση δεδομένων
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $stmt = $pdo->prepare("INSERT INTO job_listings ({$columns}) VALUES ({$placeholders})");
        $result = $stmt->execute(array_values($data));

        if ($result) {
            $listingId = $pdo->lastInsertId();
            return [
                'success' => true,
                'listing_id' => $listingId,
                'message' => 'Η αγγελία δημιουργήθηκε με επιτυχία'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Σφάλμα κατά τη δημιουργία της αγγελίας',
                'error' => $stmt->errorInfo()
            ];
        }
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Σφάλμα κατά τη δημιουργία της αγγελίας: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

// Συνάρτηση για τη δοκιμή δημιουργίας αγγελίας από εταιρεία
function testCreateCompanyListing($companyId)
{
    try {
        $pdo = $GLOBALS['pdo'];

        // Λήψη των στοιχείων της εταιρείας
        $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
        $stmt->execute([$companyId]);
        $company = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$company) {
            return [
                'success' => false,
                'message' => 'Η εταιρεία δεν βρέθηκε'
            ];
        }

        // Δημιουργία των δεδομένων της αγγελίας
        $data = [
            'driver_id' => null,
            'company_id' => $companyId,
            'title' => 'Αναζήτηση οδηγού για μεταφορές',
            'description' => 'Αναζητούμε οδηγό για μεταφορές εμπορευμάτων. Απαραίτητη η κατοχή διπλώματος Γ κατηγορίας.',
            'location' => 'Αθήνα',
            'job_type' => 'full_time',
            'vehicle_type' => 'truck',
            'vehicle_types' => 'truck',
            'salary_min' => 1000,
            'salary_max' => 1500,
            'salary_period' => 'monthly',
            'experience_years' => 2,
            'listing_type' => 'job_offer',
            'is_active' => 1,
            'is_approved' => 1,
            'views' => 0,
            'applications' => 0,
            'required_licenses' => 'C,CE',
            'requires_pei' => 1,
            'requires_adr' => 0,
            'requires_tachograph' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Εισαγωγή της αγγελίας στη βάση δεδομένων
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $stmt = $pdo->prepare("INSERT INTO job_listings ({$columns}) VALUES ({$placeholders})");
        $result = $stmt->execute(array_values($data));

        if ($result) {
            $listingId = $pdo->lastInsertId();
            return [
                'success' => true,
                'listing_id' => $listingId,
                'message' => 'Η αγγελία δημιουργήθηκε με επιτυχία'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Σφάλμα κατά τη δημιουργία της αγγελίας',
                'error' => $stmt->errorInfo()
            ];
        }
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Σφάλμα κατά τη δημιουργία της αγγελίας: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
}

// Συνάρτηση για τη δοκιμή αναζήτησης αγγελιών
function testSearchListings()
{
    try {
        $pdo = $GLOBALS['pdo'];

        // Αναζήτηση αγγελιών
        $stmt = $pdo->query("SELECT * FROM job_listings WHERE is_active = 1 LIMIT 10");
        $listings = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'listings' => $listings,
            'count' => count($listings)
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Σφάλμα κατά την αναζήτηση αγγελιών: ' . $e->getMessage()
        ];
    }
}

// Συνάρτηση για τη δοκιμή προβολής αγγελίας
function testViewListing($listingId)
{
    try {
        $pdo = $GLOBALS['pdo'];

        // Λήψη της αγγελίας
        $stmt = $pdo->prepare("SELECT * FROM job_listings WHERE id = ?");
        $stmt->execute([$listingId]);
        $listing = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$listing) {
            return [
                'success' => false,
                'message' => 'Η αγγελία δεν βρέθηκε'
            ];
        }

        // Αύξηση των προβολών
        $stmt = $pdo->prepare("UPDATE job_listings SET views = views + 1 WHERE id = ?");
        $stmt->execute([$listingId]);

        return [
            'success' => true,
            'listing' => $listing
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Σφάλμα κατά την προβολή της αγγελίας: ' . $e->getMessage()
        ];
    }
}

// Συνάρτηση για τη δοκιμή επεξεργασίας αγγελίας
function testUpdateListing($listingId, $data)
{
    try {
        $pdo = $GLOBALS['pdo'];

        // Λήψη της αγγελίας
        $stmt = $pdo->prepare("SELECT * FROM job_listings WHERE id = ?");
        $stmt->execute([$listingId]);
        $listing = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$listing) {
            return [
                'success' => false,
                'message' => 'Η αγγελία δεν βρέθηκε'
            ];
        }

        // Ενημέρωση της αγγελίας
        $setClause = [];
        $params = [];

        foreach ($data as $key => $value) {
            $setClause[] = "{$key} = ?";
            $params[] = $value;
        }

        $params[] = $listingId;

        $stmt = $pdo->prepare("UPDATE job_listings SET " . implode(', ', $setClause) . " WHERE id = ?");
        $result = $stmt->execute($params);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Η αγγελία ενημερώθηκε με επιτυχία'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Σφάλμα κατά την ενημέρωση της αγγελίας',
                'error' => $stmt->errorInfo()
            ];
        }
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Σφάλμα κατά την ενημέρωση της αγγελίας: ' . $e->getMessage()
        ];
    }
}

// Συνάρτηση για τη δοκιμή διαγραφής αγγελίας
function testDeleteListing($listingId)
{
    try {
        $pdo = $GLOBALS['pdo'];

        // Λήψη της αγγελίας
        $stmt = $pdo->prepare("SELECT * FROM job_listings WHERE id = ?");
        $stmt->execute([$listingId]);
        $listing = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$listing) {
            return [
                'success' => false,
                'message' => 'Η αγγελία δεν βρέθηκε'
            ];
        }

        // Διαγραφή της αγγελίας
        $stmt = $pdo->prepare("DELETE FROM job_listings WHERE id = ?");
        $result = $stmt->execute([$listingId]);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Η αγγελία διαγράφηκε με επιτυχία'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Σφάλμα κατά τη διαγραφή της αγγελίας',
                'error' => $stmt->errorInfo()
            ];
        }
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Σφάλμα κατά τη διαγραφή της αγγελίας: ' . $e->getMessage()
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

// Δοκιμή δημιουργίας αγγελίας από οδηγό
if ($driverLoginResult['success']) {
    $driverListingResult = testCreateDriverListing($driverLoginResult['user_id']);
    $results['driver_listing'] = $driverListingResult;

    // Δοκιμή προβολής αγγελίας
    if ($driverListingResult['success']) {
        $viewListingResult = testViewListing($driverListingResult['listing_id']);
        $results['view_listing'] = $viewListingResult;

        // Δοκιμή επεξεργασίας αγγελίας
        $updateListingResult = testUpdateListing($driverListingResult['listing_id'], [
            'title' => 'Αναζήτηση εργασίας ως οδηγός (ενημερωμένο)',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        $results['update_listing'] = $updateListingResult;

        // Δοκιμή διαγραφής αγγελίας
        $deleteListingResult = testDeleteListing($driverListingResult['listing_id']);
        $results['delete_listing'] = $deleteListingResult;
    }
}

// Δοκιμή δημιουργίας αγγελίας από εταιρεία
if ($companyLoginResult['success']) {
    $companyListingResult = testCreateCompanyListing($companyLoginResult['user_id']);
    $results['company_listing'] = $companyListingResult;
}

// Δοκιμή αναζήτησης αγγελιών
$searchListingsResult = testSearchListings();
$results['search_listings'] = $searchListingsResult;

// Εμφάνιση των αποτελεσμάτων
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4>Δοκιμή Συστήματος Αγγελιών</h4>
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

                    <?php if (isset($results['driver_listing'])) : ?>
                        <h6>Δημιουργία Αγγελίας από Οδηγό</h6>
                        <div class="alert <?php echo $results['driver_listing']['success'] ? 'alert-success' : 'alert-danger'; ?>">
                            <?php if ($results['driver_listing']['success']) : ?>
                                <p>Επιτυχής δημιουργία αγγελίας από οδηγό: ID <?php echo $results['driver_listing']['listing_id']; ?></p>
                            <?php else : ?>
                                <p>Αποτυχία δημιουργίας αγγελίας από οδηγό: <?php echo $results['driver_listing']['message']; ?></p>
                                <?php if (isset($results['driver_listing']['error'])) : ?>
                                    <pre><?php print_r($results['driver_listing']['error']); ?></pre>
                                <?php endif; ?>
                                <?php if (isset($results['driver_listing']['trace'])) : ?>
                                    <pre><?php echo $results['driver_listing']['trace']; ?></pre>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($results['view_listing'])) : ?>
                        <h6>Προβολή Αγγελίας</h6>
                        <div class="alert <?php echo $results['view_listing']['success'] ? 'alert-success' : 'alert-danger'; ?>">
                            <?php if ($results['view_listing']['success']) : ?>
                                <p>Επιτυχής προβολή αγγελίας</p>
                            <?php else : ?>
                                <p>Αποτυχία προβολής αγγελίας: <?php echo $results['view_listing']['message']; ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($results['update_listing'])) : ?>
                        <h6>Επεξεργασία Αγγελίας</h6>
                        <div class="alert <?php echo $results['update_listing']['success'] ? 'alert-success' : 'alert-danger'; ?>">
                            <?php if ($results['update_listing']['success']) : ?>
                                <p>Επιτυχής επεξεργασία αγγελίας</p>
                            <?php else : ?>
                                <p>Αποτυχία επεξεργασίας αγγελίας: <?php echo $results['update_listing']['message']; ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($results['delete_listing'])) : ?>
                        <h6>Διαγραφή Αγγελίας</h6>
                        <div class="alert <?php echo $results['delete_listing']['success'] ? 'alert-success' : 'alert-danger'; ?>">
                            <?php if ($results['delete_listing']['success']) : ?>
                                <p>Επιτυχής διαγραφή αγγελίας</p>
                            <?php else : ?>
                                <p>Αποτυχία διαγραφής αγγελίας: <?php echo $results['delete_listing']['message']; ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($results['company_listing'])) : ?>
                        <h6>Δημιουργία Αγγελίας από Εταιρεία</h6>
                        <div class="alert <?php echo $results['company_listing']['success'] ? 'alert-success' : 'alert-danger'; ?>">
                            <?php if ($results['company_listing']['success']) : ?>
                                <p>Επιτυχής δημιουργία αγγελίας από εταιρεία: ID <?php echo $results['company_listing']['listing_id']; ?></p>
                            <?php else : ?>
                                <p>Αποτυχία δημιουργίας αγγελίας από εταιρεία: <?php echo $results['company_listing']['message']; ?></p>
                                <?php if (isset($results['company_listing']['error'])) : ?>
                                    <pre><?php print_r($results['company_listing']['error']); ?></pre>
                                <?php endif; ?>
                                <?php if (isset($results['company_listing']['trace'])) : ?>
                                    <pre><?php echo $results['company_listing']['trace']; ?></pre>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <h6>Αναζήτηση Αγγελιών</h6>
                    <div class="alert <?php echo $searchListingsResult['success'] ? 'alert-success' : 'alert-danger'; ?>">
                        <?php if ($searchListingsResult['success']) : ?>
                            <p>Επιτυχής αναζήτηση αγγελιών: Βρέθηκαν <?php echo $searchListingsResult['count']; ?> αγγελίες</p>
                        <?php else : ?>
                            <p>Αποτυχία αναζήτησης αγγελιών: <?php echo $searchListingsResult['message']; ?></p>
                        <?php endif; ?>
                    </div>
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