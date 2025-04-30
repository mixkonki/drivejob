<?php
namespace Drivejob\Controllers;
use Drivejob\Models\DriversModel;
use Drivejob\Models\DriverAssessmentModel;
use Drivejob\Core\Validator;
use Drivejob\Core\CSRF;
use Drivejob\Core\AuthMiddleware;
use Drivejob\Core\Logger;
use Drivejob\Models\DriverLicenseModel;
use Drivejob\Models\JobListingModel;
use Drivejob\Models\MatchingModel;
use Drivejob\Core\Session;

class DriversController {
    private $driversModel;
    private $driverAssessmentModel;
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->driversModel = new DriversModel($pdo);
        // Θεωρητικά θα δημιουργήσουμε ένα μοντέλο για την αυτοαξιολόγηση του οδηγού
        // $this->driverAssessmentModel = new DriverAssessmentModel($pdo);
    }
    
    /**
     * Προβάλλει τη σελίδα προφίλ του οδηγού
     */
    public function profile() {
        
    // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
    AuthMiddleware::hasRole('driver');
    
    // Λήψη των στοιχείων του οδηγού
    $driverId = $_SESSION['user_id'];
    $driverData = $this->driversModel->getDriverById($driverId);
    
    // Λήψη των αδειών οδήγησης του οδηγού
    $driverLicenses = $this->driversModel->getDriverLicenses($driverId);
    $driverLicenseTypes = !empty($driverLicenses) ? array_column($driverLicenses, 'license_type') : [];

    // Λήψη των δεξιοτήτων του οδηγού
$driverSkills = $this->driversModel->getDriverSkills($driverId);

// Λήψη των πιστοποιήσεων του οδηγού
$driverCertifications = $this->driversModel->getDriverCertifications($driverId);
    // Λήψη της εμπειρίας σε οχήματα
    $driverVehicleExperience = $this->driversModel->getDriverVehicleExperience($driverId);

    // Έλεγχος για ΠΕΙ
    $hasPeiC = false;
    $hasPeiD = false;
    $peiCExpiryDate = null;
    $peiDExpiryDate = null;
    
    if (!empty($driverLicenses)) {
        foreach ($driverLicenses as $license) {
            if (!empty($license['has_pei']) && $license['has_pei'] == 1) {
                if (in_array($license['license_type'], ['C', 'CE', 'C1', 'C1E'])) {
                    $hasPeiC = true;
                    if (!empty($license['pei_expiry_c'])) {
                        $peiCExpiryDate = $license['pei_expiry_c'];
                    }
                } else if (in_array($license['license_type'], ['D', 'DE', 'D1', 'D1E'])) {
                    $hasPeiD = true;
                    if (!empty($license['pei_expiry_d'])) {
                        $peiDExpiryDate = $license['pei_expiry_d'];
                    }
                }
            }
        }
    }
    // Λήψη των δεδομένων του πιστοποιητικού ADR
    $driverADR = $this->driversModel->getDriverADRCertificate($driverId);
    if ($driverADR) {
        $driverData['adr_certificate'] = 1;
        $driverData['adr_certificate_number'] = $driverADR['certificate_number'];
        $driverData['adr_certificate_expiry'] = $driverADR['expiry_date'];
        $driverData['adr_certificate_type'] = $driverADR['adr_type'];
        
        // Ορισμός των κλάσεων ADR με βάση τον τύπο
        switch ($driverADR['adr_type']) {
            case 'Π1':
                $driverData['adr_classes'] = 'Βασική + Πρακτική';
                break;
            case 'Π2':
                $driverData['adr_classes'] = 'Βασική + Κλάση 1 (εκρηκτικά)';
                break;
            case 'Π3':
                $driverData['adr_classes'] = 'Βασική + Κλάση 7 (ραδιενεργά)';
                break;
            case 'Π4':
                $driverData['adr_classes'] = 'Βασική + Κλάση 1 + Κλάση 7';
                break;
            case 'Π5':
                $driverData['adr_classes'] = 'Βασική + Βυτία';
                break;
            case 'Π6':
                $driverData['adr_classes'] = 'Βασική + Βυτία + Κλάση 1';
                break;
            case 'Π7':
                $driverData['adr_classes'] = 'Βασική + Βυτία + Κλάση 7';
                break;
            case 'Π8':
                $driverData['adr_classes'] = 'Βασική + Βυτία + Κλάση 1 + Κλάση 7';
                break;
            default:
                $driverData['adr_classes'] = $driverADR['adr_type'];
        }
    } else {
        $driverData['adr_certificate'] = 0;
        $driverData['adr_certificate_number'] = null;
        $driverData['adr_certificate_expiry'] = null;
        $driverData['adr_certificate_type'] = null;
        $driverData['adr_classes'] = null;
    }
    
    // Λήψη των δεδομένων κάρτας ταχογράφου
    $tachographCard = $this->driversModel->getDriverTachographCard($driverId);
    if ($tachographCard) {
        $driverData['tachograph_card'] = 1;
        $driverData['tachograph_card_number'] = $tachographCard['card_number'];
        $driverData['tachograph_card_expiry'] = $tachographCard['expiry_date'];
    } else {
        $driverData['tachograph_card'] = 0;
        $driverData['tachograph_card_number'] = null;
        $driverData['tachograph_card_expiry'] = null;
    }
    
    // Λήψη των ειδικών αδειών του οδηγού
$driverSpecialLicenses = $this->driversModel->getDriverSpecialLicenses($driverId);
   // Λήψη των δεδομένων άδειας χειριστή
$operatorLicense = $this->driversModel->getDriverOperatorLicense($driverId);
if ($operatorLicense) {
    $driverData['operator_license'] = 1;
    $driverData['operator_license_type'] = $operatorLicense['speciality'];
    $driverData['operator_license_expiry'] = $operatorLicense['expiry_date'];
    $driverData['operator_license_number'] = $operatorLicense['license_number'];
    
    // Λήψη των υποειδικοτήτων
    $operatorSubSpecialities = $this->driversModel->getDriverOperatorSubSpecialities($operatorLicense['id']);
    
    // Αφαίρεση διπλοτύπων και προσθήκη ονομάτων
    $uniqueSubSpecialities = [];
    $usedCodes = [];
    
    foreach ($operatorSubSpecialities as $subSpec) {
        // Έλεγχος για διπλότυπα με βάση τον κωδικό υποειδικότητας
        if (in_array($subSpec['sub_speciality'], $usedCodes)) {
            continue;
        }
        
        // Προσθήκη του ονόματος της υποειδικότητας
        $subSpec['name'] = $this->getSubSpecialityName($subSpec['sub_speciality']);
        
        // Προσθήκη στις μοναδικές υποειδικότητες
        $uniqueSubSpecialities[] = $subSpec;
        $usedCodes[] = $subSpec['sub_speciality'];
    }
    
    // Αντικατάσταση του αρχικού πίνακα με τον πίνακα χωρίς διπλότυπα
    $operatorSubSpecialities = $uniqueSubSpecialities;
} else {
    $driverData['operator_license'] = 0;
    $driverData['operator_license_type'] = null;
    $driverData['operator_license_expiry'] = null;
    $driverData['operator_license_number'] = null;
    $operatorSubSpecialities = [];
}
    
    // Λήψη των δεδομένων κάρτας ταχογράφου
    $tachographCard = $this->driversModel->getDriverTachographCard($driverId);
    if ($tachographCard) {
        $driverData['tachograph_card'] = 1;
        $driverData['tachograph_card_number'] = $tachographCard['card_number'];
        $driverData['tachograph_card_expiry'] = $tachographCard['expiry_date'];
    } else {
        $driverData['tachograph_card'] = 0;
        $driverData['tachograph_card_number'] = null;
        $driverData['tachograph_card_expiry'] = null;
    }
    
    // Λήψη των δεδομένων άδειας χειριστή
    $operatorLicense = $this->driversModel->getDriverOperatorLicense($driverId);
    if ($operatorLicense) {
        $driverData['operator_license'] = 1;
        $driverData['operator_license_type'] = $operatorLicense['speciality'];
        $driverData['operator_license_expiry'] = $operatorLicense['expiry_date'];
        $driverData['operator_license_number'] = $operatorLicense['license_number'];
    } else {
        $driverData['operator_license'] = 0;
        $driverData['operator_license_type'] = null;
        $driverData['operator_license_expiry'] = null;
        $driverData['operator_license_number'] = null;
    }
    
    // Λήψη των δεξιοτήτων του οδηγού
    $driverSkills = $this->getDriverSkills($driverId);
    
   // Λήψη των αγγελιών που έχει δημιουργήσει ο οδηγός
   $jobListingModel = new JobListingModel($this->pdo);
   $myListings = $jobListingModel->getDriverListings($driverId);
   
   // Λήψη των προτεινόμενων αγγελιών για τον οδηγό
   $matchingModel = new MatchingModel($this->pdo);
   $matchedListings = $matchingModel->findMatchingListingsForDriver($driverId);
    
    // Λήψη των συντεταγμένων της τοποθεσίας του οδηγού για τον χάρτη
    $driverLocation = null;
    if (!empty($driverData['address']) && !empty($driverData['city'])) {
        $address = urlencode($driverData['address'] . ', ' . $driverData['city'] . ', ' . $driverData['country']);
        $driverLocation = $this->getGeocodingData($address);
    }
    
    // Λήψη δεδομένων αυτοαξιολόγησης
    $driverAssessment = [
        'total_score' => 75,
        'driving_skills' => 80,
        'safety_compliance' => 70,
        'professionalism' => 85,
        'technical_knowledge' => 65
    ];
    // Προσθήκη στο αρχείο src/Controllers/DriversController.php
// Λειτουργία που θα προσθέσουμε στη μέθοδο profile()

// Στην υπάρχουσα μέθοδο profile() προσθέτουμε τον παρακάτω κώδικα μετά τις υπάρχουσες γραμμές:

// Λήψη της βαθμολογίας του οδηγού
$driverRating = $this->driversModel->getDriverRating($driverId);

// Αν δεν υπάρχει αποθηκευμένη βαθμολογία, χρησιμοποιούμε προσωρινά δεδομένα
if (!$driverRating) {
    $driverRating = [
        'total_score' => 75,        // Συνολική βαθμολογία σε κλίμακα 0-100
        'skills_score' => 19,       // Βαθμολογία προσόντων σε κλίμακα 0-25
        'safety_score' => 23,       // Βαθμολογία ασφάλειας σε κλίμακα 0-30
        'professionalism_score' => 18, // Βαθμολογία επαγγελματισμού σε κλίμακα 0-25
        'technical_score' => 15,    // Βαθμολογία τεχνικών δεξιοτήτων σε κλίμακα 0-20
        'last_updated' => date('Y-m-d H:i:s')  // Ημερομηνία τελευταίας ενημέρωσης
    ];
}

// Λήψη των πρόσφατων συμβάντων του οδηγού (τα 2 πιο πρόσφατα)
$recentIncidents = $this->driversModel->getDriverIncidents($driverId);

// Λήψη δεδομένων τηλεματικής (αν υπάρχουν)
$telemetryData = $this->driversModel->getDriverTelemetryData($driverId);

// Προαιρετικά: Αν θέλετε προσωρινά δεδομένα τηλεματικής για επίδειξη 
// (σχολιάστε το αν θέλετε να εμφανίζεται το "κατεβάστε την εφαρμογή")
/*
if (!$telemetryData) {
    $telemetryData = [
        'score' => 82,              // Βαθμολογία οδήγησης σε κλίμακα 0-100
        'avg_speed' => 65.7,        // Μέση ταχύτητα σε χλμ/ώρα
        'harsh_braking' => 3,       // Αριθμός απότομων φρεναρισμάτων
        'harsh_acceleration' => 5,  // Αριθμός απότομων επιταχύνσεων
        'total_distance' => 1250,   // Συνολική απόσταση σε χλμ
        'date_collected' => date('Y-m-d', strtotime('-7 days')) // Ημερομηνία συλλογής δεδομένων
    ];
}
*/
// Προσθήκη πριν τη φόρτωση του view
$driver = $driverData;

// Φόρτωση του view
include ROOT_DIR . '/src/Views/drivers/profile.php';
}
    
    /**
     * Προβάλλει τη φόρμα επεξεργασίας προφίλ
     */
    public function edit() {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');
        
        // Λήψη των στοιχείων του οδηγού
        $driverId = $_SESSION['user_id'];
        $driverData = $this->driversModel->getDriverById($driverId);
        
        // Λήψη των αδειών οδήγησης του οδηγού
        $driverLicenses = $this->driversModel->getDriverLicenses($driverId);
        $driverLicenseTypes = array_column($driverLicenses, 'license_type');
        $driverPEI = array_column(array_filter($driverLicenses, function($license) {
            return $license['has_pei'] == 1;
        }), 'license_type');
        
        // Λήψη του πιστοποιητικού ADR του οδηγού
        $driverADR = $this->driversModel->getDriverADRCertificate($driverId);
        
        // Λήψη της άδειας χειριστή μηχανημάτων του οδηγού
        $driverOperator = $this->driversModel->getDriverOperatorLicense($driverId);
        $driverOperatorSubSpecialities = [];
        
        if ($driverOperator) {
            $driverOperatorSubSpecialities = $this->driversModel->getDriverOperatorSubSpecialities($driverOperator['id']);
        }
        
        // Φόρτωση των ειδικών αδειών
        $driverSpecialLicenses = $this->driversModel->getDriverSpecialLicenses($driverId);
        
        // Φόρτωση δεδομένων ταχογράφου
        $driverTachograph = $this->driversModel->getDriverTachographCard($driverId);
        // Λήψη των δεξιοτήτων του οδηγού
$driverSkills = $this->driversModel->getDriverSkills($driverId);

// Λήψη των πιστοποιήσεων του οδηγού
$driverCertifications = $this->driversModel->getDriverCertifications($driverId);
    // Λήψη της εμπειρίας σε οχήματα
    $driverVehicleExperience = $this->driversModel->getDriverVehicleExperience($driverId);

// Λήψη των γλωσσικών ικανοτήτων (βρίσκονται ήδη στο $driverData, δεν χρειάζεται επιπλέον μέθοδος)
        
        // Προετοιμασία δεδομένων ΠΕΙ
        $peiCExpiryDate = null;
        $peiDExpiryDate = null;
        
        foreach ($driverLicenses as $license) {
            if (isset($license['has_pei']) && $license['has_pei'] == 1) {
                if (in_array($license['license_type'], ['C', 'CE', 'C1', 'C1E']) && !empty($license['pei_expiry_c'])) {
                    $peiCExpiryDate = $license['pei_expiry_c'];
                } else if (in_array($license['license_type'], ['D', 'DE', 'D1', 'D1E']) && !empty($license['pei_expiry_d'])) {
                    $peiDExpiryDate = $license['pei_expiry_d'];
                }
            }
        }
        
        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/edit_profile.php';
    }
    
    /**
     * Αποθηκεύει τις αλλαγές στο προφίλ
     */
    public function update() {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');
        
        // Έλεγχος για CSRF token
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            $_SESSION['error_message'] = 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
            header('Location: ' . BASE_URL . 'drivers/edit-profile');
            exit();
        }
        
        // Επικύρωση βασικών δεδομένων
        $validator = new Validator($_POST);
        $validator->required('first_name', 'Το όνομα είναι υποχρεωτικό.')
                 ->required('last_name', 'Το επώνυμο είναι υποχρεωτικό.')
                 ->required('phone', 'Το τηλέφωνο είναι υποχρεωτικό.')
                 ->pattern('phone', '/^[0-9+\s()-]{10,15}$/', 'Παρακαλώ εισάγετε ένα έγκυρο τηλέφωνο.');
        
        if (!$validator->isValid()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old_input'] = $_POST;
            header('Location: ' . BASE_URL . 'drivers/edit-profile');
            exit();
        }
        
        // Λήψη ID του συνδεδεμένου οδηγού
        $driverId = $_SESSION['user_id'];
        
        // Συλλογή των δεδομένων από τη φόρμα
        $data = $this->collectFormData();
        
        
     // Ενημέρωση του προφίλ
