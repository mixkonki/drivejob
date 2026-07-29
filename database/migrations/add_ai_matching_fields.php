<?php

require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Database;

class AddAIMatchingFields
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function up()
    {
        try {
            // Add fields to drivers table if they don't exist
            $this->addColumnIfNotExists('drivers', 'is_available', 'TINYINT(1) DEFAULT 1');
            $this->addColumnIfNotExists('drivers', 'latitude', 'DECIMAL(10,8) DEFAULT NULL');
            $this->addColumnIfNotExists('drivers', 'longitude', 'DECIMAL(11,8) DEFAULT NULL');
            $this->addColumnIfNotExists('drivers', 'preferred_schedule', "VARCHAR(50) DEFAULT 'any'");
            $this->addColumnIfNotExists('drivers', 'max_distance', 'INT DEFAULT 100');
            $this->addColumnIfNotExists('drivers', 'min_salary', 'DECIMAL(10,2) DEFAULT 0');
            $this->addColumnIfNotExists('drivers', 'driving_since', 'DATE DEFAULT NULL');
            $this->addColumnIfNotExists('drivers', 'last_login', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');

            // Add fields to job_listings table if they don't exist
            $this->addColumnIfNotExists('job_listings', 'latitude', 'DECIMAL(10,8) DEFAULT NULL');
            $this->addColumnIfNotExists('job_listings', 'longitude', 'DECIMAL(11,8) DEFAULT NULL');
            $this->addColumnIfNotExists('job_listings', 'is_urgent', 'TINYINT(1) DEFAULT 0');
            $this->addColumnIfNotExists('job_listings', 'route_type', "VARCHAR(50) DEFAULT 'local'");
            $this->addColumnIfNotExists('job_listings', 'cargo_type', 'VARCHAR(100) DEFAULT NULL');
            $this->addColumnIfNotExists('job_listings', 'benefits', 'TEXT DEFAULT NULL');
            $this->addColumnIfNotExists('job_listings', 'requirements', 'TEXT DEFAULT NULL');
            $this->addColumnIfNotExists('job_listings', 'min_experience', 'INT DEFAULT 0');
            $this->addColumnIfNotExists('job_listings', 'salary_min', 'DECIMAL(10,2) DEFAULT NULL');
            $this->addColumnIfNotExists('job_listings', 'salary_max', 'DECIMAL(10,2) DEFAULT NULL');

            // Add fields to companies table if they don't exist
            $this->addColumnIfNotExists('companies', 'latitude', 'DECIMAL(10,8) DEFAULT NULL');
            $this->addColumnIfNotExists('companies', 'longitude', 'DECIMAL(11,8) DEFAULT NULL');

            echo "AI matching fields added successfully!\n";
            return true;
        } catch (PDOException $e) {
            echo "Error adding AI matching fields: " . $e->getMessage() . "\n";
            return false;
        }
    }

    private function addColumnIfNotExists($table, $column, $definition)
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);

        if ($stmt->fetchColumn() == 0) {
            $this->pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
            echo "   Added column $column to $table\n";
        } else {
            echo "   Column $column already exists in $table\n";
        }
    }

    public function down()
    {
        // We don't remove these fields as they might contain data
        echo "This migration cannot be reversed to preserve data integrity.\n";
        return true;
    }
}

// Execute migration
$migration = new AddAIMatchingFields();
$migration->up();
