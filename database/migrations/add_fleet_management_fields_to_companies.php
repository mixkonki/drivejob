<?php
// Migration για προσθήκη πεδίων διαχείρισης στόλου και οδηγών στις εταιρείες

require_once __DIR__ . '/../../src/bootstrap.php';

try {
    $pdo = $GLOBALS['pdo'];

    echo "Προσθήκη νέων πεδίων για διαχείριση στόλου και οδηγών...\n";

    // Προσθήκη νέων στηλών στον πίνακα companies
    $alterTableSQL = "
        ALTER TABLE companies 
        -- Πεδία για DriveManager Pro
        ADD COLUMN fleet_size INT DEFAULT 0 COMMENT 'Μέγεθος στόλου οχημάτων',
        ADD COLUMN active_drivers INT DEFAULT 0 COMMENT 'Αριθμός ενεργών οδηγών',
        ADD COLUMN has_hr_system BOOLEAN DEFAULT FALSE COMMENT 'Διαθέτει σύστημα HR',
        ADD COLUMN has_payroll_system BOOLEAN DEFAULT FALSE COMMENT 'Διαθέτει σύστημα μισθοδοσίας',
        ADD COLUMN has_training_program BOOLEAN DEFAULT FALSE COMMENT 'Διαθέτει πρόγραμμα εκπαίδευσης',
        
        -- Πεδία για DriveFleet Solutions
        ADD COLUMN has_fleet_management BOOLEAN DEFAULT FALSE COMMENT 'Χρησιμοποιεί σύστημα διαχείρισης στόλου',
        ADD COLUMN has_telematics BOOLEAN DEFAULT FALSE COMMENT 'Χρησιμοποιεί telematics',
        ADD COLUMN has_route_optimization BOOLEAN DEFAULT FALSE COMMENT 'Χρησιμοποιεί βελτιστοποίηση διαδρομών',
        ADD COLUMN maintenance_provider VARCHAR(255) DEFAULT NULL COMMENT 'Πάροχος συντήρησης',
        
        -- Πεδία για Compliance & Legal
        ADD COLUMN has_legal_support BOOLEAN DEFAULT FALSE COMMENT 'Διαθέτει νομική υποστήριξη',
        ADD COLUMN compliance_certifications TEXT DEFAULT NULL COMMENT 'Πιστοποιήσεις συμμόρφωσης (JSON)',
        ADD COLUMN operates_internationally BOOLEAN DEFAULT FALSE COMMENT 'Δραστηριοποιείται διεθνώς',
        ADD COLUMN operating_countries TEXT DEFAULT NULL COMMENT 'Χώρες δραστηριοποίησης (JSON)',
        
        -- Πεδία για τύπους μεταφορών
        ADD COLUMN transport_types TEXT DEFAULT NULL COMMENT 'Τύποι μεταφορών (JSON)',
        ADD COLUMN specializations TEXT DEFAULT NULL COMMENT 'Εξειδικεύσεις (ADR, ATP, κλπ) (JSON)',
        
        -- Πεδία για subscription/services
        ADD COLUMN subscription_plan ENUM('basic', 'professional', 'enterprise', 'custom') DEFAULT 'basic',
        ADD COLUMN subscription_expires_at DATETIME DEFAULT NULL,
        ADD COLUMN enabled_modules TEXT DEFAULT NULL COMMENT 'Ενεργά modules (JSON)',
        
        -- Πεδία για στατιστικά
        ADD COLUMN monthly_job_posts INT DEFAULT 0 COMMENT 'Μηνιαίες αγγελίες',
        ADD COLUMN successful_hires INT DEFAULT 0 COMMENT 'Επιτυχημένες προσλήψεις',
        ADD COLUMN average_hiring_time INT DEFAULT NULL COMMENT 'Μέσος χρόνος πρόσληψης σε ημέρες',
        
        -- Timestamps για features
        ADD COLUMN fleet_management_since DATETIME DEFAULT NULL,
        ADD COLUMN hr_system_since DATETIME DEFAULT NULL,
        ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ";

    $pdo->exec($alterTableSQL);
    echo "✓ Προστέθηκαν νέα πεδία στον πίνακα companies\n";

    // Δημιουργία πίνακα για fleet vehicles
    $createFleetTableSQL = "
        CREATE TABLE IF NOT EXISTS company_fleet_vehicles (
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

    // Δημιουργία πίνακα για driver management
    $createDriverManagementSQL = "
        CREATE TABLE IF NOT EXISTS company_driver_management (
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

    // Δημιουργία πίνακα για compliance tracking
    $createComplianceTableSQL = "
        CREATE TABLE IF NOT EXISTS company_compliance_tracking (
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

    echo "\n✅ Η migration ολοκληρώθηκε επιτυχώς!\n";
} catch (PDOException $e) {
    echo "❌ Σφάλμα: " . $e->getMessage() . "\n";
    exit(1);
}