if ($this->driversModel->updateProfile($driverId, $data)) {
    // Διαχείριση αδειών οδήγησης
    $this->handleDrivingLicenses($driverId);
    
    // Διαχείριση μεταφόρτωσης εικόνων και αρχείων
    $this->handleFileUploads($driverId);
    
    // Διαχείριση ειδικών αδειών
    $this->handleSpecialLicenses($driverId);
    
    // Διαχείριση κάρτας ταχογράφου
    $this->handleTachographCard($driverId);
    
    // Διαχείριση πιστοποιητικού ADR
    $this->handleADRCertificate($driverId);
    
    // Διαχείριση άδειας χειριστή μηχανημάτων
    $this->handleOperatorLicense($driverId);
    
    // Διαχείριση δεξιοτήτων και πιστοποιήσεων 
    if (isset($_POST['skills']) || isset($_POST['languages']) || isset($_POST['certifications'])) {
        $this->updateSkills(false); // Το false σημαίνει να μην κάνει ανακατεύθυνση
    }
    // Διαχείριση εμπειρίας σε οχήματα
if (isset($_POST['vehicle_experience']) && is_array($_POST['vehicle_experience'])) {
    $this->driversModel->updateDriverVehicleExperience($driverId, $_POST['vehicle_experience']);
}

    $_SESSION['success_message'] = 'Το προφίλ σας ενημερώθηκε με επιτυχία.';
} else {
    $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την ενημέρωση του προφίλ σας. Παρακαλώ δοκιμάστε ξανά.';
}

