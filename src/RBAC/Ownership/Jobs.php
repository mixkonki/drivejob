<?php

namespace DriveJob\RBAC\Ownership;

use DriveJob\RBAC\DB;
use PDO;

final class Jobs
{
    /** true αν ο χρήστης είναι ιδιοκτήτης της αγγελίας (ελέγχει company_id και driver_id). */
    public static function isOwner(int $userId, int $jobId): bool
    {
        $pdo = DB::pdo();

        // 1) Έλεγχος αν ο χρήστης είναι company που δημιούργησε την αγγελία
        try {
            $st = $pdo->prepare("
                SELECT 1 FROM job_listings jl 
                JOIN companies c ON c.id = jl.company_id 
                WHERE jl.id = :jid AND c.user_id = :uid 
                LIMIT 1
            ");
            $st->execute([":jid" => $jobId, ":uid" => $userId]);
            if ($st->fetchColumn()) return true;
        } catch (\Throwable $e) {
        }

        // 2) Έλεγχος αν ο χρήστης είναι driver που δημιούργησε την αγγελία
        try {
            $st = $pdo->prepare("
                SELECT 1 FROM job_listings jl 
                JOIN drivers d ON d.id = jl.driver_id 
                WHERE jl.id = :jid AND d.user_id = :uid 
                LIMIT 1
            ");
            $st->execute([":jid" => $jobId, ":uid" => $userId]);
            if ($st->fetchColumn()) return true;
        } catch (\Throwable $e) {
        }

        return false;
    }
}
