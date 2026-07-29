<?php
// Debug script για το AI widget
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Session;

// Manually set session for testing
Session::set('user_id', 4);
Session::set('user_role', 'company');

// Get company data
$pdo = \Drivejob\Core\Database::getInstance()->getConnection();

// Get listings for company
$stmt = $pdo->prepare("
    SELECT id, title, is_active 
    FROM job_listings 
    WHERE company_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([4]);
$listings = $stmt->fetchAll(\PDO::FETCH_ASSOC);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Widget Debug</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
</head>
<body>
    <div class='container mt-5'>
        <h2>AI Widget Debug</h2>
        
        <div class='row'>
            <div class='col-md-6'>
                <h4>Company Listings:</h4>
                <pre>";
print_r($listings);
echo "</pre>
            </div>
            
            <div class='col-md-6'>
                <h4>Widget Output:</h4>
                ";

// Simulate the data structure expected by the widget
$listings = ['results' => $listings];

// Include the widget
include __DIR__ . '/../src/Views/companies/partials/candidates-widget-final.php';

echo "
            </div>
        </div>
    </div>
</body>
</html>";