header('Location: ' . BASE_URL . 'drivers/driver_profile');
exit();
    }
    
    /**
     * Συλλέγει τα δεδομένα της φόρμας σε ένα συγκεντρωτικό πίνακα
     */
    private function collectFormData() {
        return [
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'phone' => $_POST['phone'],
            'landline' => $_POST['landline'] ?? null,
            'birth_date' => $_POST['birth_date'] ?? null,
            'address' => $_POST['address'] ?? null,
            'house_number' => $_POST['house_number'] ?? null,
            'city' => $_POST['city'] ?? null,
            'country' => $_POST['country'] ?? null,
            'postal_code' => $_POST['postal_code'] ?? null,
            'about_me' => $_POST['about_me'] ?? null,
            'experience_years' => $_POST['experience_years'] ? intval($_POST['experience_years']) : null,
            'available_for_work' => isset($_POST['available_for_work']) ? 1 : 0,
            'preferred_job_type' => $_POST['preferred_job_type'] ?? null,
            'preferred_vehicle_type' => $_POST['preferred_vehicle_type'] ?? null,
            'preferred_location' => $_POST['preferred_location'] ?? null,
            'preferred_radius' => $_POST['preferred_radius'] ?? null,
            'salary_min' => $_POST['salary_min'] ?? null,
            'salary_max' => $_POST['salary_max'] ?? null,
            'salary_period' => $_POST['salary_period'] ?? null,
            'social_linkedin' => $_POST['social_linkedin'] ?? null,
            'social_facebook' => $_POST['social_facebook'] ?? null,
            'social_twitter' => $_POST['social_twitter'] ?? null,
            'social_instagram' => $_POST['social_instagram'] ?? null,
            'willing_to_relocate' => isset($_POST['willing_to_relocate']) ? 1 : 0,
            'willing_to_travel' => isset($_POST['willing_to_travel']) ? 1 : 0,
            'license_number' => $_POST['license_number'] ?? null,
            'license_document_expiry' => $_POST['license_document_expiry'] ?? null,
            'license_codes' => $_POST['license_codes'] ?? null,
            'marital_status' => $_POST['marital_status'] ?? null,
            'education_level' => $_POST['education_level'] ?? null,
            'military_service' => $_POST['military_service'] ?? null,
            'languages' => isset($_POST['languages']) ? implode(',', $_POST['languages']) : null,
            'language_notes' => $_POST['language_notes'] ?? null,
        ];
    }
    
    /**
     * Διαχειρίζεται τις άδειες οδήγησης
     */
    private function handleDrivingLicenses($driverId) {
        $this->driversModel->deleteDriverLicenses($driverId);
        
        if (isset($_POST['license_types']) && is_array($_POST['license_types'])) {
            $licenseNumber = $_POST['license_number'] ?? null;
            $licenseDocumentExpiry = $_POST['license_document_expiry'] ?? null;
            
            foreach ($_POST['license_types'] as $licenseType) {
                $hasPei = false;
                $peiExpiryC = null;
                $peiExpiryD = null;
                
                // Έλεγχος για ΠΕΙ στις κατηγορίες C και D (και υποκατηγορίες)
                if (in_array($licenseType, ['C', 'CE', 'C1', 'C1E'])) {
                    // Έλεγχος για το αντίστοιχο checkbox ΠΕΙ
                    $peiCheckboxName = 'has_pei_' . strtolower($licenseType);
                    if (isset($_POST[$peiCheckboxName])) {
                        $hasPei = true;
                        $peiExpiryC = !empty($_POST['pei_c_expiry']) ? $_POST['pei_c_expiry'] : null;
                    }
                } else if (in_array($licenseType, ['D', 'DE', 'D1', 'D1E'])) {
                    // Έλεγχος για το αντίστοιχο checkbox ΠΕΙ
                    $peiCheckboxName = 'has_pei_' . strtolower($licenseType);
                    if (isset($_POST[$peiCheckboxName])) {
                        $hasPei = true;
                        $peiExpiryD = !empty($_POST['pei_d_expiry']) ? $_POST['pei_d_expiry'] : null;
                    }
                }
                
                // Λήψη της ημερομηνίας λήξης για τη συγκεκριμένη κατηγορία
                $expiryDate = $_POST['license_expiry'][$licenseType] ?? null;
                
                $this->driversModel->addDriverLicense($driverId, $licenseType, $hasPei, $expiryDate, 
                                                      $licenseNumber, $peiExpiryC, $peiExpiryD, $licenseDocumentExpiry);
            }
        }
    }
    
    /**
     * Διαχειρίζεται τις μεταφορτώσεις αρχείων (εικόνες, βιογραφικό)
     */
    private function handleFileUploads($driverId) {
        // Μεταφορτώσεις εικόνων
        $imageFields = [
            'license_front_image',
            'license_back_image',
            'profile_image',
            'adr_front_image',
            'adr_back_image',
            'operator_front_image',
            'operator_back_image',
            'tachograph_front_image',
            'tachograph_back_image'
        ];
        
        foreach ($imageFields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                if (in_array($field, ['license_front_image', 'license_back_image'])) {
                    $this->handleLicenseImageUpload($driverId, $field);
                } else if ($field === 'profile_image') {
                    $this->handleProfileImageUpload($driverId);
                } else {
                    $uploadDir = $this->getUploadDirectory($field);
                    $this->handleDocumentImageUpload($driverId, $field, $uploadDir, $field);
                }
            }
        }
        
        // Μεταφόρτωση βιογραφικού
        if (isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] === UPLOAD_ERR_OK) {
            $this->handleResumeFileUpload($driverId);
        }
    }
    
    /**
     * Επιστρέφει τον κατάλογο μεταφόρτωσης για κάθε τύπο αρχείου
     */
    private function getUploadDirectory($fieldName) {
        $directories = [
            'adr_front_image' => 'uploads/adr_images/',
            'adr_back_image' => 'uploads/adr_images/',
            'operator_front_image' => 'uploads/operator_images/',
            'operator_back_image' => 'uploads/operator_images/',
            'tachograph_front_image' => 'uploads/tachograph_images/',
            'tachograph_back_image' => 'uploads/tachograph_images/',
            'license_front_image' => 'uploads/license_images/',
            'license_back_image' => 'uploads/license_images/',
            'profile_image' => 'uploads/profile_images/',
            'resume_file' => 'uploads/resumes/'
        ];
        
        return $directories[$fieldName] ?? 'uploads/';
    }
    
    /**
     * Διαχειρίζεται τη μεταφόρτωση εικόνας άδειας οδήγησης
     */
    private function handleLicenseImageUpload($driverId, $fieldName) {
        $uploadPath = 'uploads/license_images/';
        $this->handleDocumentImageUpload($driverId, $fieldName, $uploadPath, $fieldName);
    }
    
    /**
     * Διαχειρίζεται τη μεταφόρτωση εικόνας προφίλ
     */
    private function handleProfileImageUpload($driverId) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        $file = $_FILES['profile_image'];
        
        // Έλεγχος τύπου αρχείου
        if (!in_array($file['type'], $allowedTypes)) {
            $_SESSION['error_message'] = 'Μη αποδεκτός τύπος αρχείου. Επιτρέπονται μόνο JPEG, PNG και GIF.';
            return false;
        }
        
        // Έλεγχος μεγέθους αρχείου
        if ($file['size'] > $maxSize) {
            $_SESSION['error_message'] = 'Το αρχείο είναι πολύ μεγάλο. Μέγιστο μέγεθος: 2MB.';
            return false;
        }
        
        // Δημιουργία του καταλόγου αν δεν υπάρχει
        $uploadDir = ROOT_DIR . '/public/uploads/profile_images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Δημιουργία μοναδικού ονόματος αρχείου
        $filename = $driverId . '_profile_' . time() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $filename;
        
        // Μεταφορά του αρχείου
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Ενημέρωση του πεδίου στη βάση δεδομένων
            $relativePath = 'uploads/profile_images/' . $filename;
            $this->driversModel->updateProfileImage($driverId, $relativePath);
            return true;
        }
        
        $_SESSION['error_message'] = 'Σφάλμα κατά τη μεταφόρτωση της εικόνας. Παρακαλώ δοκιμάστε ξανά.';
        return false;
    }
    
    /**
     * Διαχειρίζεται τη μεταφόρτωση βιογραφικού
     */
    private function handleResumeFileUpload($driverId) {
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        $file = $_FILES['resume_file'];
        
        // Έλεγχος τύπου αρχείου
        if (!in_array($file['type'], $allowedTypes)) {
            $_SESSION['error_message'] = 'Μη αποδεκτός τύπος αρχείου. Επιτρέπονται μόνο PDF, DOC και DOCX.';
            return false;
        }
        
        // Έλεγχος μεγέθους αρχείου
        if ($file['size'] > $maxSize) {
            $_SESSION['error_message'] = 'Το αρχείο είναι πολύ μεγάλο. Μέγιστο μέγεθος: 5MB.';
            return false;
        }
        
        // Δημιουργία του καταλόγου αν δεν υπάρχει
        $uploadDir = ROOT_DIR . '/public/uploads/resumes/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Δημιουργία μοναδικού ονόματος αρχείου
        $filename = $driverId . '_resume_' . time() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $filename;
        
        // Μεταφορά του αρχείου
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Ενημέρωση του πεδίου στη βάση δεδομένων
            $relativePath = 'uploads/resumes/' . $filename;
            $this->driversModel->updateResumeFile($driverId, $relativePath);
            return true;
        }
        
        $_SESSION['error_message'] = 'Σφάλμα κατά τη μεταφόρτωση του βιογραφικού. Παρακαλώ δοκιμάστε ξανά.';
        return false;
    }
    
    /**
     * Διαχειρίζεται τη μεταφόρτωση εικόνων διπλώματος, ADR, ταχογράφου, κλπ.
     */
    private function handleDocumentImageUpload($driverId, $fieldName, $uploadPath, $documentType) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        $file = $_FILES[$fieldName];
        
        // Έλεγχος τύπου αρχείου
        if (!in_array($file['type'], $allowedTypes)) {
            $_SESSION['error_message'] = 'Μη αποδεκτός τύπος αρχείου. Επιτρέπονται μόνο JPEG, PNG και GIF.';
            return false;
        }
        
        // Έλεγχος μεγέθους αρχείου
        if ($file['size'] > $maxSize) {
            $_SESSION['error_message'] = 'Το αρχείο είναι πολύ μεγάλο. Μέγιστο μέγεθος: 2MB.';
            return false;
        }
        
        // Δημιουργία του καταλόγου αν δεν υπάρχει
        $uploadDir = ROOT_DIR . '/public/' . $uploadPath;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Δημιουργία μοναδικού ονόματος αρχείου
        $filename = $driverId . '_' . $documentType . '_' . time() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $filename;
        
        // Μεταφορά του αρχείου
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Επιστροφή του σχετικού μονοπατιού
            $relativePath = $uploadPath . $filename;
            
            // Ενημέρωση του πεδίου στον πίνακα drivers
            $this->driversModel->updateDriverDocumentImage($driverId, $documentType, $relativePath);
            
            return $relativePath;
        }
        
        $_SESSION['error_message'] = 'Σφάλμα κατά τη μεταφόρτωση της εικόνας. Παρακαλώ δοκιμάστε ξανά.';
        return false;
    }
    
    /**
     * Διαχειρίζεται τις ειδικές άδειες
     */
    private function handleSpecialLicenses($driverId) {
        // Διαγραφή των υπαρχουσών ειδικών αδειών
        $this->driversModel->deleteDriverSpecialLicenses($driverId);
        
        // Αν έχουν υποβληθεί ειδικές άδειες, τις προσθέτουμε στη βάση
        if (isset($_POST['special_license_type']) && is_array($_POST['special_license_type'])) {
            foreach ($_POST['special_license_type'] as $index => $type) {
                // Αν ο τύπος άδειας δεν είναι κενός, προσθέτουμε την άδεια
                if (!empty(trim($type))) {
                    $licenseNumber = $_POST['special_license_number'][$index] ?? '';
                    $expiryDate = $_POST['special_license_expiry'][$index] ?? null;
                    $details = $_POST['special_license_details'][$index] ?? '';
                    
                    $this->driversModel->addDriverSpecialLicense($driverId, $type, $licenseNumber, $expiryDate, $details);
                }
            }
        }
    }
    
    /**
     * Διαχειρίζεται το πιστοποιητικό ADR
     */
    private function handleADRCertificate($driverId) {
        if (isset($_POST['adr_certificate']) && $_POST['adr_certificate'] == 1) {
            $adrData = [
                'adr_type' => $_POST['adr_certificate_type'] ?? null,
                'certificate_number' => $_POST['adr_certificate_number'] ?? null,
                'expiry_date' => $_POST['adr_certificate_expiry'] ?? null
            ];
            
            $this->driversModel->updateDriverADRCertificate($driverId, $adrData);
        } else {
            // Αν δεν έχει επιλεγεί το ADR, διαγράφουμε τα στοιχεία
            $this->driversModel->deleteDriverADRCertificate($driverId);
        }
    }
    
    /**
     * Διαχειρίζεται την κάρτα ταχογράφου
     */
    private function handleTachographCard($driverId) {
        if (isset($_POST['tachograph_card']) && $_POST['tachograph_card'] == 1) {
            $tachographData = [
                'card_number' => $_POST['tachograph_card_number'] ?? null,
                'expiry_date' => $_POST['tachograph_card_expiry'] ?? null
            ];
            
            $this->driversModel->updateDriverTachographCard($driverId, $tachographData);
        } else {
            // Αν δεν έχει επιλεγεί η κάρτα ταχογράφου, διαγράφουμε τα στοιχεία
            $this->driversModel->deleteDriverTachographCard($driverId);
        }
    }
    
    /**
     * Διαχειρίζεται την άδεια χειριστή μηχανημάτων
     */
    private function handleOperatorLicense($driverId) {
        Logger::init();
        
        if (isset($_POST['operator_license']) && $_POST['operator_license'] == 1) {
            // Δημιουργία του πίνακα δεδομένων
            $operatorData = [
                'speciality' => $_POST['operator_speciality'] ?? null,
                'license_number' => $_POST['operator_license_number'] ?? null,
                'expiry_date' => $_POST['operator_license_expiry'] ?? null
            ];
            
            // Ενημέρωση ή προσθήκη της άδειας χειριστή
            $operatorLicenseId = $this->driversModel->updateDriverOperatorLicense($driverId, $operatorData);
            
            if ($operatorLicenseId) {
                // Διαχείριση υποειδικοτήτων
                $this->handleOperatorSubSpecialities($operatorLicenseId);
            }
        } else {
            // Αν δεν έχει επιλεγεί η άδεια χειριστή, διαγράφουμε τα στοιχεία
            $this->driversModel->deleteDriverOperatorLicense($driverId);
        }
    }
    
    /**
     * Διαχειρίζεται τις υποειδικότητες της άδειας χειριστή
     */
    private function handleOperatorSubSpecialities($operatorLicenseId) {
        // Διαγραφή υπαρχουσών υποειδικοτήτων
        $this->driversModel->deleteDriverOperatorSubSpecialities($operatorLicenseId);
        
        // Λήψη των επιλεγμένων υποειδικοτήτων και ομάδων από JSON
        $selectedSubSpecialities = [];
        $selectedGroups = [];
        
        // Λήψη από το πεδίο JSON των υποειδικοτήτων
        if (isset($_POST['all_selected_subspecialities']) && !empty($_POST['all_selected_subspecialities'])) {
            try {
                $selectedSubSpecialities = json_decode($_POST['all_selected_subspecialities'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("Σφάλμα JSON υποειδικοτήτων: " . json_last_error_msg());
                }
            } catch (\Exception $e) {
                Logger::error("Σφάλμα αποκωδικοποίησης JSON υποειδικοτήτων: " . $e->getMessage(), "OperatorLicense");
                $selectedSubSpecialities = [];
            }
        }
        
        // Λήψη από το πεδίο JSON των ομάδων
        if (isset($_POST['all_selected_groups']) && !empty($_POST['all_selected_groups'])) {
            try {
                $selectedGroups = json_decode($_POST['all_selected_groups'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("Σφάλμα JSON ομάδων: " . json_last_error_msg());
                }
            } catch (\Exception $e) {
                Logger::error("Σφάλμα αποκωδικοποίησης JSON ομάδων: " . $e->getMessage(), "OperatorLicense");
                $selectedGroups = [];
            }
        }
        
        // Εναλλακτική μέθοδος λήψης αν η JSON μέθοδος αποτύχει
        if (empty($selectedSubSpecialities) && isset($_POST['operator_sub_specialities'])) {
            $selectedSubSpecialities = is_array($_POST['operator_sub_specialities']) 
                ? $_POST['operator_sub_specialities'] 
                : [$_POST['operator_sub_specialities']];
        }
        
        // Προσθήκη των επιλεγμένων υποειδικοτήτων
        if (!empty($selectedSubSpecialities)) {
            foreach ($selectedSubSpecialities as $subSpeciality) {
                // Καθορισμός της ομάδας (A ή B)
                $groupType = 'A'; // Προεπιλογή
                
                // Από το JSON αντικείμενο ομάδων
                if (isset($selectedGroups[$subSpeciality])) {
                    $groupType = $selectedGroups[$subSpeciality];
                } 
                // Από τα άμεσα πεδία της φόρμας
                else if (isset($_POST["group_{$subSpeciality}"])) {
                    $groupType = $_POST["group_{$subSpeciality}"];
                }
                
                // Προσθήκη της υποειδικότητας με την ομάδα της
                $this->driversModel->addDriverOperatorSubSpeciality($operatorLicenseId, $subSpeciality, $groupType);
            }
        }
    }
    
    /**
     * Λαμβάνει τις συντεταγμένες από μια διεύθυνση μέσω της υπηρεσίας Geocoding
     * 
     * @param string $address Η διεύθυνση προς γεωκωδικοποίηση
     * @return array|null Συντεταγμένες [lat, lng] ή null σε περίπτωση σφάλματος
     */
    private function getGeocodingData($address) {
        try {
            $apiKey = 'YOUR_GOOGLE_MAPS_API_KEY'; // Αντικαταστήστε με το δικό σας API κλειδί
            $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$apiKey}";
            
            // Ορίστε ένα ρητό χρονικό όριο 5 δευτερολέπτων
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                return null;
            }
            
            $data = json_decode($response, true);
            
            if (isset($data['status']) && $data['status'] === 'OK' && !empty($data['results'][0]['geometry']['location'])) {
                return [
                    'lat' => $data['results'][0]['geometry']['location']['lat'],
                    'lng' => $data['results'][0]['geometry']['location']['lng']
                ];
            }
        } catch (\Exception $e) {
            Logger::error('Σφάλμα κατά τη λήψη δεδομένων geocoding: ' . $e->getMessage());
        }
        
        return null;
    }
    /**
 * Εναλλαγή διαθεσιμότητας οδηγού για εργασία
 */
