<?php

namespace Drivejob\Services;

use Drivejob\Core\Logger;

/**
 * Υπηρεσία για τη διαχείριση αρχείων
 */
class FileService
{
    /**
     * Οι επιτρεπόμενοι τύποι αρχείων ανά κατηγορία
     * 
     * @var array
     */
    private $allowedTypes = [
        'image' => ['image/jpeg', 'image/png', 'image/gif'],
        'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'all' => ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
    ];

    /**
     * Τα μέγιστα μεγέθη αρχείων ανά κατηγορία (σε bytes)
     * 
     * @var array
     */
    private $maxSizes = [
        'image' => 2 * 1024 * 1024, // 2MB
        'document' => 5 * 1024 * 1024, // 5MB
        'all' => 10 * 1024 * 1024 // 10MB
    ];

    /**
     * Οι βασικοί κατάλογοι για κάθε τύπο αρχείου
     * 
     * @var array
     */
    private $baseDirectories = [
        'profile_image' => 'profile_images',
        'resume_file' => 'resumes',
        'criminal_record_file' => 'criminal_records',
        'license_front_image' => 'licenses',
        'license_back_image' => 'licenses',
        'tachograph_front_image' => 'tachographs',
        'tachograph_back_image' => 'tachographs',
        'adr_front_image' => 'adr_certificates',
        'adr_back_image' => 'adr_certificates',
        'operator_front_image' => 'operator_licenses',
        'operator_back_image' => 'operator_licenses',
        'certificate_file' => 'certificates',
        'default' => 'misc'
    ];

