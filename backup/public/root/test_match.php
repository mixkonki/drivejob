<?php
require_once __DIR__ . '/../src/bootstrap.php';

// Δημιουργία του router
$router = new \Drivejob\Core\Router();

// Φόρτωση των διαδρομών
require_once __DIR__ . '/../config/routes.php';

// Διαδρομή για δοκιμή
$testPath = '/job-listings';

// Εμφάνιση των διαδρομών
echo "<h1>Καταχωρημένες Διαδρομές</h1>";
echo "<pre>";
$routes = $router->getRoutes();
print_r($routes);
echo "</pre>";

// Δοκιμή αντιστοίχισης διαδρομής
echo "<h1>Δοκιμή Αντιστοίχισης Διαδρομής</h1>";
echo "<p>Διαδρομή για δοκιμή: $testPath</p>";

// Προσομοίωση του getPath()
function simulateGetPath($path)
{
    echo "<p>Προσομοίωση του getPath() για το path: $path</p>";

    // Αφαίρεση του βασικού path της εφαρμογής - διόρθωση για το WAMP
    $basePath = '/drivejob/public';
    if (strpos($path, $basePath) === 0) {
        $path = substr($path, strlen($basePath));
    }

    // Καθαρισμός του path
    $path = trim($path, '/');
    $path = '/' . $path;

    echo "<p>Επεξεργασμένο path: $path</p>";

    return $path;
}

// Προσομοίωση του resolve()
function simulateResolve($router, $path)
{
    echo "<p>Προσομοίωση του resolve() για το path: $path</p>";

    $method = 'GET';

    // Έλεγχος αν υπάρχει ακριβής διαδρομή
    $routes = $router->getRoutes();
    echo "<p>Διαθέσιμες διαδρομές για τη μέθοδο $method:</p>";
    echo "<ul>";
    foreach ($routes[$method] ?? [] as $route => $callback) {
        echo "<li>$route</li>";
    }
    echo "</ul>";

    if (isset($routes[$method][$path])) {
        echo "<p>Βρέθηκε ακριβής διαδρομή: $path</p>";
        echo "<p>Callback: " . print_r($routes[$method][$path], true) . "</p>";
        return true;
    }

    // Έλεγχος για παραμετροποιημένες διαδρομές
    foreach ($routes[$method] ?? [] as $route => $callback) {
        $pattern = convertRouteToRegex($route);
        echo "<p>Έλεγχος διαδρομής: $route με pattern: " . htmlspecialchars($pattern) . "</p>";
        if ($pattern && preg_match($pattern, $path)) {
            echo "<p>Βρέθηκε παραμετροποιημένη διαδρομή: $route</p>";
            echo "<p>Callback: " . print_r($callback, true) . "</p>";
            return true;
        }
    }

    echo "<p>Δεν βρέθηκε διαδρομή για το path: $path</p>";
    return false;
}

// Μετατροπή διαδρομής σε κανονική έκφραση
function convertRouteToRegex($route, $caseInsensitive = true)
{
    // Αντικατάσταση παραμέτρων της μορφής {id} με ομάδες regex
    $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route);
    // Προσθήκη ^ και $ για ακριβές ταίριασμα και προετοιμασία για preg_match
    $flags = $caseInsensitive ? 'i' : '';
    return "#^{$pattern}$" . ($flags ? "#{$flags}" : "#");
}

// Δοκιμή αντιστοίχισης
$processedPath = simulateGetPath($testPath);
$matched = simulateResolve($router, $processedPath);

echo "<p>Αποτέλεσμα αντιστοίχισης: " . ($matched ? 'Επιτυχία' : 'Αποτυχία') . "</p>";
