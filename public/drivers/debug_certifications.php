<?php

// Αρχικοποίηση της εφαρμογής
$container = require_once __DIR__ . '/../../src/bootstrap.php';

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι οδηγός
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Λήψη του ID του οδηγού
$driverId = $_SESSION['user_id'];

// Δημιουργία του service για τα προφίλ οδηγών
$driverProfileService = new \Drivejob\Services\DriverProfileService($pdo);

// Λήψη του προφίλ του οδηγού
$driverProfile = $driverProfileService->getDriverProfile($driverId);

if (!$driverProfile) {
    $_SESSION['error_message'] = 'Δεν βρέθηκε το προφίλ του οδηγού.';
    header('Location: ' . BASE_URL . 'drivers/edit_profile.php');
    exit();
}

// Λήψη των πιστοποιητικών εκπαίδευσης του οδηγού
$driverCertifications = $driverProfile['certifications'] ?? [];

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php';
?>

<style>
    .debug-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .debug-section {
        margin-bottom: 30px;
        padding: 20px;
        background-color: #f9f9f9;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .debug-section h2 {
        margin-top: 0;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }

    .debug-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .debug-table th,
    .debug-table td {
        padding: 8px 12px;
        text-align: left;
        border: 1px solid #ddd;
    }

    .debug-table th {
        background-color: #f2f2f2;
    }

    .debug-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .debug-table tr:hover {
        background-color: #f0f0f0;
    }

    .debug-code {
        background-color: #f5f5f5;
        padding: 15px;
        border-radius: 5px;
        font-family: monospace;
        white-space: pre-wrap;
        overflow-x: auto;
    }

    .debug-actions {
        margin-top: 20px;
    }

    .debug-actions a {
        display: inline-block;
        margin-right: 10px;
        padding: 8px 15px;
        background-color: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 4px;
    }

    .debug-actions a:hover {
        background-color: #0069d9;
    }

    .debug-info {
        background-color: #e9f5ff;
        padding: 15px;
        border-left: 5px solid #007bff;
        margin-bottom: 20px;
    }

    .debug-warning {
        background-color: #fff9e9;
        padding: 15px;
        border-left: 5px solid #ffc107;
        margin-bottom: 20px;
    }

    .debug-error {
        background-color: #ffebee;
        padding: 15px;
        border-left: 5px solid #dc3545;
        margin-bottom: 20px;
    }
</style>

