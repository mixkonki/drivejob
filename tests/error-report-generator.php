<?php

/**
 * Script για δημιουργία αναφορών σφαλμάτων από τα tests
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

class ErrorReportGenerator
{
    private $reportDir;
    private $timestamp;

    public function __construct()
    {
        $this->timestamp = date('Y-m-d_H-i-s');
        $this->reportDir = ROOT_DIR . '/tests/reports/' . $this->timestamp;

        // Δημιουργία φακέλου αναφορών αν δεν υπάρχει
        if (!is_dir(ROOT_DIR . '/tests/reports')) {
            mkdir(ROOT_DIR . '/tests/reports', 0755, true);
        }

        if (!is_dir($this->reportDir)) {
            mkdir($this->reportDir, 0755, true);
        }
    }

    public function generateReport($testResults, $testType = 'driver_tests')
    {
        // Δημιουργία αρχείων αναφοράς
        $this->generateMarkdownReport($testResults, $testType);
        $this->generateJsonReport($testResults, $testType);
        $this->generateSystemInfo();
        $this->generateErrorLog();

        echo "Αναφορά δημιουργήθηκε στο: " . $this->reportDir . "\n";
    }

    private function generateMarkdownReport($testResults, $testType)
    {
        $content = "# Αναφορά Tests: $testType\n\n";
        $content .= "**Ημερομηνία:** " . date('Y-m-d H:i:s') . "\n\n";

        // Συνολικά στατιστικά
        $total = count($testResults);
        $passed = 0;
        $failed = 0;
        $errors = 0;

        foreach ($testResults as $result) {
            if (strpos($result, 'ΕΠΙΤΥΧΙΑ') !== false) {
                $passed++;
            } elseif (strpos($result, 'ΑΠΟΤΥΧΙΑ') !== false) {
                $failed++;
            } elseif (strpos($result, 'ΣΦΑΛΜΑ') !== false) {
                $errors++;
            }
        }

        $content .= "## Συνολικά Στατιστικά\n\n";
        $content .= "- Σύνολο tests: $total\n";
        $content .= "- Επιτυχημένα: ✅ $passed\n";
        $content .= "- Αποτυχημένα: ❌ $failed\n";
        $content .= "- Σφάλματα: ⚠️ $errors\n\n";

        // Λεπτομερή αποτελέσματα
        $content .= "## Λεπτομερή Αποτελέσματα\n\n";
        foreach ($testResults as $test => $result) {
            $status = '❓';
            if (strpos($result, 'ΕΠΙΤΥΧΙΑ') !== false) {
                $status = '✅';
            } elseif (strpos($result, 'ΑΠΟΤΥΧΙΑ') !== false) {
                $status = '❌';
            } elseif (strpos($result, 'ΣΦΑΛΜΑ') !== false) {
                $status = '⚠️';
            }

            $content .= "### $status $test\n";
            $content .= "```\n$result\n```\n\n";
        }

        // Προτάσεις διόρθωσης για τα αποτυχημένα tests
        if ($failed > 0 || $errors > 0) {
            $content .= "## Προτεινόμενες Δράσεις\n\n";
            foreach ($testResults as $test => $result) {
                if (strpos($result, 'ΑΠΟΤΥΧΙΑ') !== false || strpos($result, 'ΣΦΑΛΜΑ') !== false) {
                    $content .= "- **$test**: " . $this->getSuggestion($test, $result) . "\n";
                }
            }
        }

        file_put_contents($this->reportDir . "/report_$testType.md", $content);
    }

    private function generateJsonReport($testResults, $testType)
    {
        $data = [
            'timestamp' => $this->timestamp,
            'test_type' => $testType,
            'summary' => [
                'total' => count($testResults),
                'passed' => 0,
                'failed' => 0,
                'errors' => 0
            ],
            'results' => []
        ];

        foreach ($testResults as $test => $result) {
            $status = 'unknown';
            if (strpos($result, 'ΕΠΙΤΥΧΙΑ') !== false) {
                $status = 'passed';
                $data['summary']['passed']++;
            } elseif (strpos($result, 'ΑΠΟΤΥΧΙΑ') !== false) {
                $status = 'failed';
                $data['summary']['failed']++;
            } elseif (strpos($result, 'ΣΦΑΛΜΑ') !== false) {
                $status = 'error';
                $data['summary']['errors']++;
            }

            $data['results'][$test] = [
                'status' => $status,
                'message' => $result,
                'suggestion' => $this->getSuggestion($test, $result)
            ];
        }

        file_put_contents($this->reportDir . "/report_$testType.json", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function generateSystemInfo()
    {
        $info = [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database' => [
                'driver' => DB_NAME ?? 'Unknown',
                'host' => DB_HOST ?? 'Unknown'
            ],
            'extensions' => get_loaded_extensions(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize')
        ];

        file_put_contents($this->reportDir . "/system_info.json", json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function generateErrorLog()
    {
        // Συλλογή των τελευταίων errors από τα logs
        $logDir = ROOT_DIR . '/logs';
        $errorLogs = [];

        if (is_dir($logDir)) {
            $files = glob($logDir . '/*.log');
            foreach ($files as $file) {
                $errorLogs[basename($file)] = file_get_contents($file);
            }
        }

        file_put_contents($this->reportDir . "/error_logs.json", json_encode($errorLogs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function getSuggestion($test, $result)
    {
        // Βάση δεδομένων για προτάσεις διόρθωσης
        $suggestions = [
            'registration' => [
                'missing_field' => 'Ελέγξτε ότι όλα τα απαιτούμενα πεδία έχουν δηλωθεί στη βάση δεδομένων.',
                'validation' => 'Βεβαιωθείτε ότι ο Validator λειτουργεί σωστά.',
                'database' => 'Ελέγξτε τη σύνδεση με τη βάση δεδομένων και τα σχήματά της.'
            ],
            'verification' => [
                'email' => 'Ελέγξτε ότι η στήλη is_verified υπάρχει στον πίνακα drivers.',
                'token' => 'Ελέγξτε τη διαδικασία δημιουργίας και επαλήθευσης tokens.'
            ],
            'login' => [
                'auth' => 'Ελέγξτε τη διαδικασία επαλήθευσης credentials.',
                'session' => 'Βεβαιωθείτε ότι το Session::set() λειτουργεί σωστά.',
                'redirect' => 'Ελέγξτε ότι οι διαδρομές redirect είναι σωστές.'
            ],
            'profile' => [
                'view' => 'Ελέγξτε ότι το view φορτώνεται σωστά.',
                'update' => 'Βεβαιωθείτε ότι τα δεδομένα περνούν στο model.',
                'csrf' => 'Ελέγξτε τη διαδικασία CSRF protection.'
            ]
        ];

        // Αντιστοίχιση test με προτάσεις
        foreach ($suggestions as $category => $categoryTips) {
            if (strpos($test, $category) !== false) {
                foreach ($categoryTips as $errorType => $tip) {
                    if (strpos(strtolower($result), strtolower($errorType)) !== false) {
                        return $tip;
                    }
                }
                // Γενική πρόταση για την κατηγορία
                return reset($categoryTips);
            }
        }

        return 'Ελέγξτε τα logs για περισσότερες πληροφορίες σχετικά με το σφάλμα.';
    }

    public function createBugReport($title, $description, $steps = [], $expected = '', $actual = '')
    {
        $bugReport = "# Bug Report: $title\n\n";
        $bugReport .= "**Ημερομηνία:** " . date('Y-m-d H:i:s') . "\n\n";
        $bugReport .= "## Περιγραφή\n$description\n\n";

        if (!empty($steps)) {
            $bugReport .= "## Βήματα Αναπαραγωγής\n";
            foreach ($steps as $i => $step) {
                $bugReport .= ($i + 1) . ". $step\n";
            }
            $bugReport .= "\n";
        }

        if ($expected) {
            $bugReport .= "## Αναμενόμενο Αποτέλεσμα\n$expected\n\n";
        }

        if ($actual) {
            $bugReport .= "## Πραγματικό Αποτέλεσμα\n$actual\n\n";
        }

        $bugReport .= "## Συστημικές Πληροφορίες\n";
        $bugReport .= "- PHP Version: " . PHP_VERSION . "\n";
        $bugReport .= "- Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
        $bugReport .= "- Database: " . (DB_NAME ?? 'Unknown') . "\n\n";

        file_put_contents($this->reportDir . "/bug_report.md", $bugReport);

        return $this->reportDir . "/bug_report.md";
    }
}

// Παράδειγμα χρήσης
if (isset($_GET['generate'])) {
    $generator = new ErrorReportGenerator();

    // Προσομοίωση αποτελεσμάτων test
    $testResults = [
        'registration' => 'ΕΠΙΤΥΧΙΑ - Εγγραφή οδηγού με ID: 123',
        'verification' => 'ΑΠΟΤΥΧΙΑ - Η επαλήθευση απέτυχε',
        'login' => 'ΣΦΑΛΜΑ - Database connection failed',
        'profile_view' => 'ΕΠΙΤΥΧΙΑ - Προβολή προφίλ λειτουργεί'
    ];

    $generator->generateReport($testResults, 'driver_auth_tests');

    // Δημιουργία bug report
    $generator->createBugReport(
        'Αποτυχία επαλήθευσης email',
        'Η διαδικασία επαλήθευσης email δεν ολοκληρώνεται επιτυχώς.',
        [
            'Ο χρήστης κάνει εγγραφή',
            'Λαμβάνει email επαλήθευσης',
            'Κάνει κλικ στο link επαλήθευσης',
            'Εμφανίζεται μήνυμα σφάλματος'
        ],
        'Ο χρήστης θα πρέπει να ανακατευθυνθεί στη σελίδα επιτυχίας',
        'Εμφανίζεται μήνυμα σφάλματος 500'
    );
}
