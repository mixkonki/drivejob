<?php

require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Database;

class CreateAIMatchingTables
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function up()
    {
        try {
            // Create driver_skills_embeddings table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS driver_skills_embeddings (
                    driver_id INT PRIMARY KEY,
                    skill_vector JSON,
                    experience_vector JSON,
                    location_data JSON,
                    availability_score DECIMAL(3,2),
                    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
                    INDEX idx_updated (last_updated)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Create job_requirements_embeddings table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS job_requirements_embeddings (
                    job_id INT PRIMARY KEY,
                    requirement_vector JSON,
                    location_data JSON,
                    urgency_score DECIMAL(3,2),
                    company_preferences JSON,
                    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (job_id) REFERENCES job_listings(id) ON DELETE CASCADE,
                    INDEX idx_updated (last_updated)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Create matching_scores table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS matching_scores (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    driver_id INT NOT NULL,
                    job_id INT NOT NULL,
                    overall_score DECIMAL(5,4) NOT NULL,
                    skill_match_score DECIMAL(5,4),
                    location_match_score DECIMAL(5,4),
                    experience_match_score DECIMAL(5,4),
                    availability_match_score DECIMAL(5,4),
                    factors JSON,
                    ml_confidence DECIMAL(5,4),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_score (overall_score DESC),
                    INDEX idx_driver_job (driver_id, job_id),
                    INDEX idx_created (created_at),
                    UNIQUE KEY unique_driver_job (driver_id, job_id),
                    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
                    FOREIGN KEY (job_id) REFERENCES job_listings(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Create matching_history table for ML training
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS matching_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    driver_id INT NOT NULL,
                    job_id INT NOT NULL,
                    predicted_score DECIMAL(5,4),
                    actual_outcome ENUM('applied', 'not_applied', 'hired', 'rejected', 'withdrawn') DEFAULT NULL,
                    feedback_score INT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
                    FOREIGN KEY (job_id) REFERENCES job_listings(id) ON DELETE CASCADE,
                    INDEX idx_outcome (actual_outcome),
                    INDEX idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Create skill_categories table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS skill_categories (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    parent_id INT DEFAULT NULL,
                    weight DECIMAL(3,2) DEFAULT 1.00,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (parent_id) REFERENCES skill_categories(id) ON DELETE SET NULL,
                    UNIQUE KEY unique_name (name)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Insert default skill categories (ignore duplicates)
            $this->pdo->exec("
                INSERT IGNORE INTO skill_categories (name, weight) VALUES
                ('Τύπος Διπλώματος', 1.5),
                ('Εμπειρία Οδήγησης', 1.3),
                ('Πιστοποιήσεις', 1.2),
                ('Τύπος Οχήματος', 1.4),
                ('Soft Skills', 0.8),
                ('Γλώσσες', 0.7),
                ('Διαθεσιμότητα', 1.1)
            ");

            echo "AI matching tables created successfully!\n";
            return true;
        } catch (PDOException $e) {
            echo "Error creating AI matching tables: " . $e->getMessage() . "\n";
            return false;
        }
    }

    public function down()
    {
        try {
            $this->pdo->exec("DROP TABLE IF EXISTS matching_history");
            $this->pdo->exec("DROP TABLE IF EXISTS matching_scores");
            $this->pdo->exec("DROP TABLE IF EXISTS job_requirements_embeddings");
            $this->pdo->exec("DROP TABLE IF EXISTS driver_skills_embeddings");
            $this->pdo->exec("DROP TABLE IF EXISTS skill_categories");

            echo "AI matching tables dropped successfully!\n";
            return true;
        } catch (PDOException $e) {
            echo "Error dropping AI matching tables: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Execute migration
$migration = new CreateAIMatchingTables();
$migration->up();