/**
 * Εναλλαγή διαθεσιμότητας οδηγού για εργασία
 */
public function toggleAvailability() {
    // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
    AuthMiddleware::hasRole('driver');
    
    // Έλεγχος για CSRF token
    if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Άκυρο αίτημα.']);
        exit();
    }
    
    try {
        // Λήψη του τρέχοντος οδηγού
        $driverId = $_SESSION['user_id'];
        $driver = $this->driversModel->getDriverById($driverId);
        
        if (!$driver) {
            echo json_encode(['success' => false, 'message' => 'Δεν βρέθηκε ο οδηγός.']);
            exit();
        }
        
        // Αλλαγή της κατάστασης διαθεσιμότητας
        $currentStatus = isset($driver['available_for_work']) ? (int)$driver['available_for_work'] : 0;
        $newStatus = $currentStatus ? 0 : 1;
        
        // Καταγραφή για εντοπισμό σφαλμάτων
        Logger::info("Εναλλαγή διαθεσιμότητας για οδηγό $driverId από $currentStatus σε $newStatus", "ToggleAvailability");
        
        $success = $this->driversModel->updateProfile($driverId, ['available_for_work' => $newStatus]);
        
        if ($success) {
            Logger::info("Επιτυχής ενημέρωση διαθεσιμότητας για οδηγό $driverId", "ToggleAvailability");
            echo json_encode([
                'success' => true, 
                'message' => 'Η διαθεσιμότητα ενημερώθηκε με επιτυχία',
                'newStatus' => $newStatus,
                'statusText' => $newStatus ? 'Διαθέσιμος/η για εργασία' : 'Μη διαθέσιμος/η για εργασία'
            ]);
        } else {
            Logger::error("Αποτυχία ενημέρωσης διαθεσιμότητας για οδηγό $driverId", "ToggleAvailability");
            echo json_encode(['success' => false, 'message' => 'Αποτυχία ενημέρωσης διαθεσιμότητας']);
        }
    } catch (Exception $e) {
        Logger::error("Σφάλμα κατά την εναλλαγή διαθεσιμότητας: " . $e->getMessage(), "ToggleAvailability");
        echo json_encode(['success' => false, 'message' => 'Σφάλμα επεξεργασίας αιτήματος']);
    }
    
    exit();
}
// Προσθήκη στο αρχείο src/Controllers/DriversController.php

