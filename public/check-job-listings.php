<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;

$pdo = Database::getInstance()->getConnection();

echo "<h2>Έλεγχος Job Listings</h2>";

// Check if table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'job_listings'");
    $tableExists = $stmt->fetch();

    if ($tableExists) {
        echo "<p>✅ Ο πίνακας job_listings υπάρχει</p>";

        // Check structure
        $stmt = $pdo->query("DESCRIBE job_listings");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Δομή πίνακα:</h3><pre>";
        print_r($columns);
        echo "</pre>";

        // Check data
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM job_listings");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Συνολικές αγγελίες: " . $count['total'] . "</p>";

        // Check active listings
        $stmt = $pdo->query("SELECT COUNT(*) as active FROM job_listings WHERE is_active = 1");
        $activeCount = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Ενεργές αγγελίες: " . $activeCount['active'] . "</p>";

        // Show sample data
        $stmt = $pdo->query("SELECT * FROM job_listings LIMIT 5");
        $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Δείγμα δεδομένων:</h3><pre>";
        print_r($listings);
        echo "</pre>";
    } else {
        echo "<p>❌ Ο πίνακας job_listings ΔΕΝ υπάρχει</p>";

        // Create table
        echo "<p>Δημιουργία πίνακα job_listings...</p>";
        $sql = "CREATE TABLE IF NOT EXISTS job_listings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            location VARCHAR(255),
            salary_range VARCHAR(100),
            employment_type VARCHAR(50),
            requirements TEXT,
            benefits TEXT,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        )";

        $pdo->exec($sql);
        echo "<p>✅ Πίνακας δημιουργήθηκε</p>";

        // Add sample data
        echo "<p>Προσθήκη δοκιμαστικών αγγελιών...</p>";

        // Get a company ID
        $stmt = $pdo->query("SELECT id FROM companies LIMIT 1");
        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($company) {
            $sampleJobs = [
                [
                    'title' => 'Οδηγός Φορτηγού C+E',
                    'description' => 'Ζητείται έμπειρος οδηγός για διεθνείς μεταφορές. Απαραίτητη εμπειρία τουλάχιστον 2 ετών.',
                    'location' => 'Αθήνα',
                    'salary_range' => '1500-2000€',
                    'employment_type' => 'Πλήρης απασχόληση',
                    'requirements' => 'Δίπλωμα C+E, Κάρτα ψηφιακού ταχογράφου, ADR',
                    'benefits' => 'Ασφάλιση, Bonus παραγωγικότητας'
                ],
                [
                    'title' => 'Οδηγός Διανομής',
                    'description' => 'Ζητείται οδηγός για διανομές εντός Αττικής. Πρωινό ωράριο.',
                    'location' => 'Πειραιάς',
                    'salary_range' => '900-1200€',
                    'employment_type' => 'Πλήρης απασχόληση',
                    'requirements' => 'Δίπλωμα Β, Εμπειρία σε διανομές',
                    'benefits' => 'Σταθερό ωράριο, Ασφάλιση'
                ],
                [
                    'title' => 'Οδηγός Λεωφορείου',
                    'description' => 'Ζητείται οδηγός για τουριστικό λεωφορείο. Εποχική εργασία.',
                    'location' => 'Θεσσαλονίκη',
                    'salary_range' => '1200-1500€',
                    'employment_type' => 'Εποχική απασχόληση',
                    'requirements' => 'Δίπλωμα D, ΠΕΙ',
                    'benefits' => 'Διαμονή, Γεύματα'
                ]
            ];

            $stmt = $pdo->prepare("INSERT INTO job_listings (company_id, title, description, location, salary_range, employment_type, requirements, benefits) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($sampleJobs as $job) {
                $stmt->execute([
                    $company['id'],
                    $job['title'],
                    $job['description'],
                    $job['location'],
                    $job['salary_range'],
                    $job['employment_type'],
                    $job['requirements'],
                    $job['benefits']
                ]);
            }

            echo "<p>✅ Προστέθηκαν " . count($sampleJobs) . " δοκιμαστικές αγγελίες</p>";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ Σφάλμα: " . $e->getMessage() . "</p>";
}

// Check messages issue
echo "<hr><h2>Έλεγχος Messages</h2>";

try {
    // Check conversations
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM conversations");
    $convCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Συνολικές συνομιλίες: " . $convCount['total'] . "</p>";

    // Check messages
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM messages");
    $msgCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Συνολικά μηνύματα: " . $msgCount['total'] . "</p>";

    // Show sample conversations with message count
    $stmt = $pdo->query("
        SELECT c.*, 
               (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id) as message_count
        FROM conversations c
        LIMIT 5
    ");
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Δείγμα συνομιλιών:</h3><pre>";
    print_r($conversations);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p>❌ Σφάλμα: " . $e->getMessage() . "</p>";
}
