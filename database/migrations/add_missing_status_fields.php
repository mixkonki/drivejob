<?php

require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Database;

class AddMissingStatusFields
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function up()
    {
        try {
            // Add status field to driver_certifications if it doesn't exist
            $this->addColumnIfNotExists('driver_certifications', 'status', "VARCHAR(20) DEFAULT 'active'");

            // Add status field to job_listings if it doesn't exist
            $this->addColumnIfNotExists('job_listings', 'status', "VARCHAR(20) DEFAULT 'active'");

            // Add is_active field to driver_licenses if it doesn't exist
            $this->addColumnIfNotExists('driver_licenses', 'is_active', 'TINYINT(1) DEFAULT 1');

            echo "Missing status fields added successfully!\n";
            return true;
        } catch (PDOException $e) {
            echo "Error adding status fields: " . $e->getMessage() . "\n";
            return false;
        }
    }

    private function addColumnIfNotExists($table, $column, $definition)
    {
        // Check if table exists first
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ?
        ");
        $stmt->execute([$table]);

        if ($stmt->fetchColumn() == 0) {
            echo "   Table $table does not exist, skipping...\n";
            return;
        }

        // Check if column exists
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
$migration = new AddMissingStatusFields();
$migration->up();
