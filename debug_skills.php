<?php
// Αρχείο για τον έλεγχο της αποθήκευσης των δεξιοτήτων

// Φόρτωση του bootstrap της εφαρμογής
require_once __DIR__ . '/src/bootstrap.php';

// Λήψη του PDO από το container
$container = \Drivejob\Core\Container::getInstance();
$pdo = $container->get('pdo');

// Δημιουργία του SkillModel
$skillModel = new \Drivejob\Models\Driver\SkillModel($pdo);

// Έλεγχος αν έχει οριστεί το driver_id
$driverId = isset($_GET['driver_id']) ? intval($_GET['driver_id']) : null;

if (!$driverId) {
    echo "<h1>Σφάλμα: Δεν έχει οριστεί το driver_id</h1>";
    exit;
}

// Ανάκτηση των δεξιοτήτων του οδηγού
$driverSkills = $skillModel->getDriverSkills($driverId);

echo "<h1>Δεξιότητες Οδηγού (ID: $driverId)</h1>";

if (empty($driverSkills)) {
    echo "<p>Δεν βρέθηκαν δεξιότητες για τον οδηγό με ID: $driverId</p>";
} else {
    echo "<h2>Υπάρχουσες Δεξιότητες</h2>";
    echo "<pre>";
    print_r($driverSkills);
    echo "</pre>";

    // Έλεγχος για τις νέες δεξιότητες
    echo "<h2>Έλεγχος Νέων Δεξιοτήτων</h2>";
    $newSkills = [
        'precision_handling',
        'fire_safety',
        'vehicle_inspection',
        'report_writing',
        'inspection_behavior',
        'border_crossing',
        'technical_terms',
        'equipment_handling',
        'checklists_usage',
        'freight_only_loading',
        'freight_only_dangerous'
    ];

    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Δεξιότητα</th><th>Υπάρχει στη Βάση</th><th>Τιμή</th></tr>";

    foreach ($newSkills as $skill) {
        $exists = array_key_exists($skill, $driverSkills);
        $value = $exists ? $driverSkills[$skill] : 'N/A';

        echo "<tr>";
        echo "<td>$skill</td>";
        echo "<td>" . ($exists ? 'Ναι' : 'Όχι') . "</td>";
        echo "<td>$value</td>";
        echo "</tr>";
    }

    echo "</table>";
}

// Έλεγχος της δομής του πίνακα driver_skills
echo "<h2>Δομή Πίνακα driver_skills</h2>";

try {
    $stmt = $pdo->query("DESCRIBE driver_skills");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<p>Στήλες στον πίνακα driver_skills:</p>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>$column</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "<p>Σφάλμα κατά την ανάκτηση της δομής του πίνακα: " . $e->getMessage() . "</p>";
}

// Έλεγχος του κώδικα στο SkillModel.php
echo "<h2>Κώδικας updateDriverSkills στο SkillModel.php</h2>";
echo "<pre>";
$skillModelFile = file_get_contents(__DIR__ . '/src/Models/Driver/SkillModel.php');
if ($skillModelFile) {
    // Εξαγωγή της μεθόδου updateDriverSkills
    preg_match('/public function updateDriverSkills.*?{(.*?)}[\s\n]*public/s', $skillModelFile, $matches);
    if (isset($matches[1])) {
        echo htmlspecialchars($matches[1]);
    } else {
        echo "Δεν βρέθηκε η μέθοδος updateDriverSkills";
    }
} else {
    echo "Δεν ήταν δυνατή η ανάγνωση του αρχείου SkillModel.php";
}
echo "</pre>";

// Έλεγχος του κώδικα στο DriverCertificationService.php
echo "<h2>Κώδικας updateSkills στο DriverCertificationService.php</h2>";
echo "<pre>";
$certificationServiceFile = file_get_contents(__DIR__ . '/src/Services/DriverCertificationService.php');
if ($certificationServiceFile) {
    // Εξαγωγή της μεθόδου updateSkills
    preg_match('/public function updateSkills.*?{(.*?)}[\s\n]*private/s', $certificationServiceFile, $matches);
    if (isset($matches[1])) {
        echo htmlspecialchars($matches[1]);
    } else {
        echo "Δεν βρέθηκε η μέθοδος updateSkills";
    }
} else {
    echo "Δεν ήταν δυνατή η ανάγνωση του αρχείου DriverCertificationService.php";
}
echo "</pre>";