<main>
    <div class="debug-container">
        <h1>Διαγνωστικές Πληροφορίες Πιστοποιητικών</h1>

        <div class="debug-section">
            <h2>Πληροφορίες Συστήματος</h2>
            <div class="debug-info">
                <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                <p><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
                <p><strong>Database Type:</strong> MySQL</p>
                <p><strong>Current Driver ID:</strong> <?php echo $driverId; ?></p>
                <p><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            </div>
        </div>

        <div class="debug-section">
            <h2>Αποθηκευμένα Πιστοποιητικά</h2>

            <?php if (empty($driverCertifications)): ?>
                <div class="debug-warning">
                    <p>Δεν βρέθηκαν αποθηκευμένα πιστοποιητικά για τον οδηγό.</p>
                </div>
            <?php else: ?>
                <p>Βρέθηκαν <?php echo count($driverCertifications); ?> πιστοποιητικά:</p>

                <table class="debug-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ID</th>
                            <th>Τίτλος</th>
                            <th>Πάροχος</th>
                            <th>Κατηγορία</th>
                            <th>Τύπος Μεταφοράς</th>
                            <th>Ημερομηνία</th>
                            <th>Λήξη</th>
                            <th>Διάρκεια</th>
                            <th>Αρχείο</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($driverCertifications as $index => $cert): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo $cert['id'] ?? 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($cert['title'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($cert['provider'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($cert['category'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($cert['transport_type'] ?? 'both'); ?></td>
                                <td><?php echo $cert['date'] ?? ''; ?></td>
                                <td><?php echo $cert['expiry'] ?? ''; ?></td>
                                <td><?php echo $cert['duration'] ?? ''; ?></td>
                                <td>
                                    <?php if (!empty($cert['certificate_file'])): ?>
                                        <a href="<?php echo BASE_URL . 'uploads/certificates/' . htmlspecialchars($cert['certificate_file']); ?>" target="_blank">
                                            <?php echo htmlspecialchars($cert['certificate_file']); ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="debug-section">
            <h2>Έλεγχος Φακέλου Αρχείων</h2>

            <?php
            $certificatesDir = ROOT_DIR . '/uploads/certificates';
            $dirExists = file_exists($certificatesDir);
            $isWritable = is_writable($certificatesDir);
            ?>

            <div class="<?php echo $dirExists ? 'debug-info' : 'debug-error'; ?>">
                <p><strong>Φάκελος:</strong> <?php echo $certificatesDir; ?></p>
                <p><strong>Υπάρχει:</strong> <?php echo $dirExists ? 'Ναι' : 'Όχι'; ?></p>
                <?php if ($dirExists): ?>
                    <p><strong>Δικαιώματα εγγραφής:</strong> <?php echo $isWritable ? 'Ναι' : 'Όχι'; ?></p>
                <?php endif; ?>
            </div>

            <?php if ($dirExists): ?>
                <h3>Περιεχόμενα Φακέλου</h3>
                <?php
                $files = scandir($certificatesDir);
                $certificateFiles = array_filter($files, function ($file) {
                    return !in_array($file, ['.', '..']);
                });
                ?>

                <?php if (empty($certificateFiles)): ?>
                    <div class="debug-warning">
                        <p>Ο φάκελος είναι κενός.</p>
                    </div>
                <?php else: ?>
                    <table class="debug-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Όνομα Αρχείου</th>
                                <th>Μέγεθος</th>
                                <th>Τύπος</th>
                                <th>Ημερομηνία Τροποποίησης</th>
                                <th>Ενέργειες</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($certificateFiles as $index => $file): ?>
                                <?php
                                $filePath = $certificatesDir . '/' . $file;
                                $fileSize = filesize($filePath);
                                $fileType = mime_content_type($filePath);
                                $fileModified = date('Y-m-d H:i:s', filemtime($filePath));
                                ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($file); ?></td>
                                    <td><?php echo number_format($fileSize / 1024, 2); ?> KB</td>
                                    <td><?php echo $fileType; ?></td>
                                    <td><?php echo $fileModified; ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL . 'uploads/certificates/' . $file; ?>" target="_blank">Προβολή</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="debug-section">
            <h2>Έλεγχος Βάσης Δεδομένων</h2>

            <?php
            try {
                // Έλεγχος ύπαρξης πίνακα
                $tableCheckStmt = $pdo->query("SHOW TABLES LIKE 'driver_certifications'");
                $tableExists = $tableCheckStmt->rowCount() > 0;

                if ($tableExists) {
                    // Έλεγχος δομής πίνακα
                    $columnsStmt = $pdo->query("SHOW COLUMNS FROM driver_certifications");
                    $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);

                    // Έλεγχος για τη στήλη certificate_file
                    $hasCertificateFileColumn = in_array('certificate_file', $columns);

                    // Ανάκτηση των εγγραφών για τον τρέχοντα οδηγό
                    $stmt = $pdo->prepare("SELECT * FROM driver_certifications WHERE driver_id = ?");
                    $stmt->execute([$driverId]);
                    $dbCertifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (PDOException $e) {
                $error = $e->getMessage();
            }
            ?>

            <?php if (isset($error)): ?>
                <div class="debug-error">
                    <p><strong>Σφάλμα:</strong> <?php echo $error; ?></p>
                </div>
            <?php else: ?>
                <div class="debug-info">
                    <p><strong>Πίνακας driver_certifications:</strong> <?php echo $tableExists ? 'Υπάρχει' : 'Δεν υπάρχει'; ?></p>
                    <?php if ($tableExists): ?>
                        <p><strong>Στήλες:</strong> <?php echo implode(', ', $columns); ?></p>
                        <p><strong>Στήλη certificate_file:</strong> <?php echo $hasCertificateFileColumn ? 'Υπάρχει' : 'Δεν υπάρχει'; ?></p>
                        <p><strong>Εγγραφές για τον οδηγό:</strong> <?php echo count($dbCertifications); ?></p>
                    <?php endif; ?>
                </div>

                <?php if ($tableExists && !empty($dbCertifications)): ?>
                    <h3>Εγγραφές από τη Βάση Δεδομένων</h3>
                    <table class="debug-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ID</th>
                                <th>Τίτλος</th>
                                <th>Πάροχος</th>
                                <th>Κατηγορία</th>
                                <th>Τύπος Μεταφοράς</th>
                                <th>Ημερομηνία</th>
                                <th>Λήξη</th>
                                <th>Διάρκεια</th>
                                <th>Αρχείο</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dbCertifications as $index => $cert): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $cert['id'] ?? 'N/A'; ?></td>
                                    <td><?php echo htmlspecialchars($cert['title'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($cert['provider'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($cert['category'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($cert['transport_type'] ?? 'both'); ?></td>
                                    <td><?php echo $cert['date'] ?? ''; ?></td>
                                    <td><?php echo $cert['expiry'] ?? ''; ?></td>
                                    <td><?php echo $cert['duration'] ?? ''; ?></td>
                                    <td>
                                        <?php if (!empty($cert['certificate_file'])): ?>
                                            <a href="<?php echo BASE_URL . 'uploads/certificates/' . htmlspecialchars($cert['certificate_file']); ?>" target="_blank">
                                                <?php echo htmlspecialchars($cert['certificate_file']); ?>
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="debug-section">
            <h2>Έλεγχος Φόρμας</h2>

            <div class="debug-info">
                <p>Για να ελέγξετε τη λειτουργία της φόρμας, ακολουθήστε τα παρακάτω βήματα:</p>
                <ol>
                    <li>Πηγαίνετε στη σελίδα <a href="<?php echo BASE_URL; ?>drivers/certifications.php">Διαχείριση Πιστοποιητικών</a></li>
                    <li>Προσθέστε ένα νέο πιστοποιητικό συμπληρώνοντας όλα τα υποχρεωτικά πεδία</li>
                    <li>Πατήστε το κουμπί "Προσθήκη Πιστοποιητικού"</li>
                    <li>Πατήστε το κουμπί "Αποθήκευση Αλλαγών"</li>
                    <li>Επιστρέψτε σε αυτή τη σελίδα για να δείτε αν το πιστοποιητικό αποθηκεύτηκε σωστά</li>
                </ol>
            </div>

            <div class="debug-actions">
                <a href="<?php echo BASE_URL; ?>drivers/certifications.php">Πήγαινε στη Διαχείριση Πιστοποιητικών</a>
                <a href="<?php echo BASE_URL; ?>drivers/debug_certifications.php">Ανανέωση Σελίδας</a>
            </div>
        </div>
    </div>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php';
?>