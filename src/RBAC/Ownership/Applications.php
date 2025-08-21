<?php
namespace DriveJob\RBAC\Ownership;

use DriveJob\RBAC\DB;
use PDO;

final class Applications {
    public static function isEmployerOfApplication(int $userId, int $applicationId): bool {
        $pdo = DB::pdo();

        // job_applications -> job_listings -> companies.user_id
        try {
            $st = $pdo->prepare("
                SELECT 1
                FROM job_applications ja
                JOIN job_listings jl ON jl.id = ja.job_listing_id
                JOIN companies c     ON c.id = jl.company_id
                WHERE ja.id = :aid AND c.user_id = :uid
                LIMIT 1
            ");
            $st->execute([':aid'=>$applicationId, ':uid'=>$userId]);
            if ($st->fetchColumn()) return true;
        } catch (\Throwable $e) {}

        // Alt schema: job_applications -> jobs.employer_id
        try {
            $st = $pdo->prepare("
                SELECT 1
                FROM job_applications ja
                JOIN jobs j ON j.id = ja.job_id
                WHERE ja.id = :aid AND j.employer_id = :uid
                LIMIT 1
            ");
            $st->execute([':aid'=>$applicationId, ':uid'=>$userId]);
            if ($st->fetchColumn()) return true;
        } catch (\Throwable $e) {}

        return false;
    }
}