/**
 * Επιστρέφει τα στοιχεία ενός οδηγού
 * 
 * @param int $driverId ID του οδηγού
 * @return array|false Στοιχεία οδηγού ή false αν δεν βρέθηκε
 */
public function getDriverById($driverId) {
    return $this->driversModel->getDriverById($driverId);
}

/**
 * Επιστρέφει τις δεξιότητες ενός οδηγού
 * 
 * @param int $driverId ID του οδηγού
 * @return array Στοιχεία δεξιοτήτων οδηγού
 */
public function getDriverSkills($driverId) {
    return $this->driversModel->getDriverSkills($driverId);
}

/**
 * Επιστρέφει τις γλωσσικές ικανότητες ενός οδηγού
 * 
 * @param int $driverId ID του οδηγού
 * @return array Στοιχεία γλωσσικών ικανοτήτων
 */
public function getDriverLanguages($driverId) {
    $driverData = $this->driversModel->getDriverById($driverId);
    
    $languages = [];
    
    // Εξαγωγή των γλωσσικών ικανοτήτων από τα δεδομένα του οδηγού
    if ($driverData) {
        $languageFields = [
            'greek' => 'language_greek',
            'english' => 'language_english',
            'german' => 'language_german',
            'french' => 'language_french',
            'italian' => 'language_italian',
            'other' => [
                'name' => 'language_other_name',
                'level' => 'language_other_level'
            ]
        ];
        
        foreach ($languageFields as $language => $field) {
            if (is_array($field)) {
                if (isset($driverData[$field['name']]) && !empty($driverData[$field['name']])) {
                    $languages[$language] = [
                        'name' => $driverData[$field['name']],
                        'level' => $driverData[$field['level']] ?? ''
                    ];
                }
            } else {
                if (isset($driverData[$field]) && !empty($driverData[$field])) {
                    $languages[$language] = $driverData[$field];
                }
            }
        }
    }
    
    return $languages;
}

/**
 * Επιστρέφει τις πιστοποιήσεις και τα σεμινάρια ενός οδηγού
 * 
 * @param int $driverId ID του οδηγού
 * @return array Λίστα πιστοποιήσεων και σεμιναρίων
 */
public function getDriverCertifications($driverId) {
    return $this->driversModel->getDriverCertifications($driverId);
}

/**
 * Επιστρέφει τα δεδομένα αυτοαξιολόγησης ενός οδηγού
 * 
 * @param int $driverId ID του οδηγού
 * @return array Στοιχεία αυτοαξιολόγησης
 */
public function getDriverAssessment($driverId) {
    return $this->driversModel->getDriverAssessment($driverId);
}

/**
 * Διαχειρίζεται την επεξεργασία των δεξιοτήτων του οδηγού
 * 
 * @return bool Επιτυχία/αποτυχία
 */