    /**
     * Ο βασικός κατάλογος για τα αρχεία
     * 
     * @var string
     */
    private $uploadsDir;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->uploadsDir = ROOT_DIR . '/uploads';
    }

    /**
     * Ανεβάζει ένα αρχείο
     * 
     * @param array $file Τα δεδομένα του αρχείου από το $_FILES
     * @param string $fileType Ο τύπος του αρχείου (π.χ. profile_image, resume_file)
     * @param string $category Η κατηγορία του αρχείου (image, document, all)
     * @return array Πληροφορίες για το αρχείο που ανέβηκε ή σφάλμα
     */
    public function uploadFile($file, $fileType, $category = 'all')
    {
        // Έλεγχος αν υπάρχει αρχείο
        if (!isset($file) || !is_array($file) || empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => 'Δεν παρέχθηκε έγκυρο αρχείο',
                'error_code' => 'no_file'
            ];
        }

        // Έλεγχος τύπου αρχείου
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($file['tmp_name']);

        if (!in_array($mimeType, $this->allowedTypes[$category])) {
            return [
                'success' => false,
                'message' => 'Μη επιτρεπόμενος τύπος αρχείου. Επιτρέπονται μόνο: ' . implode(', ', $this->getAllowedExtensions($category)),
                'error_code' => 'invalid_type'
            ];
        }

        // Έλεγχος μεγέθους αρχείου
        if ($file['size'] > $this->maxSizes[$category]) {
            return [
                'success' => false,
                'message' => 'Το αρχείο είναι πολύ μεγάλο. Μέγιστο επιτρεπόμενο μέγεθος: ' . $this->formatSize($this->maxSizes[$category]),
                'error_code' => 'file_too_large'
            ];
        }

        // Δημιουργία του καταλόγου προορισμού
        $directory = $this->getUploadDirectory($fileType);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                return [
                    'success' => false,
                    'message' => 'Αποτυχία δημιουργίας καταλόγου προορισμού',
                    'error_code' => 'directory_creation_failed'
                ];
            }
        }

        // Δημιουργία μοναδικού ονόματος αρχείου
        $filename = uniqid() . '_' . $this->sanitizeFilename(basename($file['name']));
        $targetPath = $directory . '/' . $filename;

        // Ανέβασμα του αρχείου
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return [
                'success' => false,
                'message' => 'Αποτυχία ανεβάσματος αρχείου',
                'error_code' => 'upload_failed'
            ];
        }

        // Καταγραφή επιτυχίας
        Logger::info('Επιτυχές ανέβασμα αρχείου', [
            'file_type' => $fileType,
            'original_name' => $file['name'],
            'saved_as' => $filename,
            'mime_type' => $mimeType,
            'size' => $file['size']
        ]);

        // Επιστροφή πληροφοριών για το αρχείο
        return [
            'success' => true,
            'message' => 'Το αρχείο ανέβηκε με επιτυχία',
            'file_path' => str_replace(ROOT_DIR, '', $targetPath),
            'file_name' => $filename,
            'original_name' => $file['name'],
            'mime_type' => $mimeType,
            'size' => $file['size']
        ];
    }

    /**
     * Διαγράφει ένα αρχείο
     * 
     * @param string $filePath Η διαδρομή του αρχείου
     * @return array Αποτέλεσμα της διαγραφής
     */
    public function deleteFile($filePath)
    {
        // Έλεγχος αν η διαδρομή είναι έγκυρη
        if (empty($filePath)) {
            return [
                'success' => false,
                'message' => 'Δεν παρέχθηκε έγκυρη διαδρομή αρχείου',
                'error_code' => 'invalid_path'
            ];
        }

        // Προσθήκη του ROOT_DIR αν η διαδρομή είναι σχετική
        if (strpos($filePath, ROOT_DIR) !== 0) {
            $filePath = ROOT_DIR . $filePath;
        }

        // Έλεγχος αν το αρχείο υπάρχει
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'message' => 'Το αρχείο δεν υπάρχει',
                'error_code' => 'file_not_found'
            ];
        }

        // Διαγραφή του αρχείου
        if (!unlink($filePath)) {
            return [
                'success' => false,
                'message' => 'Αποτυχία διαγραφής αρχείου',
                'error_code' => 'delete_failed'
            ];
        }

        // Καταγραφή επιτυχίας
        Logger::info('Επιτυχής διαγραφή αρχείου', [
            'file_path' => $filePath
        ]);

        return [
            'success' => true,
            'message' => 'Το αρχείο διαγράφηκε με επιτυχία'
        ];
    }

    /**
     * Επιστρέφει τον κατάλογο προορισμού για ένα συγκεκριμένο τύπο αρχείου
     * 
     * @param string $fileType Ο τύπος του αρχείου
     * @return string Ο κατάλογος προορισμού
     */
    public function getUploadDirectory($fileType)
    {
        $baseDir = $this->uploadsDir;
        $subDir = isset($this->baseDirectories[$fileType]) ? $this->baseDirectories[$fileType] : $this->baseDirectories['default'];

        return $baseDir . '/' . $subDir;
    }

    /**
     * Επιστρέφει τις επιτρεπόμενες επεκτάσεις αρχείων για μια κατηγορία
     * 
     * @param string $category Η κατηγορία του αρχείου
     * @return array Οι επιτρεπόμενες επεκτάσεις
     */
    public function getAllowedExtensions($category)
    {
        $extensions = [];
        $mimeToExt = [
            'image/jpeg' => 'jpg/jpeg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
        ];

        foreach ($this->allowedTypes[$category] as $mime) {
            if (isset($mimeToExt[$mime])) {
                $extensions[] = $mimeToExt[$mime];
            }
        }

        return $extensions;
    }

    /**
     * Επιστρέφει το μέγιστο επιτρεπόμενο μέγεθος αρχείου για μια κατηγορία
     * 
     * @param string $category Η κατηγορία του αρχείου
     * @return int Το μέγιστο επιτρεπόμενο μέγεθος σε bytes
     */
    public function getMaxFileSize($category)
    {
        return $this->maxSizes[$category];
    }

    /**
     * Μορφοποιεί ένα μέγεθος σε bytes σε ανθρώπινα αναγνώσιμη μορφή
     * 
     * @param int $bytes Το μέγεθος σε bytes
     * @return string Το μέγεθος σε ανθρώπινα αναγνώσιμη μορφή
     */
    private function formatSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Καθαρίζει ένα όνομα αρχείου από μη ασφαλείς χαρακτήρες
     * 
     * @param string $filename Το όνομα του αρχείου
     * @return string Το καθαρισμένο όνομα αρχείου
     */
    private function sanitizeFilename($filename)
    {
        // Αφαίρεση ειδικών χαρακτήρων
        $filename = preg_replace('/[^\p{L}\p{N}_\-\.]/u', '_', $filename);

        // Αφαίρεση πολλαπλών παύλων ή κάτω παύλων
        $filename = preg_replace('/-+/', '-', $filename);
        $filename = preg_replace('/_+/', '_', $filename);

        return $filename;
    }

    /**
     * Επεξεργάζεται πολλαπλά αρχεία από το $_FILES
     * 
     * @param array $files Τα αρχεία από το $_FILES
     * @param array $fileTypes Οι τύποι των αρχείων
     * @return array Τα αποτελέσματα του ανεβάσματος
     */
    public function processMultipleFiles($files, $fileTypes)
    {
        $results = [];

        foreach ($fileTypes as $inputName => $fileType) {
            if (isset($files[$inputName]) && $files[$inputName]['error'] !== UPLOAD_ERR_NO_FILE) {
                // Καθορισμός της κατηγορίας με βάση τον τύπο αρχείου
                $category = 'all';
                if (strpos($fileType, 'image') !== false) {
                    $category = 'image';
                } elseif (strpos($fileType, 'file') !== false || strpos($fileType, 'document') !== false) {
                    $category = 'document';
                }

                $results[$inputName] = $this->uploadFile($files[$inputName], $fileType, $category);
            }
        }

        return $results;
    }
}
