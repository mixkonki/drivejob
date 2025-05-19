<?php

namespace Drivejob\Services;

use PDO;
use Drivejob\Core\Logger;
use Drivejob\Models\Driver\ProfileModel;

/**
 * Υπηρεσία για το ανέβασμα αρχείων
 * 
 * @deprecated Χρησιμοποιήστε το FileService αντί για αυτό
 */
class FileUploadService
{
    private $pdo;
    private $profileModel;
    private $fileService;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->profileModel = new ProfileModel($pdo);
        $this->fileService = new FileService();
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

        // Ορισμός των τύπων αρχείων
        $fileTypes = [
            'license_front_image' => 'license_front_image',
            'license_back_image' => 'license_back_image',
            'profile_image' => 'profile_image',
            'adr_front_image' => 'adr_front_image',
            'adr_back_image' => 'adr_back_image',
            'operator_front_image' => 'operator_front_image',
            'operator_back_image' => 'operator_back_image',
            'tachograph_front_image' => 'tachograph_front_image',
            'tachograph_back_image' => 'tachograph_back_image',
            'resume_file' => 'resume_file'
        ];

        // Χρήση του FileService για το ανέβασμα πολλαπλών αρχείων
        $results = $this->fileService->processMultipleFiles($files, $fileTypes);

        // Ενημέρωση της βάσης δεδομένων με τα αποτελέσματα
        foreach ($results as $field => $result) {
            if ($result['success']) {
                if ($field === 'profile_image') {
                    $this->profileModel->updateProfileImage($driverId, $result['file_path']);
                } else if ($field === 'resume_file') {
                    $this->profileModel->updateResumeFile($driverId, $result['file_path']);
                } else {
                    $this->profileModel->updateDriverDocumentImage($driverId, $field, $result['file_path']);
                }
            } else {
                $_SESSION['error_message'] = $result['message'];
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
        // Χρήση του FileService για το ανέβασμα του αρχείου
        $result = $this->fileService->uploadFile($file, 'profile_image', 'image');

        if ($result['success']) {
            // Ενημέρωση του πεδίου στη βάση δεδομένων
            $this->profileModel->updateProfileImage($driverId, $result['file_path']);
            return true;
        } else {
            // Σε περίπτωση σφάλματος, αποθήκευση του μηνύματος σφάλματος
            $_SESSION['error_message'] = $result['message'];
            return false;
        }
    }

    /**
     * Διαχειρίζεται τη μεταφόρτωση βιογραφικού
     */
    private function handleResumeFileUpload($driverId, $file)
    {
        // Χρήση του FileService για το ανέβασμα του αρχείου
        $result = $this->fileService->uploadFile($file, 'resume_file', 'document');

        if ($result['success']) {
            // Ενημέρωση του πεδίου στη βάση δεδομένων
            $this->profileModel->updateResumeFile($driverId, $result['file_path']);
            return true;
        } else {
            // Σε περίπτωση σφάλματος, αποθήκευση του μηνύματος σφάλματος
            $_SESSION['error_message'] = $result['message'];
            return false;
        }
    }

    /**
     * Διαχειρίζεται τη μεταφόρτωση εικόνων εγγράφων
     */
    private function handleDocumentImageUpload($driverId, $fieldName, $uploadPath, $documentType, $file)
    {
        // Χρήση του FileService για το ανέβασμα του αρχείου
        $result = $this->fileService->uploadFile($file, $documentType, 'image');

        if ($result['success']) {
            // Ενημέρωση του πεδίου στον πίνακα drivers
            $this->profileModel->updateDriverDocumentImage($driverId, $documentType, $result['file_path']);
            return true;
        } else {
            // Σε περίπτωση σφάλματος, αποθήκευση του μηνύματος σφάλματος
            $_SESSION['error_message'] = $result['message'];
            return false;
        }
    }
}
