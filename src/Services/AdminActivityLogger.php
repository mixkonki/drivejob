<?php

namespace Drivejob\Services;

use PDO;
use PDOException;

/**
 * AdminActivityLogger - Service για καταγραφή δραστηριότητας admin
 * 
 * Καταγράφει όλες τις ενέργειες των administrators για audit trail
 */
class AdminActivityLogger
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Καταγραφή δραστηριότητας admin
     */
    public function log($adminId, $action, $resourceType = null, $resourceId = null, $details = [])
    {
        try {
            $sql = "INSERT INTO admin_activity_logs (
                admin_id, 
                action, 
                resource_type, 
                resource_id, 
                details, 
                ip_address, 
                user_agent
            ) VALUES (
                :admin_id, 
                :action, 
                :resource_type, 
                :resource_id, 
                :details, 
                :ip_address, 
                :user_agent
            )";

            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute([
                'admin_id' => $adminId,
                'action' => $action,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'details' => json_encode($details),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (PDOException $e) {
            error_log("Admin activity logging error: " . $e->getMessage());
            return false;
        }
    }
}
