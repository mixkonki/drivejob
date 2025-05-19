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

// Προσθέτουμε αγγελία αναζήτησης εργασίας για τον οδηγό (job_search)
if ($driverId) {
    try {
        $stmt = $pdo->prepare('
            INSERT INTO job_listings (
                driver_id, title, description, location, 
                listing_type, transport_type, job_type, 
                salary_min, salary_max, salary_type,
                vehicle_types, experience_years, 
                is_active, created_at, updated_at
            ) VALUES (
                :driver_id, :title, :description, :location, 
                :listing_type, :transport_type, :job_type, 
                :salary_min, :salary_max, :salary_type,
                :vehicle_types, :experience_years, 
                :is_active, NOW(), NOW()
            )
        ');

        $stmt->execute([
            'driver_id' => $driverId,
            'title' => 'Έμπειρος οδηγός φορτηγού αναζητά εργασία',
            'description' => 'Είμαι έμπειρος οδηγός φορτηγού με 10+ χρόνια εμπειρίας. Διαθέτω όλα τα απαραίτητα διπλώματα και πιστοποιήσεις. Αναζητώ εργασία πλήρους απασχόλησης στην περιοχή της Θεσσαλονίκης.',
            'location' => 'Θεσσαλονίκη',
            'listing_type' => 'job_search',
            'transport_type' => 'freight',
            'job_type' => 'full_time',
            'salary_min' => 1200,
            'salary_max' => 1800,
            'salary_type' => 'monthly',
            'vehicle_types' => 'truck',
            'experience_years' => 10,
            'is_active' => 1
        ]);

        echo "Driver job search listing added successfully with ID: " . $pdo->lastInsertId() . "\n";
    } catch (PDOException $e) {
        echo "Error adding driver job search listing: " . $e->getMessage() . "\n";
    }
}

// Προσθέτουμε αγγελία προσφοράς εργασίας για την επιχείρηση (job_offer)
if ($companyId) {
    try {
        $stmt = $pdo->prepare('
            INSERT INTO job_listings (
                company_id, title, description, location, 
                listing_type, transport_type, job_type, 
                salary_min, salary_max, salary_type,
                vehicle_types, required_license, experience_years, 
                is_active, created_at, updated_at
            ) VALUES (
                :company_id, :title, :description, :location, 
                :listing_type, :transport_type, :job_type, 
                :salary_min, :salary_max, :salary_type,
                :vehicle_types, :required_license, :experience_years, 
                :is_active, NOW(), NOW()
            )
        ');

        $stmt->execute([
            'company_id' => $companyId,
            'title' => 'Ζητείται οδηγός φορτηγού για διεθνείς μεταφορές',
            'description' => 'Η εταιρεία μας αναζητά έμπειρο οδηγό φορτηγού για διεθνείς μεταφορές. Απαραίτητα προσόντα: Δίπλωμα Γ+Ε κατηγορίας, ΠΕΙ, κάρτα ταχογράφου και τουλάχιστον 3 χρόνια εμπειρία σε διεθνείς μεταφορές.',
            'location' => 'Θεσσαλονίκη',
            'listing_type' => 'job_offer',
            'transport_type' => 'freight',
            'job_type' => 'full_time',
            'salary_min' => 1500,
            'salary_max' => 2000,
            'salary_type' => 'monthly',
            'vehicle_types' => 'truck',
            'required_license' => 'C+E',
            'experience_years' => 3,
            'is_active' => 1
        ]);

        $jobListingId = $pdo->lastInsertId();
        echo "Company job offer listing added successfully with ID: " . $jobListingId . "\n";
    } catch (PDOException $e) {
        echo "Error adding company job offer listing: " . $e->getMessage() . "\n";
    }
}

// Προσθέτουμε μια δεύτερη αγγελία προσφοράς εργασίας για την επιχείρηση (job_offer)
if ($companyId) {
    try {
        $stmt = $pdo->prepare('
            INSERT INTO job_listings (
                company_id, title, description, location, 
                listing_type, transport_type, job_type, 
                salary_min, salary_max, salary_type,
                vehicle_types, required_license, experience_years, 
                is_active, created_at, updated_at
            ) VALUES (
                :company_id, :title, :description, :location, 
                :listing_type, :transport_type, :job_type, 
                :salary_min, :salary_max, :salary_type,
                :vehicle_types, :required_license, :experience_years, 
                :is_active, NOW(), NOW()
            )
        ');

        $stmt->execute([
            'company_id' => $companyId,
            'title' => 'Ζητείται οδηγός βαν για τοπικές διανομές',
            'description' => 'Η εταιρεία μας αναζητά οδηγό βαν για τοπικές διανομές στην περιοχή της Θεσσαλονίκης. Απαραίτητα προσόντα: Δίπλωμα Β κατηγορίας και τουλάχιστον 1 χρόνο εμπειρία σε διανομές.',
            'location' => 'Θεσσαλονίκη',
            'listing_type' => 'job_offer',
            'transport_type' => 'freight',
            'job_type' => 'part_time',
            'salary_min' => 800,
            'salary_max' => 1000,
            'salary_type' => 'monthly',
            'vehicle_types' => 'van',
            'required_license' => 'B',
            'experience_years' => 1,
            'is_active' => 1
        ]);

        $jobListingId = $pdo->lastInsertId();
        echo "Second company job offer listing added successfully with ID: " . $jobListingId . "\n";
    } catch (PDOException $e) {
        echo "Error adding second company job offer listing: " . $e->getMessage() . "\n";
    }
}

// Προσθέτουμε μια δεύτερη αγγελία αναζήτησης εργασίας για τον οδηγό (job_search)
if ($driverId) {
    try {
        $stmt = $pdo->prepare('
            INSERT INTO job_listings (
                driver_id, title, description, location, 
                listing_type, transport_type, job_type, 
                salary_min, salary_max, salary_type,
                vehicle_types, experience_years, 
                is_active, created_at, updated_at
            ) VALUES (
                :driver_id, :title, :description, :location, 
                :listing_type, :transport_type, :job_type, 
                :salary_min, :salary_max, :salary_type,
                :vehicle_types, :experience_years, 
                :is_active, NOW(), NOW()
            )
        ');

        $stmt->execute([
            'driver_id' => $driverId,
            'title' => 'Οδηγός βαν αναζητά εργασία μερικής απασχόλησης',
            'description' => 'Είμαι οδηγός βαν με 5 χρόνια εμπειρίας σε τοπικές διανομές. Αναζητώ εργασία μερικής απασχόλησης στην περιοχή της Θεσσαλονίκης.',
            'location' => 'Θεσσαλονίκη',
            'listing_type' => 'job_search',
            'transport_type' => 'freight',
            'job_type' => 'part_time',
            'salary_min' => 700,
            'salary_max' => 900,
            'salary_type' => 'monthly',
            'vehicle_types' => 'van',
            'experience_years' => 5,
            'is_active' => 1
        ]);

        echo "Second driver job search listing added successfully with ID: " . $pdo->lastInsertId() . "\n";
    } catch (PDOException $e) {
        echo "Error adding second driver job search listing: " . $e->getMessage() . "\n";
    }
}

// Δημιουργούμε αιτήσεις εργασίας (job_applications)
if ($driverId && $companyId) {
    try {
        // Παίρνουμε τις αγγελίες προσφοράς εργασίας της επιχείρησης
        $stmt = $pdo->prepare('SELECT id FROM job_listings WHERE company_id = ? AND listing_type = "job_offer"');
        $stmt->execute([$companyId]);
        $companyListings = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Δημιουργούμε αιτήσεις εργασίας
        foreach ($companyListings as $jobListingId) {
            $stmt = $pdo->prepare('
                INSERT INTO job_applications (
                    job_listing_id, driver_id, message, status, created_at, updated_at
                ) VALUES (
                    :job_listing_id, :driver_id, :message, :status, NOW(), NOW()
                )
            ');

            $stmt->execute([
                'job_listing_id' => $jobListingId,
                'driver_id' => $driverId,
                'message' => 'Ενδιαφέρομαι για τη θέση εργασίας και θα ήθελα να συζητήσουμε περισσότερες λεπτομέρειες.',
                'status' => 'pending'
            ]);

            echo "Created job application for job listing $jobListingId\n";
        }
    } catch (PDOException $e) {
        echo "Error creating job applications: " . $e->getMessage() . "\n";
    }
}

echo "Done!\n";
