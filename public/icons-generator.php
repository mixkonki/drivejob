<?php
/**
 * License Icons Generator
 * 
 * Αυτό το script δημιουργεί τα εικονίδια για τις κατηγορίες αδειών οδήγησης
 * χρησιμοποιώντας τη βιβλιοθήκη GD.
 * 
 * Για να λειτουργήσει, πρέπει να έχει ενεργοποιηθεί η επέκταση GD στην PHP.
 */

// Διαδρομή για την αποθήκευση των εικονιδίων
$outputDir = __DIR__ . '/../public/img/license_icons/';

// Έλεγχος εάν υπάρχει ο φάκελος, αλλιώς δημιουργία του
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
    echo "Δημιουργήθηκε φάκελος: $outputDir\n";
}

// Ορισμός των κατηγοριών αδειών οδήγησης
$licenseCategories = [
    'AM' => 'Μοτοποδήλατα',
    'A1' => 'Μοτοσυκλέτες έως 125 cc',
    'A2' => 'Μοτοσυκλέτες έως 35 kW',
    'A' => 'Μοτοσυκλέτες',
    'B' => 'Επιβατικά',
    'BE' => 'Επιβατικά με ρυμουλκούμενο',
    'C1' => 'Φορτηγά < 7.5t',
    'C1E' => 'Φορτηγά < 7.5t με ρυμουλκούμενο',
    'C' => 'Φορτηγά',
    'CE' => 'Φορτηγά με ρυμουλκούμενο',
    'D1' => 'Μικρά λεωφορεία',
    'D1E' => 'Μικρά λεωφορεία με ρυμουλκούμενο',
    'D' => 'Λεωφορεία',
    'DE' => 'Λεωφορεία με ρυμουλκούμενο'
];

// Ορισμός χρωμάτων για τις διαφορετικές κατηγορίες
$categoryColors = [
    'AM' => [255, 140, 0], // Πορτοκαλί
    'A1' => [255, 140, 0],
    'A2' => [255, 140, 0],
    'A' => [255, 140, 0],
    'B' => [0, 128, 0],    // Πράσινο
    'BE' => [0, 128, 0],
    'C1' => [0, 0, 255],   // Μπλε
    'C1E' => [0, 0, 255],
    'C' => [0, 0, 255],
    'CE' => [0, 0, 255],
    'D1' => [191, 0, 0],   // Κόκκινο
    'D1E' => [191, 0, 0],
    'D' => [191, 0, 0],
    'DE' => [191, 0, 0]
];

// Δημιουργία εικονιδίων για κάθε κατηγορία
foreach ($licenseCategories as $category => $description) {
    // Δημιουργία του εικονιδίου
    createLicenseIcon($category, $categoryColors[$category], $outputDir);
    echo "Δημιουργήθηκε εικονίδιο για την κατηγορία: $category\n";
}

echo "Ολοκληρώθηκε η δημιουργία όλων των εικονιδίων.\n";

/**
 * Δημιουργεί ένα εικονίδιο για μια κατηγορία άδειας οδήγησης
 * 
 * @param string $category Κατηγορία άδειας
 * @param array $color Χρώμα σε μορφή RGB [r, g, b]
 * @param string $outputDir Φάκελος αποθήκευσης
 */
function createLicenseIcon($category, $color, $outputDir) {
    // Διαστάσεις εικονιδίου
    $width = 100;
    $height = 100;
    
    // Δημιουργία εικόνας
    $image = imagecreatetruecolor($width, $height);
    
    // Ορισμός διαφάνειας
    imagealphablending($image, false);
    imagesavealpha($image, true);
    
    // Διαφανές φόντο
    $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
    imagefill($image, 0, 0, $transparent);
    
    // Χρώματα
    $bgColor = imagecolorallocatealpha($image, $color[0], $color[1], $color[2], 30);  // Αρκετά διαφανές
    $borderColor = imagecolorallocate($image, $color[0], $color[1], $color[2]);       // Πλήρες χρώμα
    $textColor = imagecolorallocate($image, 255, 255, 255);                           // Λευκό
    
    // Δημιουργία κύκλου
    $centerX = $width / 2;
    $centerY = $height / 2;
    $radius = min($width, $height) / 2 - 5;
    
    // Γέμισμα κύκλου
    imagefilledellipse($image, $centerX, $centerY, $radius * 2, $radius * 2, $bgColor);
    
    // Περίγραμμα κύκλου
    imageellipse($image, $centerX, $centerY, $radius * 2, $radius * 2, $borderColor);
    
    // Προσθήκη κειμένου
    $fontSize = 30;  // Μέγεθος γραμματοσειράς για το GD
    
    // Υπολογισμός θέσης κειμένου
    $textBox = imagettfbbox($fontSize, 0, __DIR__ . '/arial.ttf', $category);
    $textWidth = $textBox[2] - $textBox[0];
    $textHeight = $textBox[1] - $textBox[7];
    $textX = $centerX - ($textWidth / 2);
    $textY = $centerY + ($textHeight / 2);
    
    // Προσθήκη κειμένου
    imagettftext($image, $fontSize, 0, $textX, $textY, $textColor, __DIR__ . '/arial.ttf', $category);
    
    // Αποθήκευση εικόνας
    $filepath = $outputDir . strtolower($category) . '.png';
    imagepng($image, $filepath);
    
    // Απελευθέρωση μνήμης
    imagedestroy($image);
}

// Σημείωση: Αυτό το script απαιτεί την ύπαρξη ενός αρχείου γραμματοσειράς 'arial.ttf'
// στον ίδιο φάκελο. Εναλλακτικά, μπορείτε να χρησιμοποιήσετε κάποια από τις
// ενσωματωμένες γραμματοσειρές της GD με τις συναρτήσεις imagestring() και
// imagestringup() αντί για την imagettftext().