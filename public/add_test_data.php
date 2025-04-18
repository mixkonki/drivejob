<?php
// Φόρτωση του bootstrap
require_once __DIR__ . '/../src/bootstrap.php';

// Σύνδεση με τη βάση δεδομένων
try {
    $pdo = new PDO("mysql:host=localhost;dbname=drivejob;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Επιτυχής σύνδεση με τη βάση δεδομένων.\n";
} catch (PDOException $e) {
    die("Σφάλμα σύνδεσης: " . $e->getMessage());
}

// Έλεγχος αν υπάρχει ήδη δοκιμαστικός οδηγός
$stmt = $pdo->query("SELECT * FROM drivers WHERE email = 'test@drivejob.gr'");
$driver = $stmt->fetch(PDO::FETCH_ASSOC);

if ($driver) {
    $driver_id = $driver['id'];
    echo "Χρήση υπάρχοντος οδηγού με ID: {$driver_id}\n";
} else {
    // Προσθήκη δοκιμαστικού οδηγού
    $stmt = $pdo->prepare("INSERT INTO drivers (first_name, last_name, email, phone, is_verified) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Test', 'Driver', 'test@drivejob.gr', '6912345678', 1]);
    $driver_id = $pdo->lastInsertId();
    echo "Δημιουργήθηκε νέος οδηγός με ID: {$driver_id}\n";
}

// Προσθήκη άδειας οδήγησης που λήγει σε 30 ημέρες
$expiryDate = date('Y-m-d', strtotime('+30 days'));
$stmt = $pdo->prepare("SELECT * FROM driver_licenses WHERE driver_id = ? AND license_type = 'C'");
$stmt->execute([$driver_id]);
$license = $stmt->fetch(PDO::FETCH_ASSOC);

if ($license) {
    // Ενημέρωση υπάρχουσας άδειας
    $stmt = $pdo->prepare("UPDATE driver_licenses SET expiry_date = ? WHERE id = ?");
    $stmt->execute([$expiryDate, $license['id']]);
    echo "Ενημερώθηκε η άδεια οδήγησης με ID: {$license['id']}, νέα ημερομηνία λήξης: {$expiryDate}\n";
} else {
    // Προσθήκη νέας άδειας
    $stmt = $pdo->prepare("INSERT INTO driver_licenses (driver_id, license_type, expiry_date) VALUES (?, ?, ?)");
    $stmt->execute([$driver_id, 'C', $expiryDate]);
    $license_id = $pdo->lastInsertId();
    echo "Δημιουργήθηκε νέα άδεια οδήγησης με ID: {$license_id}, ημερομηνία λήξης: {$expiryDate}\n";
}

// Προσθήκη πιστοποιητικού ADR που λήγει σε 30 ημέρες
$expiryDate = date('Y-m-d', strtotime('+30 days'));
$stmt = $pdo->prepare("SELECT * FROM driver_adr_certificates WHERE driver_id = ?");
$stmt->execute([$driver_id]);
$adr = $stmt->fetch(PDO::FETCH_ASSOC);

if ($adr) {
    // Ενημέρωση υπάρχοντος πιστοποιητικού
    $stmt = $pdo->prepare("UPDATE driver_adr_certificates SET expiry_date = ? WHERE id = ?");
    $stmt->execute([$expiryDate, $adr['id']]);
    echo "Ενημερώθηκε το πιστοποιητικό ADR με ID: {$adr['id']}, νέα ημερομηνία λήξης: {$expiryDate}\n";
} else {
    // Προσθήκη νέου πιστοποιητικού
    $stmt = $pdo->prepare("INSERT INTO driver_adr_certificates (driver_id, adr_type, expiry_date) VALUES (?, ?, ?)");
    $stmt->execute([$driver_id, 'Π1', $expiryDate]);
    $adr_id = $pdo->lastInsertId();
    echo "Δημιουργήθηκε νέο πιστοποιητικό ADR με ID: {$adr_id}, ημερομηνία λήξης: {$expiryDate}\n";
}

echo "Η διαδικασία ολοκληρώθηκε. Τώρα μπορείτε να εκτελέσετε το cron των ειδοποιήσεων.\n";