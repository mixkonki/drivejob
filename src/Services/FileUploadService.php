<?php

namespace Drivejob\Services;

use PDO;
use Drivejob\Core\Logger;
use Drivejob\Models\Driver\ProfileModel;

class FileUploadService
{
    private $pdo;
    private $profileModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->profileModel = new ProfileModel($pdo);
    }
    /**
     * Διαχειρίζεται τις μεταφορτώσεις αρχείων
     *
     * @param int $driverId ID του οδηγού
     * @param array $files Πίνακας των αρχείων από $_FILES
     * @return bool Επιτυχία/αποτυχία
     */
    public function handleFileUploads($driverId, $files)
    {
        $success = true;

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
            if (isset($files[$field]) && $files[$field]['error'] === UPLOAD_ERR_OK) {
                if (in_array($field, ['license_front_image', 'license_back_image'])) {
                    $result = $this->handleLicenseImageUpload($driverId, $field, $files[$field]);
                } else if ($field === 'profile_image') {
                    $result = $this->handleProfileImageUpload($driverId, $files[$field]);
                } else {
                    $uploadDir = $this->getUploadDirectory($field);
                    $result = $this->handleDocumentImageUpload($driverId, $field, $uploadDir, $field, $files[$field]);
                }

                if (!$result) {
                    $success = false;
                }
            }
        }

        // Μεταφόρτωση βιογραφικού
        if (isset($files['resume_file']) && $files['resume_file']['error'] === UPLOAD_ERR_OK) {
            $result = $this->handleResumeFileUpload($driverId, $files['resume_file']);
            if (!$result) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Επιστρέφει τον κατάλογο μεταφόρτωσης για κάθε τύπο αρχείου
     */
    private function getUploadDirectory($fieldName)
    {
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
    private function handleLicenseImageUpload($driverId, $fieldName, $file)
    {
        $uploadPath = 'uploads/license_images/';
        return $this->handleDocumentImageUpload($driverId, $fieldName, $uploadPath, $fieldName, $file);
    }

    /**
     * Διαχειρίζεται τη μεταφόρτωση εικόνας προφίλ
     */
    private function handleProfileImageUpload($driverId, $file)
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB

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
    private function handleResumeFileUpload($driverId, $file)
    {
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $maxSize = 5 * 1024 * 1024; // 5MB

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
     * Διαχειρίζεται τη μεταφόρτωση εικόνων εγγράφων
     */
    private function handleDocumentImageUpload($driverId, $fieldName, $uploadPath, $documentType, $file)
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB

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

            return true;
        }

        $_SESSION['error_message'] = 'Σφάλμα κατά τη μεταφόρτωση της εικόνας. Παρακαλώ δοκιμάστε ξανά.';
        return false;
    }
}
