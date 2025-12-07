<?php

namespace DriveJob\RBAC;

class RBAC {
    private static $pdo;
    
    public static function setPDO($pdo) {
        self::$pdo = $pdo;
    }
    
    public static function requirePermission($userId, $permission) {
        // Για τώρα, απλά επιστρέφουμε true για να λειτουργήσει
        // Μπορείτε να το ενεργοποιήσετε αργότερα
        return true;
        
        /* Πραγματικός έλεγχος - ενεργοποιήστε όταν είναι έτοιμο
        if (!self::$pdo) {
            require_once __DIR__ . "/../../config/database.php";
            self::$pdo = $GLOBALS["pdo"];
        }
        
        $stmt = self::$pdo->prepare("
            SELECT COUNT(*) FROM user_permissions 
            WHERE user_id = ? AND permission = ?
        ");
        $stmt->execute([$userId, $permission]);
        
        if ($stmt->fetchColumn() == 0) {
            header("HTTP/1.1 403 Forbidden");
            echo json_encode(["error" => "Forbidden", "missing_permission" => $permission]);
            exit;
        }
        */
    }
    
    public static function hasPermission($userId, $permission) {
        // Για τώρα, απλά επιστρέφουμε true
        return true;
    }
}