public function updateDriverSkills() {
    // Έλεγχος για CSRF token
    if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
        return false;
    }
    
    // Λήψη ID του συνδεδεμένου οδηγού
    $driverId = $_SESSION['user_id'];
    
    // Επεξεργασία των δεξιοτήτων
    $skills = isset($_POST['skills']) ? $_POST['skills'] : [];
    
    // Μορφοποίηση των δεξιοτήτων για αποθήκευση
    // Μετατροπή των επιλεγμένων τιμών σε 1, και των μη επιλεγμένων σε 0
    $skillsData = [
        // Οδηγικές Ικανότητες
        'defensive_driving' => isset($skills['defensive_driving']) ? 1 : 0,
        'eco_driving' => isset($skills['eco_driving']) ? 1 : 0,
        'night_driving' => isset($skills['night_driving']) ? 1 : 0,
        'mountain_driving' => isset($skills['mountain_driving']) ? 1 : 0,
        'extreme_conditions' => isset($skills['extreme_conditions']) ? 1 : 0,
        
        // Ασφάλεια & Συμμόρφωση
        'loading_securing' => isset($skills['loading_securing']) ? 1 : 0,
        'emergency_response' => isset($skills['emergency_response']) ? 1 : 0,
        'first_aid' => isset($skills['first_aid']) ? 1 : 0,
        'dangerous_goods' => isset($skills['dangerous_goods']) ? 1 : 0,
        'tacograph_compliance' => isset($skills['tacograph_compliance']) ? 1 : 0,
        
        // Επαγγελματισμός
        'customer_service' => isset($skills['customer_service']) ? 1 : 0,
        'time_management' => isset($skills['time_management']) ? 1 : 0,
        'route_planning' => isset($skills['route_planning']) ? 1 : 0,
        'conflict_resolution' => isset($skills['conflict_resolution']) ? 1 : 0,
        'multilingual' => isset($skills['multilingual']) ? 1 : 0,
        
        // Τεχνικές Γνώσεις
        'vehicle_maintenance' => isset($skills['vehicle_maintenance']) ? 1 : 0,
        'troubleshooting' => isset($skills['troubleshooting']) ? 1 : 0,
        'digital_tachograph' => isset($skills['digital_tachograph']) ? 1 : 0,
        'gps_systems' => isset($skills['gps_systems']) ? 1 : 0,
        'logistics_software' => isset($skills['logistics_software']) ? 1 : 0
    ];
    
    // Επεξεργασία των γλωσσικών ικανοτήτων
    $languages = isset($_POST['languages']) ? $_POST['languages'] : [];
    
    // Δημιουργία των δεδομένων του οδηγού για τις γλωσσικές ικανότητες
    $languageData = [
        'language_greek' => $languages['greek'] ?? 'native',
        'language_english' => $languages['english'] ?? '',
        'language_german' => $languages['german'] ?? '',
        'language_french' => $languages['french'] ?? '',
        'language_italian' => $languages['italian'] ?? '',
        'language_other_name' => $languages['other_name'] ?? '',
        'language_other_level' => $languages['other_level'] ?? ''
    ];
    
    // Επεξεργασία των επιπλέον δεξιοτήτων
    $additionalSkills = isset($_POST['additional_skills']) ? $_POST['additional_skills'] : '';
    
    // Αποθήκευση των επιπλέον δεξιοτήτων στον πίνακα οδηγών
    $driverData = ['additional_skills' => $additionalSkills];
    
    // Ενημέρωση των δεξιοτήτων
    $skillsResult = $this->driversModel->updateDriverSkills($driverId, $skillsData);
    
    // Ενημέρωση των γλωσσικών ικανοτήτων και των επιπλέον δεξιοτήτων
    $driverResult = $this->driversModel->updateProfile($driverId, array_merge($languageData, $driverData));
    
    // Επεξεργασία των πιστοποιήσεων
    $certifications = [];
    if (isset($_POST['certifications']) && is_array($_POST['certifications'])) {
        foreach ($_POST['certifications'] as $cert) {
            if (!empty($cert['title'])) {
                $certifications[] = [
                    'title' => $cert['title'],
                    'provider' => $cert['provider'] ?? '',
                    'date' => $cert['date'] ?? null,
                    'expiry' => $cert['expiry'] ?? null,
                    'description' => $cert['description'] ?? ''
                ];
            }
        }
    }
    
    // Ενημέρωση του πεδίου training_seminars
    $trainingData = ['training_seminars' => isset($_POST['training_seminars']) ? 1 : 0];
    
    // Αν υπάρχουν λεπτομέρειες για τα σεμινάρια, τις αποθηκεύουμε
    if (isset($_POST['training_details'])) {
        $trainingData['training_details'] = $_POST['training_details'];
    }
    
    // Ενημέρωση του οδηγού με τα σεμινάρια
    $trainingResult = $this->driversModel->updateProfile($driverId, $trainingData);
    
    // Αποθήκευση των πιστοποιήσεων
    $certResult = true;
    if (!empty($certifications)) {
        $certResult = $this->driversModel->addDriverCertifications($driverId, $certifications);
    }
    
    // Επιστροφή του συνολικού αποτελέσματος
    return $skillsResult && $driverResult && $trainingResult && $certResult;
}

/**
 * Διαχειρίζεται την ενημέρωση της αυτοαξιολόγησης του οδηγού
 * 
 * @return bool Επιτυχία/αποτυχία
 */
public function updateDriverAssessment() {
    // Έλεγχος για CSRF token
    if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
        return false;
    }
    
    // Λήψη ID του συνδεδεμένου οδηγού
    $driverId = $_SESSION['user_id'];
    
    // Επεξεργασία των δεδομένων αυτοαξιολόγησης
    $assessmentData = isset($_POST['assessment']) ? $_POST['assessment'] : [];
    
    // Ενημέρωση της αυτοαξιολόγησης
    return $this->driversModel->updateDriverAssessment($driverId, $assessmentData);
}

/**
 * Προβάλλει τη σελίδα επεξεργασίας δεξιοτήτων
 */
public function editSkills() {
    // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
    AuthMiddleware::hasRole('driver');
    
    // Φόρτωση του view
    include ROOT_DIR . '/public/drivers/edit-skills.php';
}

/**
 * Προβάλλει τη σελίδα αυτοαξιολόγησης
 */
public function selfAssessment() {
    // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
    AuthMiddleware::hasRole('driver');
    
    // Φόρτωση του view
    include ROOT_DIR . '/public/drivers/update-assessment.php';
}
/**
 * Χειρίζεται την ενημέρωση των προσόντων και πιστοποιήσεων
 * Προσθέστε αυτή τη μέθοδο στο DriversController.php
 */
public function updateSkills() {
    // Καταγραφή των δεδομένων που υποβάλλονται
error_log('POST data: ' . print_r($_POST, true));
    // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
    AuthMiddleware::hasRole('driver');
    
    // Έλεγχος για CSRF token
    if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
        header('Location: ' . BASE_URL . 'drivers/edit-profile');
        exit();
    }
    
    // Λήψη ID του συνδεδεμένου οδηγού
    $driverId = $_SESSION['user_id'];
    
    try {
        // 1. Ενημέρωση δεξιοτήτων οδηγού
        if (isset($_POST['skills'])) {
            $skillsData = [];
            $allSkillFields = [
                'defensive_driving', 'eco_driving', 'night_driving', 'mountain_driving', 'extreme_conditions',
                'loading_securing', 'emergency_response', 'first_aid', 'dangerous_goods', 'tacograph_compliance',
                'customer_service', 'time_management', 'route_planning', 'conflict_resolution', 'multilingual',
                'vehicle_maintenance', 'troubleshooting', 'digital_tachograph', 'gps_systems', 'logistics_software'
            ];
            
            // Αρχικοποίηση όλων των δεξιοτήτων σε 0
            foreach ($allSkillFields as $field) {
                $skillsData[$field] = 0;
            }
            
            // Ενημέρωση των επιλεγμένων δεξιοτήτων
            foreach ($_POST['skills'] as $skill => $value) {
                if (in_array($skill, $allSkillFields)) {
                    $skillsData[$skill] = 1;
                }
            }
            
            // Αποθήκευση των δεξιοτήτων
            $this->driversModel->updateDriverSkills($driverId, $skillsData);
        } else {
            // Αν δεν επιλέχθηκαν δεξιότητες, μηδενίζουμε όλες τις δεξιότητες
            $emptySkills = [];
            $allSkillFields = [
                'defensive_driving', 'eco_driving', 'night_driving', 'mountain_driving', 'extreme_conditions',
                'loading_securing', 'emergency_response', 'first_aid', 'dangerous_goods', 'tacograph_compliance',
                'customer_service', 'time_management', 'route_planning', 'conflict_resolution', 'multilingual',
                'vehicle_maintenance', 'troubleshooting', 'digital_tachograph', 'gps_systems', 'logistics_software'
            ];
            
            foreach ($allSkillFields as $field) {
                $emptySkills[$field] = 0;
            }
            
            // Αποθήκευση μηδενικών δεξιοτήτων
            $this->driversModel->updateDriverSkills($driverId, $emptySkills);
        }
        
        // 2. Ενημέρωση γλωσσικών ικανοτήτων
        if (isset($_POST['languages'])) {
            $languageData = [
                'language_greek' => $_POST['languages']['greek'] ?? '',
                'language_english' => $_POST['languages']['english'] ?? '',
                'language_german' => $_POST['languages']['german'] ?? '',
                'language_french' => $_POST['languages']['french'] ?? '',
                'language_italian' => $_POST['languages']['italian'] ?? '',
                'language_other_name' => $_POST['languages']['other_name'] ?? '',
                'language_other_level' => $_POST['languages']['other_level'] ?? ''
            ];
            
            // Ενημέρωση των γλωσσικών ικανοτήτων
            $this->driversModel->updateProfile($driverId, $languageData);
        }
        
        // 3. Ενημέρωση σεμιναρίων και πρόσθετων δεξιοτήτων
        $additionalData = [
            'training_seminars' => isset($_POST['training_seminars']) ? 1 : 0,
            'training_details' => $_POST['training_details'] ?? '',
            'additional_skills' => $_POST['additional_skills'] ?? ''
        ];
        
        // Ενημέρωση των πρόσθετων δεδομένων
        $this->driversModel->updateProfile($driverId, $additionalData);
        
        // 4. Ενημέρωση πιστοποιήσεων
        if (isset($_POST['certifications']) && is_array($_POST['certifications'])) {
            $certifications = [];
            
            foreach ($_POST['certifications'] as $cert) {
                if (!empty($cert['title'])) {
                    $certifications[] = [
                        'title' => $cert['title'],
                        'provider' => $cert['provider'] ?? '',
                        'date' => $cert['date'] ?? null,
                        'expiry' => $cert['expiry'] ?? null,
                        'description' => $cert['description'] ?? ''
                    ];
                }
            }
            
            // Αποθήκευση των πιστοποιήσεων
            $this->driversModel->addDriverCertifications($driverId, $certifications);
        } else {
            // Αν δεν υπάρχουν πιστοποιήσεις, διαγράφουμε τις υπάρχουσες
            $this->driversModel->deleteDriverCertifications($driverId);
        }
        
        $_SESSION['success_message'] = 'Τα προσόντα και οι πιστοποιήσεις σας ενημερώθηκαν με επιτυχία.';
    } catch (\Exception $e) {
        $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την ενημέρωση των προσόντων. Παρακαλώ δοκιμάστε ξανά.';
        error_log('Error in updateSkills: ' . $e->getMessage());
    }
    
    header('Location: ' . BASE_URL . 'drivers/driver_profile#qualifications');
    exit();
}
/**
 * Επιστρέφει το όνομα της υποειδικότητας με βάση τον κωδικό
 * 
 * @param string $code Κωδικός υποειδικότητας (π.χ. "1.1")
 * @return string Όνομα υποειδικότητας
 */
