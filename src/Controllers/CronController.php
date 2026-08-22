<?php

namespace Drivejob\Controllers;

use Drivejob\Core\Logger;

/**
 * Εκτέλεση προγραμματισμένων εργασιών μέσω HTTP.
 *
 * ΓΙΑΤΙ ΥΠΑΡΧΕΙ
 * Ο πάροχος φιλοξενίας απαγορεύει το crontab στους χρήστες και τα Scheduled
 * Tasks του panel δεν εκτελούνταν. Αυτός ο controller επιτρέπει σε εξωτερικό
 * χρονοπρογραμματιστή (π.χ. cron-job.org) να ενεργοποιεί τις ίδιες εργασίες.
 * Όταν ο πάροχος διορθώσει το cron, οι διαδρομές αυτές μπορούν να μείνουν ως
 * εφεδρεία ή να αφαιρεθούν — οι εργασίες τρέχουν το ίδιο και από CLI.
 *
 * ΑΣΦΑΛΕΙΑ
 * - Απαιτείται μυστικό από το .env (CRON_TOKEN). Χωρίς αυτό: 404, ώστε να μην
 *   αποκαλύπτεται καν η ύπαρξη του endpoint.
 * - Η σύγκριση γίνεται με hash_equals (σταθερός χρόνος).
 * - Προτιμάται η κεφαλίδα X-Cron-Token· το query parameter υποστηρίζεται μόνο
 *   για υπηρεσίες που δεν στέλνουν κεφαλίδες, και καταγράφεται προειδοποίηση
 *   επειδή τα URL καταλήγουν σε access logs.
 * - Κλείδωμα ανά εργασία: αν η προηγούμενη εκτέλεση τρέχει ακόμη, επιστρέφει
 *   409 αντί να τρέξουν δύο παράλληλα.
 */
class CronController
{
    /** Οι επιτρεπόμενες εργασίες και το script που εκτελούν. */
    private const TASKS = [
        'matching-worker' => [
            'script' => 'devtools/run-matching-worker.php',
            'args'   => ['100'],
            'label'  => 'Ουρά ταιριασμάτων',
            'limit'  => 240,
        ],
        'ai-scores' => [
            'script' => 'scripts/cron/update-matching-scores.php',
            'args'   => [],
            'label'  => 'Υπολογισμός σκορ AI',
            'limit'  => 280,
        ],
        'license-expiry' => [
            'script' => 'scripts/cron/check_expiring_licenses.php',
            'args'   => [],
            'label'  => 'Ειδοποιήσεις λήξης αδειών',
            'limit'  => 280,
        ],
        'backup' => [
            'script' => 'scripts/backup-database.sh',
            'args'   => [],
            'label'  => 'Αντίγραφο ασφαλείας βάσης',
            'limit'  => 280,
            'bin'    => 'bash',
        ],
    ];

    public function run(string $task)
    {
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');

        $expected = (string) ($_ENV['CRON_TOKEN'] ?? getenv('CRON_TOKEN') ?: '');

        // Χωρίς ρυθμισμένο μυστικό η λειτουργία είναι απενεργοποιημένη.
        if (strlen($expected) < 24) {
            $this->notFound('CRON_TOKEN δεν έχει οριστεί ή είναι πολύ μικρό (<24 χαρακτήρες).');
        }

        $fromHeader = $_SERVER['HTTP_X_CRON_TOKEN'] ?? '';
        $provided = $fromHeader !== '' ? $fromHeader : (string) ($_GET['token'] ?? '');

        if ($provided === '' || !hash_equals($expected, $provided)) {
            Logger::warning('Cron: απόρριψη αιτήματος', [
                'task' => $task,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '?',
                'via' => $fromHeader !== '' ? 'header' : 'query',
            ]);
            $this->notFound();
        }

        if ($fromHeader === '') {
            Logger::warning('Cron: το μυστικό στάλθηκε ως query parameter — προτιμήστε την κεφαλίδα X-Cron-Token.');
        }

        if (!isset(self::TASKS[$task])) {
            http_response_code(400);
            echo "Άγνωστη εργασία. Επιτρεπόμενες: " . implode(', ', array_keys(self::TASKS)) . "\n";
            exit;
        }

        $config = self::TASKS[$task];

        // ── Κλείδωμα ώστε να μην τρέξουν δύο παράλληλα ──────────────────
        $lockDir = ROOT_DIR . '/storage/locks';
        if (!is_dir($lockDir)) {
            @mkdir($lockDir, 0775, true);
        }
        $lockFile = $lockDir . '/cron-' . $task . '.lock';
        $lock = fopen($lockFile, 'c');

        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            http_response_code(409);
            echo "Η εργασία «{$config['label']}» εκτελείται ήδη — παραλείπεται.\n";
            exit;
        }

