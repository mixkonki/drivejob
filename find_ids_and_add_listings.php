<?php

// Σύνδεση με τη βάση δεδομένων
$pdo = new PDO('mysql:host=localhost;dbname=drivejob', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Βρίσκουμε το ID του οδηγού
$stmt = $pdo->prepare('SELECT id FROM drivers WHERE email = ?');
$stmt->execute(['kostas.michailidis@hotmail.gr']);
$driver = $stmt->fetch(PDO::FETCH_ASSOC);
$driverId = $driver ? $driver['id'] : null;
echo 'Driver ID: ' . ($driverId ?: 'Not found') . "\n";

// Βρίσκουμε το ID της επιχείρησης
$stmt = $pdo->prepare('SELECT id FROM companies WHERE email = ?');
$stmt->execute(['info@thessdrive.gr']);
$company = $stmt->fetch(PDO::FETCH_ASSOC);
$companyId = $company ? $company['id'] : null;
echo 'Company ID: ' . ($companyId ?: 'Not found') . "\n";

// Προσθέτουμε αγγελία για τον οδηγό
if ($driverId) {
    try {
        $stmt = $pdo->prepare('
            INSERT INTO driver_job_listings (
                driver_id, title, description, preferred_location, 
                preferred_job_type, preferred_vehicle_type, 
                expected_salary_min, expected_salary_max, 
                availability_start_date, availability_end_date, 
                is_active, created_at, updated_at
            ) VALUES (
                :driver_id, :title, :description, :preferred_location, 
                :preferred_job_type, :preferred_vehicle_type, 
                :expected_salary_min, :expected_salary_max, 
                :availability_start_date, :availability_end_date, 
                :is_active, NOW(), NOW()
            )
        ');

        $stmt->execute([
            'driver_id' => $driverId,
            'title' => 'Έμπειρος οδηγός φορτηγού αναζητά εργασία',
            'description' => 'Είμαι έμπειρος οδηγός φορτηγού με 10+ χρόνια εμπειρίας. Διαθέτω όλα τα απαραίτητα διπλώματα και πιστοποιήσεις. Αναζητώ εργασία πλήρους απασχόλησης στην περιοχή της Θεσσαλονίκης.',
            'preferred_location' => 'Θεσσαλονίκη',
            'preferred_job_type' => 'full_time',
            'preferred_vehicle_type' => 'truck',
            'expected_salary_min' => 1200,
            'expected_salary_max' => 1800,
            'availability_start_date' => date('Y-m-d'),
            'availability_end_date' => date('Y-m-d', strtotime('+6 months')),
            'is_active' => 1
        ]);

        echo "Driver job listing added successfully with ID: " . $pdo->lastInsertId() . "\n";
    } catch (PDOException $e) {
        echo "Error adding driver job listing: " . $e->getMessage() . "\n";
    }
}

// Προσθέτουμε αγγελία για την επιχείρηση
if ($companyId) {
    try {
        $stmt = $pdo->prepare('
            INSERT INTO job_listings (
                company_id, title, description, location, 
                job_type, vehicle_type, salary_min, salary_max, 
                required_experience, required_license_type, 
                is_active, created_at, updated_at
            ) VALUES (
                :company_id, :title, :description, :location, 
                :job_type, :vehicle_type, :salary_min, :salary_max, 
                :required_experience, :required_license_type, 
                :is_active, NOW(), NOW()
            )
        ');

        $stmt->execute([
            'company_id' => $companyId,
            'title' => 'Ζητείται οδηγός φορτηγού για διεθνείς μεταφορές',
            'description' => 'Η εταιρεία μας αναζητά έμπειρο οδηγό φορτηγού για διεθνείς μεταφορές. Απαραίτητα προσόντα: Δίπλωμα Γ+Ε κατηγορίας, ΠΕΙ, κάρτα ταχογράφου και τουλάχιστον 3 χρόνια εμπειρία σε διεθνείς μεταφορές.',
            'location' => 'Θεσσαλονίκη',
            'job_type' => 'full_time',
            'vehicle_type' => 'truck',
            'salary_min' => 1500,
            'salary_max' => 2000,
            'required_experience' => 3,
            'required_license_type' => 'C+E',
            'is_active' => 1
        ]);

        $jobListingId = $pdo->lastInsertId();
        echo "Company job listing added successfully with ID: " . $jobListingId . "\n";

        // Προσθέτουμε μερικά tags για την αγγελία
        $tags = ['Διεθνείς Μεταφορές', 'Φορτηγό', 'Πλήρης Απασχόληση'];
        foreach ($tags as $tag) {
            $stmt = $pdo->prepare('
                INSERT INTO job_tags (job_listing_id, tag_name, created_at)
                VALUES (:job_listing_id, :tag_name, NOW())
            ');
            $stmt->execute([
                'job_listing_id' => $jobListingId,
                'tag_name' => $tag
            ]);
        }
        echo "Added tags to the company job listing\n";
    } catch (PDOException $e) {
        echo "Error adding company job listing: " . $e->getMessage() . "\n";
    }
}

// Προσθέτουμε μια δεύτερη αγγελία για την επιχείρηση
if ($companyId) {
    try {
        $stmt = $pdo->prepare('
            INSERT INTO job_listings (
                company_id, title, description, location, 
                job_type, vehicle_type, salary_min, salary_max, 
                required_experience, required_license_type, 
                is_active, created_at, updated_at
            ) VALUES (
                :company_id, :title, :description, :location, 
                :job_type, :vehicle_type, :salary_min, :salary_max, 
                :required_experience, :required_license_type, 
                :is_active, NOW(), NOW()
            )
        ');

        $stmt->execute([
            'company_id' => $companyId,
            'title' => 'Ζητείται οδηγός βαν για τοπικές διανομές',
            'description' => 'Η εταιρεία μας αναζητά οδηγό βαν για τοπικές διανομές στην περιοχή της Θεσσαλονίκης. Απαραίτητα προσόντα: Δίπλωμα Β κατηγορίας και τουλάχιστον 1 χρόνο εμπειρία σε διανομές.',
            'location' => 'Θεσσαλονίκη',
            'job_type' => 'part_time',
            'vehicle_type' => 'van',
            'salary_min' => 800,
            'salary_max' => 1000,
            'required_experience' => 1,
            'required_license_type' => 'B',
            'is_active' => 1
        ]);

        $jobListingId = $pdo->lastInsertId();
        echo "Second company job listing added successfully with ID: " . $jobListingId . "\n";

        // Προσθέτουμε μερικά tags για την αγγελία
        $tags = ['Τοπικές Διανομές', 'Βαν', 'Μερική Απασχόληση'];
        foreach ($tags as $tag) {
            $stmt = $pdo->prepare('
                INSERT INTO job_tags (job_listing_id, tag_name, created_at)
                VALUES (:job_listing_id, :tag_name, NOW())
            ');
            $stmt->execute([
                'job_listing_id' => $jobListingId,
                'tag_name' => $tag
            ]);
        }
        echo "Added tags to the second company job listing\n";
    } catch (PDOException $e) {
        echo "Error adding second company job listing: " . $e->getMessage() . "\n";
    }
}

// Προσθέτουμε μια δεύτερη αγγελία για τον οδηγό
if ($driverId) {
    try {
        $stmt = $pdo->prepare('
            INSERT INTO driver_job_listings (
                driver_id, title, description, preferred_location, 
                preferred_job_type, preferred_vehicle_type, 
                expected_salary_min, expected_salary_max, 
                availability_start_date, availability_end_date, 
                is_active, created_at, updated_at
            ) VALUES (
                :driver_id, :title, :description, :preferred_location, 
                :preferred_job_type, :preferred_vehicle_type, 
                :expected_salary_min, :expected_salary_max, 
                :availability_start_date, :availability_end_date, 
                :is_active, NOW(), NOW()
            )
        ');

        $stmt->execute([
            'driver_id' => $driverId,
            'title' => 'Οδηγός βαν αναζητά εργασία μερικής απασχόλησης',
            'description' => 'Είμαι οδηγός βαν με 5 χρόνια εμπειρίας σε τοπικές διανομές. Αναζητώ εργασία μερικής απασχόλησης στην περιοχή της Θεσσαλονίκης.',
            'preferred_location' => 'Θεσσαλονίκη',
            'preferred_job_type' => 'part_time',
            'preferred_vehicle_type' => 'van',
            'expected_salary_min' => 700,
            'expected_salary_max' => 900,
            'availability_start_date' => date('Y-m-d'),
            'availability_end_date' => date('Y-m-d', strtotime('+3 months')),
            'is_active' => 1
        ]);

        echo "Second driver job listing added successfully with ID: " . $pdo->lastInsertId() . "\n";
    } catch (PDOException $e) {
        echo "Error adding second driver job listing: " . $e->getMessage() . "\n";
    }
}

// Δημιουργούμε ταιριάσματα μεταξύ των αγγελιών
if ($driverId && $companyId) {
    try {
        // Παίρνουμε τις αγγελίες του οδηγού
        $stmt = $pdo->prepare('SELECT id FROM driver_job_listings WHERE driver_id = ?');
        $stmt->execute([$driverId]);
        $driverListings = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Παίρνουμε τις αγγελίες της επιχείρησης
        $stmt = $pdo->prepare('SELECT id FROM job_listings WHERE company_id = ?');
        $stmt->execute([$companyId]);
        $companyListings = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Δημιουργούμε ταιριάσματα
        foreach ($driverListings as $driverListingId) {
            foreach ($companyListings as $companyListingId) {
                // Υπολογίζουμε ένα τυχαίο σκορ ταιριάσματος μεταξύ 60 και 95
                $matchScore = rand(60, 95);

                $stmt = $pdo->prepare('
                    INSERT INTO job_matches (
                        driver_job_listing_id, company_job_listing_id, 
                        match_score, created_at, updated_at
                    ) VALUES (
                        :driver_job_listing_id, :company_job_listing_id, 
                        :match_score, NOW(), NOW()
                    )
                ');

                $stmt->execute([
                    'driver_job_listing_id' => $driverListingId,
                    'company_job_listing_id' => $companyListingId,
                    'match_score' => $matchScore
                ]);

                echo "Created match between driver listing $driverListingId and company listing $companyListingId with score $matchScore\n";
            }
        }
    } catch (PDOException $e) {
        echo "Error creating matches: " . $e->getMessage() . "\n";
    }
}

echo "Done!\n";
