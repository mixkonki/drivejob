<?php

namespace Drivejob\Controllers;

use Drivejob\Models\Driver\ProfileModel;
use Drivejob\Models\Driver\LicenseModel;
use Drivejob\Models\Driver\CertificationModel;
use Drivejob\Models\Driver\SkillModel;
use Drivejob\Models\Driver\RatingModel;
use Drivejob\Utils\DriverResumePDF;

class DriverResumeController
{
    private $profileModel;
    private $licenseModel;
    private $certificationModel;
    private $skillModel;
    private $ratingModel;
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->profileModel = new ProfileModel($pdo);
        $this->licenseModel = new LicenseModel($pdo);
        $this->certificationModel = new CertificationModel($pdo);
        $this->skillModel = new SkillModel($pdo);
        $this->ratingModel = new RatingModel($pdo);
    }

    /**
     * Δημιουργεί και κατεβάζει το PDF βιογραφικό του οδηγού
     *
     * @param int $id Το ID του οδηγού
     */
    public function generateResume($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error_message'] = 'Πρέπει να συνδεθείτε για να έχετε πρόσβαση σε αυτή τη λειτουργία.';
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        // Έλεγχος αν ο συνδεδεμένος χρήστης είναι ο ίδιος ο οδηγός ή είναι εταιρεία
        if ($_SESSION['user_id'] != $id && $_SESSION['role'] !== 'company') {
            $_SESSION['error_message'] = 'Δεν έχετε δικαίωμα πρόσβασης σε αυτή τη λειτουργία.';
            header('Location: ' . BASE_URL . 'drivers/profile/' . $id);
            exit();
        }

        // Λήψη των δεδομένων του οδηγού
        $driver = $this->profileModel->getDriverById($id);
        if (!$driver) {
            $_SESSION['error_message'] = 'Ο οδηγός δεν βρέθηκε.';
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }

        // Λήψη επιπλέον δεδομένων για το βιογραφικό
        $driverLicenses = $this->licenseModel->getDriverLicenses($id);
        $driverLicenseTypes = [];
        if (!empty($driverLicenses)) {
            foreach ($driverLicenses as $license) {
                if (isset($license['license_type']) && !empty($license['license_type'])) {
                    $driverLicenseTypes[] = $license['license_type'];
                }
            }
        }

        $driverSkills = $this->skillModel->getDriverSkills($id);
        $driverSpecialLicenses = $this->certificationModel->getDriverSpecialLicenses($id);
        $driverAdrCertificates = $this->certificationModel->getDriverAdrCertificates($id);
        $driverOperatorLicenses = $this->certificationModel->getDriverOperatorLicenses($id);
        $driverTachographCard = $this->certificationModel->getDriverTachographCards($id);
        $averageRating = $this->ratingModel->getDriverRating($id);

        // Δημιουργία του PDF
        $options = [
            'driverLicenses' => $driverLicenses,
            'driverLicenseTypes' => $driverLicenseTypes,
            'driverSkills' => $driverSkills,
            'driverSpecialLicenses' => $driverSpecialLicenses,
            'driverAdrCertificates' => $driverAdrCertificates,
            'driverOperatorLicenses' => $driverOperatorLicenses,
            'driverTachographCard' => $driverTachographCard,
            'averageRating' => $averageRating
        ];

        $pdfGenerator = new DriverResumePDF($driver, $options);
        $pdfFile = $pdfGenerator->generate();

        // Ενημέρωση του βιογραφικού του οδηγού στη βάση δεδομένων αν έγινε κλήση από τον ίδιο τον οδηγό
        if ($_SESSION['user_id'] == $id && $_SESSION['role'] === 'driver') {
            $this->profileModel->updateResumeFile($id, $pdfFile);
        }

        // Ανακατεύθυνση στο αρχείο PDF
        header('Location: ' . BASE_URL . $pdfFile);
        exit();
    }

    /**
     * Αποθήκευση των αλλαγών στο βιογραφικό
     */
    public function updateResume()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error_message'] = 'Πρέπει να συνδεθείτε για να έχετε πρόσβαση σε αυτή τη λειτουργία.';
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        // Έλεγχος CSRF token
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error_message'] = 'Μη έγκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
            header('Location: ' . BASE_URL . 'drivers/edit-resume');
            exit();
        }

        // Έλεγχος αν ο χρήστης είναι οδηγός
        if ($_SESSION['role'] !== 'driver') {
            $_SESSION['error_message'] = 'Μόνο οι οδηγοί μπορούν να επεξεργαστούν το βιογραφικό τους.';
            header('Location: ' . BASE_URL . 'access-denied');
            exit();
        }

        $id = $_SESSION['user_id'];

        // Επικύρωση και καθαρισμός δεδομένων φόρμας
        $about_me = $_POST['about_me'] ?? '';
        $experience_years = intval($_POST['experience_years'] ?? 0);
        $work_experience = $_POST['work_experience'] ?? '';
        $language_greek = $_POST['language_greek'] ?? '';
        $language_english = $_POST['language_english'] ?? '';

        // Ενημέρωση δεδομένων βιογραφικού στη βάση
        $updateData = [
            'about_me' => $about_me,
            'experience_years' => $experience_years,
            'work_experience' => $work_experience,
            'language_greek' => $language_greek,
            'language_english' => $language_english
        ];

        $success = $this->profileModel->updateProfile($id, $updateData);

        if ($success) {
            $_SESSION['success_message'] = 'Οι αλλαγές στο βιογραφικό σας αποθηκεύτηκαν με επιτυχία.';
            // Δημιουργία νέου PDF βιογραφικού
            $this->generateResume($id);
        } else {
            $_SESSION['error_message'] = 'Υπήρξε ένα πρόβλημα κατά την αποθήκευση των αλλαγών.';
        }

        // Ανακατεύθυνση πίσω στο προφίλ
        header('Location: ' . BASE_URL . 'drivers/profile');
        exit();
    }

    /**
     * Φόρτωση της σελίδας επεξεργασίας βιογραφικού
     */
    public function editResume()
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error_message'] = 'Πρέπει να συνδεθείτε για να έχετε πρόσβαση σε αυτή τη λειτουργία.';
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        // Έλεγχος αν ο χρήστης είναι οδηγός
        if ($_SESSION['role'] !== 'driver') {
            $_SESSION['error_message'] = 'Μόνο οι οδηγοί μπορούν να επεξεργαστούν το βιογραφικό τους.';
            header('Location: ' . BASE_URL . 'access-denied');
            exit();
        }

        $id = $_SESSION['user_id'];

        // Λήψη των δεδομένων του οδηγού
        $driver = $this->profileModel->getDriverById($id);
        if (!$driver) {
            $_SESSION['error_message'] = 'Τα στοιχεία του οδηγού δεν βρέθηκαν.';
            header('Location: ' . BASE_URL . 'drivers/profile');
            exit();
        }

        // Λήψη επιπλέον δεδομένων για το βιογραφικό
        $driverLicenses = $this->licenseModel->getDriverLicenses($id);
        $driverLicenseTypes = [];
        if (!empty($driverLicenses)) {
            foreach ($driverLicenses as $license) {
                if (isset($license['license_type']) && !empty($license['license_type'])) {
                    $driverLicenseTypes[] = $license['license_type'];
                }
            }
        }

        $driverSkills = $this->skillModel->getDriverSkills($id);
        $driverSpecialLicenses = $this->certificationModel->getDriverSpecialLicenses($id);
        $driverAdrCertificates = $this->certificationModel->getDriverAdrCertificates($id);
        $driverOperatorLicenses = $this->certificationModel->getDriverOperatorLicenses($id);
        $driverTachographCard = $this->certificationModel->getDriverTachographCards($id);
        $averageRating = $this->ratingModel->getDriverRating($id);

        // Φόρτωση της προβολής επεξεργασίας βιογραφικού
        include ROOT_DIR . '/src/Views/drivers/edit-resume.php';
    }

    /**
     * Μέθοδος που καλείται από το URL /drivers/download-resume/{id}
     */
    public function downloadResume($id)
    {
        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error_message'] = 'Πρέπει να συνδεθείτε για να έχετε πρόσβαση σε αυτή τη λειτουργία.';
            header('Location: ' . BASE_URL . 'login');
            exit();
        }

        // Έλεγχος αν ο οδηγός έχει ήδη ένα βιογραφικό
        $driver = $this->profileModel->getDriverById($id);
        if (!$driver) {
            $_SESSION['error_message'] = 'Ο οδηγός δεν βρέθηκε.';
            header('Location: ' . BASE_URL . 'job-listings');
            exit();
        }

        // Αν υπάρχει ήδη βιογραφικό, προσφέρεται για κατέβασμα
        if (isset($driver['resume_file']) && !empty($driver['resume_file']) && file_exists(ROOT_DIR . '/public/' . $driver['resume_file'])) {
            // Ορισμός του ονόματος του αρχείου
            $filename = 'drivejob_' . $driver['first_name'] . '_' . $driver['last_name'] . '_resume.pdf';

            // Προσφορά του αρχείου για κατέβασμα
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize(ROOT_DIR . '/public/' . $driver['resume_file']));
            readfile(ROOT_DIR . '/public/' . $driver['resume_file']);
            exit();
        } else {
            // Αν δεν υπάρχει βιογραφικό, δημιουργία καινούργιου
            $this->generateResume($id);
        }
    }
}