        set_time_limit($config['limit']);
        $started = microtime(true);

        // Οι περισσότερες εργασίες είναι PHP· το αντίγραφο ασφαλείας είναι bash.
        if (($config['bin'] ?? 'php') === 'bash') {
            $interpreter = '/bin/bash';
        } else {
            $interpreter = $this->resolvePhpCli();
            if ($interpreter === null) {
                flock($lock, LOCK_UN);
                fclose($lock);
                http_response_code(500);
                echo "Δεν βρέθηκε εκτελέσιμο PHP CLI. Όρισε PHP_CLI στο .env "
                   . "(π.χ. PHP_CLI=/usr/php83/usr/bin/php).\n";
                exit;
            }
        }
        $command = escapeshellcmd($interpreter) . ' ' . escapeshellarg(ROOT_DIR . '/' . $config['script']);
        foreach ($config['args'] as $arg) {
            $command .= ' ' . escapeshellarg($arg);
        }

        $output = [];
        $exitCode = 0;

        if (!function_exists('exec')) {
            flock($lock, LOCK_UN);
            fclose($lock);
            http_response_code(500);
            echo "Η συνάρτηση exec() είναι απενεργοποιημένη — δεν μπορεί να εκτελεστεί εργασία μέσω HTTP.\n";
            exit;
        }

        exec($command . ' 2>&1', $output, $exitCode);

        flock($lock, LOCK_UN);
        fclose($lock);

        $seconds = round(microtime(true) - $started, 1);
        $body = implode("\n", $output);

        Logger::info('Cron μέσω HTTP', [
            'task' => $task,
            'exit_code' => $exitCode,
            'seconds' => $seconds,
        ]);

        http_response_code($exitCode === 0 ? 200 : 500);
        echo "[{$config['label']}] exit={$exitCode} σε {$seconds}s\n\n{$body}\n";
        exit;
    }

    /**
     * Εντοπισμός του εκτελέσιμου PHP της γραμμής εντολών.
     *
     * ΠΡΟΣΟΧΗ: σε web αίτημα η PHP_BINARY δείχνει στο php-fpm, ΟΧΙ στο CLI.
     * Αν περαστεί script σε php-fpm, τυπώνει το usage του και τερματίζει με
     * exit 64 — ακριβώς αυτό συνέβαινε πριν.
     *
     * Σειρά αναζήτησης: PHP_CLI από .env → PHP_BINDIR/php → PHP_BINARY (μόνο
     * αν δεν είναι fpm).
     */
    private function resolvePhpCli(): ?string
    {
        $candidates = [];

        $fromEnv = (string) ($_ENV['PHP_CLI'] ?? getenv('PHP_CLI') ?: '');
        if ($fromEnv !== '') {
            $candidates[] = $fromEnv;
        }

        $candidates[] = PHP_BINDIR . '/php';
        $candidates[] = PHP_BINARY;

        foreach ($candidates as $candidate) {
            if ($candidate === '' || !is_executable($candidate)) {
                continue;
            }
            if (str_contains(basename($candidate), 'fpm')) {
                continue;
            }
            return $candidate;
        }

        return null;
    }

    /** Απάντηση 404 ώστε το endpoint να μη διαφημίζει την ύπαρξή του. */
    private function notFound(string $reason = ''): void
    {
        if ($reason !== '') {
            Logger::warning('Cron: ' . $reason);
        }
        http_response_code(404);
        echo "Not Found\n";
        exit;
    }
}
