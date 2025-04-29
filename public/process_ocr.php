<?php
// public/process_ocr.php
// Ενεργοποίηση αναφοράς σφαλμάτων για αποσφαλμάτωση
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Καταγραφή του αιτήματος για αποσφαλμάτωση
file_put_contents(__DIR__ . '/../logs/ocr_request.log', date('[Y-m-d H:i:s] ') . "Request received\n", FILE_APPEND);

// ...υπόλοιπος κώδικας...

// Πριν την επιστροφή του JSON
$response = [
    'success' => true, 
    'text' => $text,
    'side' => $side,
    'debug_info' => 'Processed successfully'
];

// Καταγραφή της απάντησης
file_put_contents(__DIR__ . '/../logs/ocr_response.log', date('[Y-m-d H:i:s] ') . "Response: " . json_encode($response) . "\n", FILE_APPEND);

echo json_encode($response);

// Συμπερίληψη των απαραίτητων αρχείων
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

// Απενεργοποίηση αναφοράς σφαλμάτων
error_reporting(0);
ini_set('display_errors', 0);

// Ορισμός header για JSON response
header('Content-Type: application/json');

// Έλεγχος αν έχει γίνει upload αρχείου
if (!isset($_FILES['license_image']) || $_FILES['license_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Δεν έγινε αποστολή εικόνας ή προέκυψε σφάλμα κατά το ανέβασμα.']);
    exit;
}

// Δημιουργία προσωρινού φακέλου αν δεν υπάρχει
$tempDir = __DIR__ . '/../temp';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}

// Μετακίνηση του upload σε προσωρινό αρχείο
$uploadedFile = $_FILES['license_image']['tmp_name'];
$tempFilename = $tempDir . '/' . md5(uniqid(rand(), true)) . '.jpg';
move_uploaded_file($uploadedFile, $tempFilename);

// Η πλευρά της άδειας (εμπρός/πίσω)
$side = isset($_POST['side']) ? $_POST['side'] : 'front';

// Έλεγχος αν το tesseract είναι εγκατεστημένο
$tesseractInstalled = false;
exec("which tesseract", $output, $returnCode);
$tesseractInstalled = ($returnCode === 0);

if ($tesseractInstalled) {
    // Εκτέλεση του Tesseract OCR
    $outputFile = $tempDir . '/' . uniqid();
    $lang = "ell+eng"; // Ελληνικά + Αγγλικά
    $cmd = "tesseract $tempFilename $outputFile -l $lang";
    exec($cmd, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($outputFile . '.txt')) {
        $text = file_get_contents($outputFile . '.txt');
        
        // Καθαρισμός
        @unlink($outputFile . '.txt');
        @unlink($tempFilename);
        
        echo json_encode([
            'success' => true, 
            'text' => $text,
            'side' => $side
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'Αποτυχία αναγνώρισης κειμένου.',
            'side' => $side
        ]);
    }
} else {
    // Εναλλακτική: Προσομοίωση OCR για δοκιμαστικούς σκοπούς
    // Επιστροφή ψευδών δεδομένων για δοκιμή
    sleep(2); // Προσομοίωση καθυστέρησης επεξεργασίας
    
    if ($side === 'front') {
        $mockText = "ΑΔΕΙΑ ΟΔΗΓΗΣΗΣ\nΕΛΛΗΝΙΚΗ ΔΗΜΟΚΡΑΤΙΑ\n1. ΕΠΩΝΥΜΟ\n2. ΟΝΟΜΑ\n3. ΗΜ. ΓΕΝΝΗΣΗΣ\n4α. ΗΜΕΡΟΜΗΝΙΑ ΕΚΔΟΣΗΣ 01.01.2020\n4β. ΗΜΕΡΟΜΗΝΙΑ ΛΗΞΗΣ 01.01.2030\n5. ΑΡΙΘΜΟΣ ΑΔΕΙΑΣ AB123456";
    } else {
        $mockText = "ΚΑΤΗΓΟΡΙΕΣ\nB 01.01.2030\nC 01.01.2030\nCE 01.01.2030\n12. ΚΩΔΙΚΟΙ 95,01.01";
    }
    
    // Καθαρισμός
    @unlink($tempFilename);
    
    echo json_encode([
        'success' => true, 
        'text' => $mockText,
        'side' => $side,
        'info' => 'Αυτό είναι προσομοιωμένο αποτέλεσμα OCR επειδή το Tesseract δεν είναι εγκατεστημένο στο server.'
    ]);
}