private function getSubSpecialityName($code) {
    $subSpecialityNames = [
        // Ειδικότητα 1
        '1.1' => 'Εκσκαφέας (τσάπα)',
        '1.2' => 'Τσάπα - φορτωτής',
        '1.3' => 'Τροχοφόρος εκσκαφέας (αποξέστης τάφρων)',
        '1.4' => 'Προωθητής γαιών παντός τύπου και συστήματος λειτουργίας (μπουλτόζα)',
        '1.5' => 'Φορτωτής',
        '1.6' => 'Γερανοφόρος εκσκαφέας',
        '1.7' => 'Ισοπεδωτής γαιών (γκρέιντερ)',
        '1.8' => 'Ερπυστριοφόρο ή τροχοφόρο όχημα με εξάρτηση υδραυλικής σφύρας',
        '1.9' => 'Ερπυστριοφόρο ή τροχοφόρο όχημα με εξάρτηση κάδου για ανύψωση και μεταφορά',
        
        // Ειδικότητα 2
        '2.1' => 'Αυτοκινούμενος γερανός παντός τύπου-συστήματος λειτουργίας & ανυψωτικής ικανότητας',
        '2.2' => 'Γερανός επί οχήματος (Κινητός γερανός)',
        '2.3' => 'Αναβατόριο/Καλαθοφόρο-Αυτοκινούμενα',
        '2.4' => 'Περονοφόρα',
        '2.5' => 'Βαρέλα σκυροδέματος επί αυτοκίνητου (πρέσα)',
        '2.6' => 'Μπετονιέρα επί αυτοκινήτου (αντλία σκυροδέματος)',
        '2.7' => 'Γερανός πύργος για υψηλά κτίρια',
        '2.8' => 'Γερανογέφυρες (είτε εποπτεύουν από ειδική γέφυρα είτε από χειριστήριο)',
        '2.9' => 'Γερανοί οικοδομικού (οικοδομικά βαρούλκα)',
        '2.10' => 'Ειδικό μηχάνημα ανύψωσης και μετακίνησης προκατασκευασμένων στοιχείων',
        '2.11' => 'Γερανάκι',
        
        // Ειδικότητα 3
        '3.1' => 'Διαστρωτήρας ασφαλτομίγματος (FINISHER)',
        '3.2' => 'Οδοστρωτήρας (δονητικός ή κρουστικός)',
        '3.3' => 'Ανακυκλωτής ασφάλτου',
        '3.4' => 'Διαγραμμιστικό',
        '3.5' => 'Σάρωθρο',
        
        // Ειδικότητα 4
        '4.1' => 'Πολυμηχάνημα (σάρωθρο, αποχιονιστικό, καταβρεκτήρας κλπ)',
        '4.2' => 'Εκχιονιστικό μηχάνημα',
        '4.3' => 'Αποφρακτικό',
        
        // Ειδικότητα 5
        '5.1' => 'Φορτωτής υπογείων έργων',
        '5.2' => 'Μηχανήματα διάνοιξης στοών',
        '5.3' => 'Μηχανή συνεχούς εξόρυξης',
        '5.4' => 'Διατρητικό φορείο (JUMBO)',
        
        // Ειδικότητα 6
        '6.1' => 'Πασσαλοπήκτης',
        '6.2' => 'Λιπαντής',
        '6.3' => 'Αντλία εκτόξευσης έτοιμου σκυροδέματος',
        '6.4' => 'Μηχάνημα κατασκευής φραγμάτων και επιχωμάτων', 
        
        // Ειδικότητα 7
        '7.1' => 'Μηχάνημα με εξάρτηση (πύργο, αεροσυμπιεστή, εξάρτηση διάτρησης)',
        '7.2' => 'Ασφαλτοκόπτης',
        '7.3' => 'Εναερίτης χειριστής μηχανημάτων',
        '7.4' => 'Μηχάνημα ειδικής κατασκευής για καθαρισμό οδών',
        '7.5' => 'Μηχάνημα κοπής ασφάλτου',
        
        // Ειδικότητα 8
        '8.1' => 'Ανυψωτική πλατφόρμα',
        '8.2' => 'Ανυψωτικό περονοφόρο μηχάνημα (κλαρκ)',
        '8.3' => 'Εξέδρα εργασίας',
        '8.4' => 'Κινητό ανυψωτικό με κεραία βραχίονα',
    ];
    
    return isset($subSpecialityNames[$code]) ? $subSpecialityNames[$code] : "Υποειδικότητα $code";
}
/**
 * Εμφανίζει το ιστορικό συμβάντων του οδηγού
 */
public function incidentHistory() {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    try {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        AuthMiddleware::hasRole('driver');
        
        // Λήψη των στοιχείων του οδηγού
        $driverId = $_SESSION['user_id'];
        $incidents = $this->driversModel->getDriverIncidents($driverId);
        
        // Φόρτωση του view
        include ROOT_DIR . '/src/Views/drivers/incident-history.php';
    } catch (Exception $e) {
        error_log('Error in incidentHistory: ' . $e->getMessage());
        echo '<div style="color: red; padding: 20px; border: 1px solid red;">';
        echo '<h2>Σφάλμα</h2>';
        echo '<p>' . $e->getMessage() . '</p>';
        echo '</div>';
    }
}

/**
 * Εμφανίζει τη φόρμα καταχώρησης συμβάντος
 */
public function reportIncident() {
    // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
    AuthMiddleware::hasRole('driver');
    
    // Φόρτωση του view
    include ROOT_DIR . '/src/Views/drivers/report-incident.php';
}

/**
 * Αποθηκεύει ένα νέο συμβάν
 */
public function saveIncident() {
    // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
    AuthMiddleware::hasRole('driver');
    
    // Έλεγχος για CSRF token
    if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
        header('Location: ' . BASE_URL . 'drivers/report-incident');
        exit();
    }
    
    // Επικύρωση δεδομένων
    $validator = new Validator($_POST);
    $validator->required('incident_type', 'Ο τύπος συμβάντος είναι υποχρεωτικός.')
             ->required('incident_date', 'Η ημερομηνία συμβάντος είναι υποχρεωτική.')
             ->required('severity', 'Η σοβαρότητα είναι υποχρεωτική.')
             ->required('description', 'Η περιγραφή είναι υποχρεωτική.');
    
    if (!$validator->isValid()) {
        $_SESSION['errors'] = $validator->getErrors();
        $_SESSION['old_input'] = $_POST;
        header('Location: ' . BASE_URL . 'drivers/report-incident');
        exit();
    }
    
    // Λήψη ID του συνδεδεμένου οδηγού
    $driverId = $_SESSION['user_id'];
    
    // Προετοιμασία δεδομένων
    $incidentData = [
        'incident_type' => $_POST['incident_type'],
        'incident_date' => $_POST['incident_date'],
        'severity' => intval($_POST['severity']),
        'description' => $_POST['description']
    ];
    
    // Αποθήκευση του συμβάντος
    if ($this->driversModel->saveDriverIncident($driverId, $incidentData)) {
        $_SESSION['success_message'] = 'Το συμβάν καταχωρήθηκε με επιτυχία.';
        
        // Ενημέρωση της συνολικής βαθμολογίας
        $this->driversModel->updateDriverRating($driverId);
        
        header('Location: ' . BASE_URL . 'drivers/incident-history');
        exit();
    } else {
        $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την καταχώρηση του συμβάντος.';
        header('Location: ' . BASE_URL . 'drivers/report-incident');
        exit();
    }
}

/**
 * Εμφανίζει τη σελίδα βαθμολογίας του οδηγού
 */
