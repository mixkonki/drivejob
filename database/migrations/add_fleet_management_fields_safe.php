<?php
// Migration για προσθήκη πεδίων διαχείρισης στόλου και οδηγών στις εταιρείες

// Απευθείας σύνδεση στη βάση
$host = 'localhost';
$dbname = 'drivejob';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Έλεγχος και προσθήκη νέων πεδίων για διαχείριση στόλου και οδηγών...\n\n";

    // Έλεγχος ποια πεδία υπάρχουν ήδη
    $stmt = $pdo->query("SHOW COLUMNS FROM companies");
    $existingColumns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $row['Field'];
    }

    // Λίστα νέων πεδίων
    $newColumns = [
        'fleet_size' => "ADD COLUMN fleet_size INT DEFAULT 0 COMMENT 'Μέγεθος στόλου οχημάτων'",
        'active_drivers' => "ADD COLUMN active_drivers INT DEFAULT 0 COMMENT 'Αριθμός ενεργών οδηγών'",
        'has_hr_system' => "ADD COLUMN has_hr_system BOOLEAN DEFAULT FALSE COMMENT 'Διαθέτει σύστημα HR'",
        'has_payroll_system' => "ADD COLUMN has_payroll_system BOOLEAN DEFAULT FALSE COMMENT 'Διαθέτει σύστημα μισθοδοσίας'",
        'has_training_program' => "ADD COLUMN has_training_program BOOLEAN DEFAULT FALSE COMMENT 'Διαθέτει πρόγραμμα εκπαίδευσης'",
        'has_fleet_management' => "ADD COLUMN has_fleet_management BOOLEAN DEFAULT FALSE COMMENT 'Χρησιμοποιεί σύστημα διαχείρισης στόλου'",
        'has_telematics' => "ADD COLUMN has_telematics BOOLEAN DEFAULT FALSE COMMENT 'Χρησιμοποιεί telematics'",
        'has_route_optimization' => "ADD COLUMN has_route_optimization BOOLEAN DEFAULT FALSE COMMENT 'Χρησιμοποιεί βελτιστοποίηση διαδρομών'",
        'maintenance_provider' => "ADD COLUMN maintenance_provider VARCHAR(255) DEFAULT NULL COMMENT 'Πάροχος συντήρησης'",
        'has_legal_support' => "ADD COLUMN has_legal_support BOOLEAN DEFAULT FALSE COMMENT 'Διαθέτει νομική υποστήριξη'",
        'compliance_certifications' => "ADD COLUMN compliance_certifications TEXT DEFAULT NULL COMMENT 'Πιστοποιήσεις συμμόρφωσης (JSON)'",
        'operates_internationally' => "ADD COLUMN operates_internationally BOOLEAN DEFAULT FALSE COMMENT 'Δραστηριοποιείται διεθνώς'",
        'operating_countries' => "ADD COLUMN operating_countries TEXT DEFAULT NULL COMMENT 'Χώρες δραστηριοποίησης (JSON)'",
        'transport_types' => "ADD COLUMN transport_types TEXT DEFAULT NULL COMMENT 'Τύποι μεταφορών (JSON)'",
        'specializations' => "ADD COLUMN specializations TEXT DEFAULT NULL COMMENT 'Εξειδικεύσεις (ADR, ATP, κλπ) (JSON)'",
        'subscription_plan' => "ADD COLUMN subscription_plan ENUM('basic', 'professional', 'enterprise', 'custom') DEFAULT 'basic'",
        'subscription_expires_at' => "ADD COLUMN subscription_expires_at DATETIME DEFAULT NULL",
        'enabled_modules' => "ADD COLUMN enabled_modules TEXT DEFAULT NULL COMMENT 'Ενεργά modules (JSON)'",
        'monthly_job_posts' => "ADD COLUMN monthly_job_posts INT DEFAULT 0 COMMENT 'Μηνιαίες αγγελίες'",
        'successful_hires' => "ADD COLUMN successful_hires INT DEFAULT 0 COMMENT 'Επιτυχημένες προσλήψεις'",
        'average_hiring_time' => "ADD COLUMN average_hiring_time INT DEFAULT NULL COMMENT 'Μέσος χρόνος πρόσληψης σε ημέρες'",
        'fleet_management_since' => "ADD COLUMN fleet_management_since DATETIME DEFAULT NULL",
        'hr_system_since' => "ADD COLUMN hr_system_since DATETIME DEFAULT NULL"
    ];

    // Προσθήκη μόνο των πεδίων που δεν υπάρχουν
    $addedColumns = 0;
    foreach ($newColumns as $columnName => $columnDefinition) {
        if (!in_array($columnName, $existingColumns)) {
            try {
                $sql = "ALTER TABLE companies " . $columnDefinition;
                $pdo->exec($sql);
                echo "✓ Προστέθηκε το πεδίο: $columnName\n";
                $addedColumns++;
            } catch (PDOException $e) {
                echo "✗ Σφάλμα στο πεδίο $columnName: " . $e->getMessage() . "\n";
            }
        } else {
            echo "- Το πεδίο $columnName υπάρχει ήδη\n";
        }
    }

    // Έλεγχος για updated_at
    if (!in_array('updated_at', $existingColumns)) {
        try {
            $pdo->exec("ALTER TABLE companies ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            echo "✓ Προστέθηκε το πεδίο: updated_at\n";
            $addedColumns++;
        } catch (PDOException $e) {
            echo "✗ Σφάλμα στο πεδίο updated_at: " . $e->getMessage() . "\n";
        }
    }

    echo "\n✅ Προστέθηκαν $addedColumns νέα πεδία στον πίνακα companies\n\n";

    // Δημιουργία πίνακα για fleet vehicles
    echo "Έλεγχος πίνακα company_fleet_vehicles...\n";
    $tableExists = $pdo->query("SHOW TABLES LIKE 'company_fleet_vehicles'")->rowCount() > 0;

    if (!$tableExists) {
        $createFleetTableSQL = "
            CREATE TABLE company_fleet_vehicles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_id INT NOT NULL,
                vehicle_type VARCHAR(50) NOT NULL,
                license_plate VARCHAR(20) UNIQUE NOT NULL,
                brand VARCHAR(50),
                model VARCHAR(50),
                year INT,
                capacity_tons DECIMAL(10,2),
                fuel_type ENUM('diesel', 'petrol', 'electric', 'hybrid', 'lng', 'cng') DEFAULT 'diesel',
                euro_class VARCHAR(10),
                next_service_date DATE,
                next_kteo_date DATE,
                insurance_expires DATE,
                is_active BOOLEAN DEFAULT TRUE,
                telematics_id VARCHAR(100),
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
                INDEX idx_company_fleet (company_id),
                INDEX idx_active_vehicles (company_id, is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        $pdo->exec($createFleetTableSQL);
        echo "✓ Δημιουργήθηκε πίνακας company_fleet_vehicles\n";
    } else {
        echo "- Ο πίνακας company_fleet_vehicles υπάρχει ήδη\n";
    }

    // Δημιουργία πίνακα για driver management
    echo "\nΈλεγχος πίνακα company_driver_management...\n";
    $tableExists = $pdo->query("SHOW TABLES LIKE 'company_driver_management'")->rowCount() > 0;

    if (!$tableExists) {
        $createDriverManagementSQL = "
            CREATE TABLE company_driver_management (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_id INT NOT NULL,
                driver_id INT NOT NULL,
                employee_code VARCHAR(50),
                hire_date DATE NOT NULL,
                contract_type ENUM('permanent', 'temporary', 'seasonal', 'contractor') DEFAULT 'permanent',
                contract_expires DATE,
                base_salary DECIMAL(10,2),
                pay_per_km DECIMAL(10,4),
                monthly_km_target INT,
                performance_rating DECIMAL(3,2),
                last_evaluation_date DATE,
                next_training_date DATE,
                assigned_vehicle_id INT,
                is_active BOOLEAN DEFAULT TRUE,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
                FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
                FOREIGN KEY (assigned_vehicle_id) REFERENCES company_fleet_vehicles(id) ON DELETE SET NULL,
                UNIQUE KEY unique_company_driver (company_id, driver_id),
                INDEX idx_active_drivers (company_id, is_active),
                INDEX idx_driver_performance (company_id, performance_rating)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        $pdo->exec($createDriverManagementSQL);
        echo "✓ Δημιουργήθηκε πίνακας company_driver_management\n";
    } else {
        echo "- Ο πίνακας company_driver_management υπάρχει ήδη\n";
    }

    // Δημιουργία πίνακα για compliance tracking
    echo "\nΈλεγχος πίνακα company_compliance_tracking...\n";
    $tableExists = $pdo->query("SHOW TABLES LIKE 'company_compliance_tracking'")->rowCount() > 0;

    if (!$tableExists) {
        $createComplianceTableSQL = "
            CREATE TABLE company_compliance_tracking (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_id INT NOT NULL,
                compliance_type VARCHAR(100) NOT NULL,
                country_code VARCHAR(2),
                certification_name VARCHAR(255),
                issued_date DATE,
                expires_date DATE,
                document_path VARCHAR(500),
                status ENUM('active', 'expired', 'pending_renewal') DEFAULT 'active',
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
                INDEX idx_company_compliance (company_id, status),
                INDEX idx_expiring_soon (expires_date, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        $pdo->exec($createComplianceTableSQL);
        echo "✓ Δημιουργήθηκε πίνακας company_compliance_tracking\n";
    } else {
        echo "- Ο πίνακας company_compliance_tracking υπάρχει ήδη\n";
    }

    echo "\n✅ Η migration ολοκληρώθηκε επιτυχώς!\n";
} catch (PDOException $e) {
    echo "❌ Σφάλμα σύνδεσης: " . $e->getMessage() . "\n";
    exit(1);
}
