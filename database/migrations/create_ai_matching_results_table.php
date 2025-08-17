<?php
require_once __DIR__ . '/../../src/bootstrap.php';

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    echo "🤖 ΔΗΜΙΟΥΡΓΙΑ AI MATCHING RESULTS TABLE\n";
    echo "=====================================\n\n";

    // Create ai_matching_results table
    $sql = "
    CREATE TABLE IF NOT EXISTS ai_matching_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        driver_id INT NOT NULL,
        job_id INT NOT NULL,
        overall_score DECIMAL(5,2) DEFAULT 0,
        detailed_scores JSON,
        ai_insights JSON,
        ai_suggestions JSON,
        ai_model VARCHAR(50) DEFAULT 'o1-preview',
        processing_time_ms INT DEFAULT 0,
        api_cost DECIMAL(8,4) DEFAULT 0,
        confidence_level DECIMAL(3,2) DEFAULT 0.85,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        UNIQUE KEY unique_driver_job (driver_id, job_id),
        INDEX idx_driver_id (driver_id),
        INDEX idx_job_id (job_id),
        INDEX idx_overall_score (overall_score),
        INDEX idx_created_at (created_at),
        
        FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
        FOREIGN KEY (job_id) REFERENCES job_listings(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);
    echo "✅ ai_matching_results table created successfully\n\n";

    // Create ai_driver_insights table
    $sql = "
    CREATE TABLE IF NOT EXISTS ai_driver_insights (
        id INT AUTO_INCREMENT PRIMARY KEY,
        driver_id INT NOT NULL,
        career_insights JSON,
        market_analysis JSON,
        skill_recommendations JSON,
        salary_optimization JSON,
        location_strategy JSON,
        next_steps JSON,
        ai_model VARCHAR(50) DEFAULT 'o1-mini',
        generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL,
        is_active BOOLEAN DEFAULT TRUE,
        
        INDEX idx_driver_id (driver_id),
        INDEX idx_generated_at (generated_at),
        INDEX idx_expires_at (expires_at),
        
        FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);
    echo "✅ ai_driver_insights table created successfully\n\n";

    // Create ai_job_analysis table
    $sql = "
    CREATE TABLE IF NOT EXISTS ai_job_analysis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        required_skills JSON,
        experience_level VARCHAR(20),
        vehicle_types JSON,
        work_schedule JSON,
        travel_requirements JSON,
        physical_demands JSON,
        company_culture JSON,
        growth_opportunities JSON,
        ai_model VARCHAR(50) DEFAULT 'gpt-4o',
        analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        UNIQUE KEY unique_job_analysis (job_id),
        INDEX idx_job_id (job_id),
        INDEX idx_experience_level (experience_level),
        
        FOREIGN KEY (job_id) REFERENCES job_listings(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);
    echo "✅ ai_job_analysis table created successfully\n\n";

    // Create ai_usage_stats table for tracking API usage
    $sql = "
    CREATE TABLE IF NOT EXISTS ai_usage_stats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATE NOT NULL,
        model VARCHAR(50) NOT NULL,
        total_requests INT DEFAULT 0,
        total_tokens INT DEFAULT 0,
        total_cost DECIMAL(10,4) DEFAULT 0,
        avg_response_time_ms INT DEFAULT 0,
        success_rate DECIMAL(5,2) DEFAULT 100.00,
        
        UNIQUE KEY unique_date_model (date, model),
        INDEX idx_date (date),
        INDEX idx_model (model)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);
    echo "✅ ai_usage_stats table created successfully\n\n";

    // Insert sample data for testing
    echo "📊 Inserting sample AI data...\n";

    // Sample AI insights for driver 26
    $sampleInsights = [
        'career_insights' => [
            'Έχετε 8 χρόνια εμπειρίας που σας κατατάσσει στην κατηγορία Senior Driver',
            'Η τοποθεσία σας στη Θεσσαλονίκη είναι στρατηγική για βόρεια Ελλάδα',
            'Οι άδειες σας καλύπτουν 85% των διαθέσιμων θέσεων',
            'Το προφίλ σας ταιριάζει καλύτερα σε διεθνείς μεταφορές',
            'Υπάρχει δυναμικό για 15-20% αύξηση μισθού'
        ],
        'market_analysis' => [
            'trend' => 'Αυξημένη ζήτηση για έμπειρους οδηγούς φορτηγών',
            'salary_range' => '1800-2500€ για Senior drivers στη Θεσσαλονίκη',
            'growth_sectors' => ['Διεθνείς μεταφορές', 'E-commerce logistics', 'Cold chain']
        ],
        'skill_recommendations' => [
            'ADR πιστοποίηση για επικίνδυνα φορτία',
            'Ψηφιακές δεξιότητες (GPS, fleet management)',
            'Αγγλικά για διεθνείς διαδρομές'
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO ai_driver_insights 
        (driver_id, career_insights, market_analysis, skill_recommendations, expires_at)
        VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
        ON DUPLICATE KEY UPDATE
            career_insights = VALUES(career_insights),
            market_analysis = VALUES(market_analysis),
            skill_recommendations = VALUES(skill_recommendations)
    ");

    $stmt->execute([
        26,
        json_encode($sampleInsights['career_insights']),
        json_encode($sampleInsights['market_analysis']),
        json_encode($sampleInsights['skill_recommendations'])
    ]);

    echo "✅ Sample AI insights inserted for driver 26\n";

    // Sample job analysis
    $sampleJobAnalysis = [
        'required_skills' => ['C+E άδεια', 'Εμπειρία διεθνών μεταφορών', 'Αγγλικά'],
        'experience_level' => 'Senior',
        'vehicle_types' => ['truck', 'trailer'],
        'work_schedule' => ['full_time', 'international_routes'],
        'travel_requirements' => ['EU countries', '7-14 days trips'],
        'physical_demands' => ['Heavy lifting', 'Long driving hours'],
        'company_culture' => ['Family business', 'Supportive environment'],
        'growth_opportunities' => ['Fleet manager position', 'Training programs']
    ];

    $stmt = $pdo->prepare("
        INSERT INTO ai_job_analysis 
        (job_id, required_skills, experience_level, vehicle_types, work_schedule, 
         travel_requirements, physical_demands, company_culture, growth_opportunities)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            required_skills = VALUES(required_skills),
            experience_level = VALUES(experience_level)
    ");

    $stmt->execute([
        11, // Job ID
        json_encode($sampleJobAnalysis['required_skills']),
        $sampleJobAnalysis['experience_level'],
        json_encode($sampleJobAnalysis['vehicle_types']),
        json_encode($sampleJobAnalysis['work_schedule']),
        json_encode($sampleJobAnalysis['travel_requirements']),
        json_encode($sampleJobAnalysis['physical_demands']),
        json_encode($sampleJobAnalysis['company_culture']),
        json_encode($sampleJobAnalysis['growth_opportunities'])
    ]);

    echo "✅ Sample job analysis inserted for job 11\n";

    // Initialize usage stats
    $stmt = $pdo->prepare("
        INSERT INTO ai_usage_stats (date, model, total_requests, total_tokens, total_cost)
        VALUES (CURDATE(), 'o1-preview', 0, 0, 0.00)
        ON DUPLICATE KEY UPDATE total_requests = total_requests
    ");
    $stmt->execute();

    echo "✅ AI usage stats initialized\n\n";

    echo "🎉 AI Matching System database setup completed!\n";
    echo "Ready to use ChatGPT-5 for advanced matching!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
