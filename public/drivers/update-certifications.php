<?php

// Αρχικοποίηση της εφαρμογής
$container = require_once __DIR__ . '/../../src/bootstrap.php';

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι οδηγός
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Έλεγχος αν η μέθοδος είναι POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'drivers/certifications.php');
    exit();
}

// Έλεγχος για CSRF token
if (!isset($_POST['csrf_token']) || !\Drivejob\Core\CSRF::validateToken($_POST['csrf_token'])) {
    $_SESSION['error_message'] = 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
    header('Location: ' . BASE_URL . 'drivers/certifications.php');
    exit();
}

// Λήψη του ID του οδηγού
$driverId = $_SESSION['user_id'];

// Δημιουργία του service για τα προφίλ οδηγών
$driverProfileService = new \Drivejob\Services\DriverProfileService($pdo);

// Προσθήκη διαγνωστικών πληροφοριών
$debug = isset($_GET['debug']) && $_GET['debug'] === '1';
if ($debug) {
    echo "<h2>Διαγνωστικές Πληροφορίες</h2>";
    echo "<h3>POST Data</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
}

// Δημιουργία του φακέλου για τα αρχεία πιστοποιητικών αν δεν υπάρχει
$certificatesDir = ROOT_DIR . '/uploads/certificates';
if (!file_exists($certificatesDir)) {
    mkdir($certificatesDir, 0755, true);
}

// Επεξεργασία των δεδομένων από τη φόρμα
$certifications = [];

// Έλεγχος αν η φόρμα είναι κενή (δεν υπάρχουν πιστοποιητικά)
if (!isset($_POST['certifications']) || !is_array($_POST['certifications']) || empty($_POST['certifications'])) {
    // Λήψη των υπαρχόντων πιστοποιητικών από τη βάση δεδομένων
    $existingCertifications = $driverProfileService->getDriverCertifications($driverId);

    // Αν υπάρχουν ήδη πιστοποιητικά, τα διατηρούμε
    if (!empty($existingCertifications)) {
        $certifications = $existingCertifications;

        if ($debug) {
            echo "<h3>Διατήρηση υπαρχόντων πιστοποιητικών</h3>";
            echo "<pre>";
            print_r($certifications);
            echo "</pre>";
        }
    }
} else {
    // Επεξεργασία των πιστοποιητικών από τη φόρμα
    foreach ($_POST['certifications'] as $index => $cert) {
        // Έλεγχος αν υπάρχει τίτλος (υποχρεωτικό πεδίο)
        if (empty($cert['title'])) {
            continue;
        }

        // Έλεγχος για τα υπόλοιπα υποχρεωτικά πεδία
        if (
            empty($cert['provider']) || empty($cert['category']) || empty($cert['date']) ||
            !isset($cert['duration']) || !is_numeric($cert['duration']) || intval($cert['duration']) <= 0
        ) {
            continue;
        }

        // Δημιουργία της εγγραφής
        $certification = [
            'title' => trim($cert['title']),
            'provider' => isset($cert['provider']) ? trim($cert['provider']) : null,
            'category' => isset($cert['category']) ? trim($cert['category']) : null,
            'transport_type' => isset($cert['transport_type']) ? trim($cert['transport_type']) : 'both',
            'date' => isset($cert['date']) && !empty($cert['date']) ? $cert['date'] : null,
            'expiry' => isset($cert['expiry']) && !empty($cert['expiry']) ? $cert['expiry'] : null,
            'duration' => isset($cert['duration']) && is_numeric($cert['duration']) ? intval($cert['duration']) : null,
            'description' => isset($cert['description']) ? trim($cert['description']) : null,
            'certificate_file' => isset($cert['certificate_file']) ? trim($cert['certificate_file']) : null
        ];

        $certifications[] = $certification;
    }
}

// Έλεγχος για νέο αρχείο πιστοποιητικού
if (isset($_FILES['new_certificate_file']) && $_FILES['new_certificate_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['new_certificate_file'];

    // Έλεγχος τύπου αρχείου
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
    $fileType = $file['type'];

    if (in_array($fileType, $allowedTypes)) {
        // Δημιουργία μοναδικού ονόματος αρχείου
        $fileName = 'cert_' . $driverId . '_' . time() . '_' . basename($file['name']);
        $filePath = $certificatesDir . '/' . $fileName;

        // Μετακίνηση του αρχείου στον φάκελο προορισμού
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Προσθήκη του ονόματος αρχείου στο τελευταίο πιστοποιητικό
            if (!empty($certifications)) {
                $lastIndex = count($certifications) - 1;
                $certifications[$lastIndex]['certificate_file'] = $fileName;
            }

            if ($debug) {
                echo "<h3>Uploaded File</h3>";
                echo "<p>File successfully uploaded: $fileName</p>";
            }
        } else {
            if ($debug) {
                echo "<h3>Upload Error</h3>";
                echo "<p>Failed to move uploaded file.</p>";
            }
            $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την αποθήκευση του αρχείου. Παρακαλώ δοκιμάστε ξανά.';
        }
    } else {
        if ($debug) {
            echo "<h3>Invalid File Type</h3>";
            echo "<p>File type not allowed: $fileType</p>";
        }
        $_SESSION['error_message'] = 'Ο τύπος αρχείου δεν επιτρέπεται. Επιτρέπονται μόνο αρχεία PDF, JPEG και PNG.';
    }
}

// Διαγνωστικές πληροφορίες για τα επεξεργασμένα δεδομένα
if ($debug) {
    echo "<h3>Processed Certifications</h3>";
    echo "<pre>";
    print_r($certifications);
    echo "</pre>";
}

try {
    // Ενημέρωση των πιστοποιητικών
    $result = $driverProfileService->updateDriverCertifications($driverId, $certifications);

    if ($result) {
        $_SESSION['success_message'] = 'Τα πιστοποιητικά εκπαίδευσης ενημερώθηκαν με επιτυχία.';
    } else {
        $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την ενημέρωση των πιστοποιητικών. Παρακαλώ δοκιμάστε ξανά.';
    }
} catch (Exception $e) {
    // Καταγραφή του σφάλματος
    \Drivejob\Core\Logger::error('Σφάλμα κατά την ενημέρωση των πιστοποιητικών: ' . $e->getMessage());

    // Εμφάνιση του σφάλματος στη σελίδα διαγνωστικών
    if ($debug) {
        echo "<h3>Error</h3>";
        echo "<pre>";
        print_r($e->getMessage());
        echo "</pre>";
    }

    $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την ενημέρωση των πιστοποιητικών. Παρακαλώ δοκιμάστε ξανά.';
}

// Ανακατεύθυνση στη σελίδα των πιστοποιητικών
if (!$debug) {
    header('Location: ' . BASE_URL . 'drivers/certifications.php');
    exit();
}
