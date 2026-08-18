<?php

namespace Drivejob\Controllers;

use Drivejob\Core\Database;
use Drivejob\Core\Logger;

/**
 * Health check (Πακέτο 9) — GET /health
 *
 * Για uptime monitors και health checks πλατφορμών (Render, Docker, load balancer).
 * 200 όταν εφαρμογή + βάση + αποθήκευση απαντούν, 503 διαφορετικά.
 * ΔΕΝ αποκαλύπτει credentials, εκδόσεις ή στοιχεία υποδομής.
 */
class HealthController
{
    public function index()
    {
        $checks = ['app' => true, 'database' => false, 'storage' => false];

        try {
            Database::getInstance()->getConnection()->query('SELECT 1');
            $checks['database'] = true;
        } catch (\Throwable $e) {
            Logger::error('Health check: αποτυχία βάσης — ' . $e->getMessage());
        }

        $storage = ROOT_DIR . '/storage/uploads';
        $checks['storage'] = is_dir($storage) && is_writable($storage);

        $healthy = !in_array(false, $checks, true);

        http_response_code($healthy ? 200 : 503);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'time' => date('c'),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
