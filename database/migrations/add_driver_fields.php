<?php

require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Database;

class AddDriverFields
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function up()
    {
        try {
            // Add missing fields to drivers table
            $this->addColumnIfNotExists('drivers', 'date_of_birth', 'DATE DEFAULT NULL');
            $this->addColumnIfNotExists('drivers', 'years_experience', 'INT DEFAULT 0');
            $this->addColumnIfNotExists('drivers', 'age', 'INT DEFAULT NULL');
            $this->addColumnIfNotExists('drivers', 'city', 'VARCHAR(100) DEFAULT NULL');
            $this->addColumnIfNotExists('drivers', 'region', 'VARCHAR(100) DEFAULT NULL');
            $this->addColumnIfNotExists('drivers', 'latitude', 'DECIMAL(10,8) DEFAULT NULL');
            $this->addColumnIfNotExists('drivers', 'longitude', 'DECIMAL(11,8) DEFAULT NULL');

            // Add certification_type to driver_certifications if missing
            $this->addColumnIfNotExists('driver_certifications', 'certification_type', 'VARCHAR(50) DEFAULT NULL');

            // Add location field to job_listings
            $this->addColumnIfNotExists('job_listings', 'location', 'VARCHAR(255) DEFAULT NULL');
            $this->addColumnIfNotExists('job_listings', 'employment_type', 'VARCHAR(50) DEFAULT NULL');
            $this->addColumnIfNotExists('job_listings', 'vehicle_type', 'VARCHAR(100) DEFAULT NULL');

            // Update years_experience based on driving_since if available
            $this->pdo->exec("
                UPDATE drivers 
                SET years_experience = TIMESTAMPDIFF(YEAR, driving_since, CURDATE())
                WHERE driving_since IS NOT NULL AND years_experience = 0
            ");

            echo "Driver fields added successfully!\n";
            return true;
        } catch (PDOException $e) {
            echo "Error adding driver fields: " . $e->getMessage() . "\n";
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
        echo "This migration cannot be reversed to preserve data integrity.\n";
        return true;
    }
}

// Execute migration
$migration = new AddDriverFields();
$migration->up();