public function driverRating() {
    // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
    AuthMiddleware::hasRole('driver');
    
    // Λήψη των στοιχείων του οδηγού
    $driverId = $_SESSION['user_id'];
    $driverData = $this->driversModel->getDriverById($driverId);
    
    // Προσωρινά δεδομένα για επίδειξη - αντικαταστήστε με πραγματικά δεδομένα αργότερα
    $driverRating = [
        'total_score' => 75,        // Συνολική βαθμολογία σε κλίμακα 0-100
        'skills_score' => 19,       // Βαθμολογία προσόντων σε κλίμακα 0-25
        'safety_score' => 23,       // Βαθμολογία ασφάλειας σε κλίμακα 0-30
        'professionalism_score' => 18, // Βαθμολογία επαγγελματισμού σε κλίμακα 0-25
        'technical_score' => 15,    // Βαθμολογία τεχνικών δεξιοτήτων σε κλίμακα 0-20
        'last_updated' => date('Y-m-d H:i:s')  // Ημερομηνία τελευταίας ενημέρωσης
    ];
    
    // Προαιρετικά δεδομένα τηλεματικής
    // Σχολιάστε αυτό το τμήμα αν θέλετε να εμφανίσετε το μήνυμα "κατεβάστε την εφαρμογή"
    /*
    $telemetryData = [
        'score' => 82,              // Βαθμολογία οδήγησης σε κλίμακα 0-100
        'avg_speed' => 65.7,        // Μέση ταχύτητα σε χλμ/ώρα
        'harsh_braking' => 3,       // Αριθμός απότομων φρεναρισμάτων
        'harsh_acceleration' => 5,  // Αριθμός απότομων επιταχύνσεων
        'total_distance' => 1250,   // Συνολική απόσταση σε χλμ
        'date_collected' => date('Y-m-d', strtotime('-7 days')) // Ημερομηνία συλλογής δεδομένων
    ];
    */
    
    // Φόρτωση του view
    include ROOT_DIR . '/src/Views/drivers/driver-rating.php';
}

/**
 * Ανανεώνει τη βαθμολογία του οδηγού
 */
public function refreshRating() {
    // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
    AuthMiddleware::hasRole('driver');
    
    // Λήψη ID του συνδεδεμένου οδηγού
    $driverId = $_SESSION['user_id'];
    
    // Ενημέρωση της βαθμολογίας
    if ($this->driversModel->updateDriverRating($driverId)) {
        $_SESSION['success_message'] = 'Η βαθμολογία σας ενημερώθηκε με επιτυχία.';
    } else {
        $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την ενημέρωση της βαθμολογίας.';
    }
    
    header('Location: ' . BASE_URL . 'drivers/driver-rating');
    exit();
}
public function showDriverProfile($driverId) {
    // Έλεγχος αν υπάρχει ο οδηγός
    $driver = $this->driversModel->getDriverById($driverId);
    
    if (!$driver) {
        $_SESSION['error_message'] = 'Ο οδηγός δεν βρέθηκε.';
        header('Location: ' . BASE_URL);
        exit();
    }
    
    // Λήψη των αγγελιών του οδηγού
    $jobListingModel = new JobListingModel($this->pdo);
    $listings = $jobListingModel->getDriverListings($driverId, true, 1, 5); // Παίρνουμε μόνο τις 5 πρώτες ενεργές αγγελίες
    
    // Λήψη των δεξιοτήτων του οδηγού
    $driverSkillsModel = new DriverSkillsModel($this->pdo);
    $skills = $driverSkillsModel->getDriverSkills($driverId);
    
    // Λήψη των αδειών οδήγησης του οδηγού
    $driverLicensesModel = new DriverLicensesModel($this->pdo);
    $licenses = $driverLicensesModel->getDriverLicenses($driverId);
    
    // Λήψη του βαθμού αξιολόγησης του οδηγού
    $driverRatingsModel = new DriverRatingsModel($this->pdo);
    $rating = $driverRatingsModel->getDriverRating($driverId);
    
    // Φόρτωση του view
    include ROOT_DIR . '/src/Views/drivers/profile.php';
}
// Προσθέστε την παρακάτω μέθοδο στο αρχείο src/Controllers/DriversController.php
// μέσα στην κλάση DriversController

/**
 * Εμφανίζει το δημόσιο προφίλ ενός οδηγού
 * 
 * @param int $id Το ID του οδηγού
 */
/**
 * Εμφανίζει το δημόσιο προφίλ ενός οδηγού
 * 
 * @param int $id Το ID του οδηγού
 */
public function publicProfile($id) {
    // Έλεγχος αν το ID είναι έγκυρο
    if (!$id || !is_numeric($id)) {
        $_SESSION['error_message'] = 'Μη έγκυρο αναγνωριστικό οδηγού';
        header('Location: ' . BASE_URL . 'home');
        exit;
    }
    
    // Ανάκτηση των στοιχείων του οδηγού
    // Προσπαθούμε να βρούμε την κατάλληλη μέθοδο για την ανάκτηση οδηγού
    $driver = null;
    $methods = ['getById', 'getDriverById', 'get', 'findById', 'find', 'getByID', 'getDriver', 'getOne', 'findOne'];
    
    foreach ($methods as $method) {
        if (method_exists($this->driversModel, $method)) {
            try {
                $driver = $this->driversModel->$method($id);
                if ($driver) break;
            } catch (\Exception $e) {
                // Συνεχίζουμε με την επόμενη μέθοδο αν προκύψει σφάλμα
                continue;
            }
        }
    }
    
    // Εναλλακτικά, ας δοκιμάσουμε με απευθείας SQL ερώτημα ως τελευταία λύση
    if (!$driver) {
        try {
            $sql = "SELECT * FROM drivers WHERE id = :id LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            $driver = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // Σιωπηλά αγνοούμε το σφάλμα
        }
    }
    
    // Αν δεν βρέθηκε ο οδηγός
    if (!$driver) {
        $_SESSION['error_message'] = 'Ο οδηγός δεν βρέθηκε';
        header('Location: ' . BASE_URL . 'home');
        exit;
    }
    
    // Ανάκτηση πρόσθετων πληροφοριών για τον οδηγό
    $driverSkills = [];
    $driverLicenses = [];
    $driverLicenseTypes = [];
    
    // Δοκιμή για getDriverSkills
    if (method_exists($this->driversModel, 'getDriverSkills')) {
        try {
            $driverSkills = $this->driversModel->getDriverSkills($id);
        } catch (\Exception $e) {
            // Αγνοούμε το σφάλμα
        }
    }
    
    // Δοκιμή για getDriverLicenses
    if (method_exists($this->driversModel, 'getDriverLicenses')) {
        try {
            $driverLicenses = $this->driversModel->getDriverLicenses($id);
            
            if ($driverLicenses) {
                $driverLicenseTypes = array_map(function($license) {
                    return isset($license['license_type']) ? $license['license_type'] : '';
                }, $driverLicenses);
            }
        } catch (\Exception $e) {
            // Αγνοούμε το σφάλμα
        }
    }
    
    // Ανάκτηση αξιολογήσεων αν υπάρχει το μοντέλο αξιολόγησης
    $driverReviews = [];
    $averageRating = 0;
    
    if ($this->driverAssessmentModel) {
        if (method_exists($this->driverAssessmentModel, 'getDriverReviews')) {
            try {
                $driverReviews = $this->driverAssessmentModel->getDriverReviews($id);
            } catch (\Exception $e) {
                // Αγνοούμε το σφάλμα
            }
        }
        
        if (method_exists($this->driverAssessmentModel, 'getDriverAverageRating')) {
            try {
                $averageRating = $this->driverAssessmentModel->getDriverAverageRating($id);
            } catch (\Exception $e) {
                // Αγνοούμε το σφάλμα
            }
        }
    }
    
    // Ανάκτηση των αγγελιών του οδηγού
    $jobListingModel = new \Drivejob\Models\JobListingModel($this->pdo);
    $listings = [];
    
    if (method_exists($jobListingModel, 'getDriverListings')) {
        try {
            $listings = $jobListingModel->getDriverListings($id, true, 1, 5);
        } catch (\Exception $e) {
            // Αγνοούμε το σφάλμα
        }
    }
    
    // Απόδοση της σελίδας με τα δεδομένα
    // Ελέγχουμε αν υπάρχει μέθοδος render
    if (method_exists($this, 'render')) {
        $this->render('drivers/public-profile', [
            'driver' => $driver,
            'driverSkills' => $driverSkills,
            'driverLicenses' => $driverLicenses,
            'driverLicenseTypes' => $driverLicenseTypes,
            'driverReviews' => $driverReviews,
            'averageRating' => $averageRating,
            'listings' => $listings
        ]);
    } else {
        // Διαφορετικά χρησιμοποιούμε απευθείας include
        extract([
            'driver' => $driver,
            'driverSkills' => $driverSkills,
            'driverLicenses' => $driverLicenses,
            'driverLicenseTypes' => $driverLicenseTypes,
            'driverReviews' => $driverReviews,
            'averageRating' => $averageRating,
            'listings' => $listings
        ]);
        
        include ROOT_DIR . '/src/Views/drivers/public-profile.php';
    }
}
}