<?php
// check_authcontroller.php

$file = 'src/Controllers/AuthController.php';
$content = file_get_contents($file);

// Βρες τη γραμμή 103 και τις γραμμές γύρω από αυτή
$lines = explode("\n", $content);
echo "Γραμμή 98: " . ($lines[97] ?? '') . "\n";
echo "Γραμμή 99: " . ($lines[98] ?? '') . "\n";
echo "Γραμμή 100: " . ($lines[99] ?? '') . "\n";
echo "Γραμμή 101: " . ($lines[100] ?? '') . "\n";
echo "Γραμμή 102: " . ($lines[101] ?? '') . "\n";
echo "Γραμμή 103: " . ($lines[102] ?? '') . "\n\n";

// Ψάξε για διπλότυπα <?php tags
if (preg_match_all('/<\?php/', $content, $matches)) {
    echo "Βρέθηκαν " . count($matches[0]) . " <?php tags\n";

    $positions = [];
    $offset = 0;
    while (($pos = strpos($content, '<?php', $offset)) !== false) {
        $positions[] = $pos;
        $offset = $pos + 1;
    }

    foreach ($positions as $i => $pos) {
        echo "Tag #" . ($i + 1) . " στη θέση: " . $pos . "\n";
    }
}

// Ψάξε για διπλότυπα 
?>
if (preg_match_all('/\?\>/', $content, $matches)) {
echo "\nΒρέθηκαν " . count($matches[0]) . " ?> tags\n";
}