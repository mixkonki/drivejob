<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Job Listings</h2>";

// Test 1: Check if bootstrap loads
echo "<h3>1. Loading bootstrap...</h3>";
try {
    require_once __DIR__ . '/../../src/bootstrap.php';
    echo "✅ Bootstrap loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Error loading bootstrap: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Check database connection
echo "<h3>2. Testing database connection...</h3>";
try {
    $pdo = Drivejob\Core\Database::getInstance()->getConnection();
    echo "✅ Database connected<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    exit;
}

// Test 3: Run the query
echo "<h3>3. Running job listings query...</h3>";
try {
    $sql = "
        SELECT j.*, c.company_name, c.city as company_city
        FROM job_listings j
        LEFT JOIN companies c ON j.company_id = c.id
        WHERE j.listing_type = 'job_offer' 
        AND j.status = 'active'
        AND j.company_id IS NOT NULL
        ORDER BY j.created_at DESC
    ";

    echo "Query: <pre>" . htmlspecialchars($sql) . "</pre>";

    $stmt = $pdo->query($sql);
    $jobListings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "✅ Query executed successfully<br>";
    echo "Found " . count($jobListings) . " job listings<br>";

    if (!empty($jobListings)) {
        echo "<h4>Job Listings:</h4>";
        echo "<ul>";
        foreach ($jobListings as $job) {
            echo "<li>ID: {$job['id']} - {$job['title']} ({$job['company_name']}) - Status: {$job['status']} - Type: {$job['listing_type']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>⚠️ No job listings found matching the criteria</p>";

        // Check all job listings
        echo "<h4>All job listings in database:</h4>";
        $stmt = $pdo->query("SELECT id, title, listing_type, status, company_id FROM job_listings");
        $allJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Title</th><th>Type</th><th>Status</th><th>Company ID</th></tr>";
        foreach ($allJobs as $job) {
            echo "<tr>";
            echo "<td>{$job['id']}</td>";
            echo "<td>{$job['title']}</td>";
            echo "<td>{$job['listing_type']}</td>";
            echo "<td>{$job['status']}</td>";
            echo "<td>{$job['company_id']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Query error: " . $e->getMessage() . "<br>";
}

// Test 4: Check if list.php exists
echo "<h3>4. Checking list.php...</h3>";
if (file_exists(__DIR__ . '/list.php')) {
    echo "✅ list.php exists<br>";
} else {
    echo "❌ list.php not found<br>";
}

// Test 5: Check if views exist
echo "<h3>5. Checking views...</h3>";
if (file_exists(__DIR__ . '/../../src/Views/job-listings/index.php')) {
    echo "✅ View file exists<br>";
} else {
    echo "⚠️ View file not found (using fallback)<br>";
}

echo "<hr>";
echo "<p><a href='/drivejob/public/job-listings/'>Go to Job Listings</a></p>";
