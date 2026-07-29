<?php
require_once __DIR__ . '/../src/bootstrap.php';

echo "<h1>Διόρθωση Driver Profile Widgets</h1>";

// Read the driver profile file
$profilePath = ROOT_DIR . '/src/Views/drivers/driver-profile.php';
$content = file_get_contents($profilePath);

// Find where to insert the messages widget
$searchString = '<?php include __DIR__ . \'/partials/matching-widget.php\'; ?>';
$position = strpos($content, $searchString);

if ($position !== false) {
    // Calculate the position after the matching widget include
    $insertPosition = $position + strlen($searchString);

    // The text to insert
    $insertText = "\n                        \n                        <!-- Messages Widget -->\n                        <?php include __DIR__ . '/partials/messages-widget.php'; ?>";

    // Check if messages widget is already included
    if (strpos($content, 'messages-widget.php') === false) {
        // Insert the messages widget
        $newContent = substr($content, 0, $insertPosition) . $insertText . substr($content, $insertPosition);

        // Write back to file
        file_put_contents($profilePath, $newContent);
        echo "<p>✓ Added messages widget to driver profile</p>";
    } else {
        echo "<p>✓ Messages widget already exists in driver profile</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Could not find matching widget include in driver profile</p>";
}

// Also check if the matching widget has the correct title
$matchingWidgetPath = ROOT_DIR . '/src/Views/drivers/partials/matching-widget.php';
if (file_exists($matchingWidgetPath)) {
    $widgetContent = file_get_contents($matchingWidgetPath);

    // Check if it has "AI Προτάσεις" in the title
    if (strpos($widgetContent, 'AI Προτάσεις') === false) {
        // Update the title
        $widgetContent = str_replace(
            '<h3>Προτεινόμενες Θέσεις Εργασίας</h3>',
            '<h3><i class="fas fa-robot"></i> AI Προτάσεις Εργασίας</h3>',
            $widgetContent
        );
        file_put_contents($matchingWidgetPath, $widgetContent);
        echo "<p>✓ Updated matching widget title to include 'AI Προτάσεις'</p>";
    } else {
        echo "<p>✓ Matching widget already has correct title</p>";
    }
}

echo "<h2>Σύνοψη</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<p>Τα widgets του driver profile έχουν διορθωθεί!</p>";
echo "<p>Τώρα το driver profile περιλαμβάνει:</p>";
echo "<ul>";
echo "<li>AI Matching Widget (Προτάσεις Εργασίας)</li>";
echo "<li>Messages Widget (Μηνύματα)</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='" . BASE_URL . "drivers/profile' class='btn btn-primary'>Δείτε το Driver Profile</a></p>";
