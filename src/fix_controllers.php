<?php

/**
 * Script για τη διόρθωση των controllers και την υλοποίηση των μεθόδων που λείπουν
 * 
 * Αυτό το script:
 * 1. Δημιουργεί έναν συμβατότητας controller για τις εταιρείες
 * 2. Υλοποιεί τις μεθόδους hasApplied και findSimilar που λείπουν
 */

// Φόρτωση του bootstrap
require_once __DIR__ . '/bootstrap.php';

use Drivejob\Core\Logger;

// 1. Δημιουργία του συμβατότητας controller για τις εταιρείες
$companiesControllerContent = <<<'EOT'
<?php

namespace Drivejob\Controllers;

/**
 * Controller συμβατότητας για τις εταιρείες
 * 
 * Αυτός ο controller χρησιμοποιείται για συμβατότητα με τον παλιό κώδικα
 * και απλώς προωθεί τις κλήσεις στον νέο controller
 */
class CompaniesController
{
    /**
     * @var \Drivejob\Controllers\Company\CompaniesController Ο νέος controller
     */
    private $newController;

    /**
     * Constructor
     *
     * @param PDO|null $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct($pdo = null)
    {
        $this->newController = new \Drivejob\Controllers\Company\CompaniesController($pdo);
    }

    /**
     * Προωθεί όλες τις κλήσεις μεθόδων στον νέο controller
     *
     * @param string $name Το όνομα της μεθόδου
     * @param array $arguments Τα ορίσματα της μεθόδου
     * @return mixed Το αποτέλεσμα της κλήσης
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->newController, $name], $arguments);
    }

    /**
     * Προβάλλει τη σελίδα προφίλ της εταιρείας
     */
    public function profile()
    {
        return $this->newController->profile();
    }

    /**
     * Προβάλλει τη φόρμα επεξεργασίας προφίλ
     */
    public function edit()
    {
        return $this->newController->edit();
    }

    /**
     * Αποθηκεύει τις αλλαγές στο προφίλ
     */
    public function update()
    {
        return $this->newController->update();
    }

    /**
     * Εμφανίζει το δημόσιο προφίλ μιας εταιρείας
     * 
     * @param int $id Το ID της εταιρείας
     */
    public function publicProfile($id)
    {
        return $this->newController->publicProfile($id);
    }

    /**
     * Αναζητά εταιρείες με βάση διάφορα κριτήρια
     */
    public function search()
    {
        return $this->newController->search();
    }
}
EOT;

// Αποθήκευση του controller συμβατότητας
$companiesControllerPath = __DIR__ . '/Controllers/CompaniesController.php';
if (!file_exists($companiesControllerPath)) {
    if (file_put_contents($companiesControllerPath, $companiesControllerContent)) {
        echo "Ο controller συμβατότητας για τις εταιρείες δημιουργήθηκε με επιτυχία.\n";
    } else {
        echo "Σφάλμα κατά τη δημιουργία του controller συμβατότητας για τις εταιρείες.\n";
    }
} else {
    echo "Ο controller συμβατότητας για τις εταιρείες υπάρχει ήδη.\n";
}

// 2. Υλοποίηση της μεθόδου hasApplied στο JobApplicationRepository
$jobApplicationRepositoryPath = __DIR__ . '/Repositories/JobApplicationRepository.php';
if (file_exists($jobApplicationRepositoryPath)) {
    $jobApplicationRepositoryContent = file_get_contents($jobApplicationRepositoryPath);

    // Έλεγχος αν η μέθοδος hasApplied υπάρχει ήδη
    if (strpos($jobApplicationRepositoryContent, 'function hasApplied') === false) {
        // Προσθήκη της μεθόδου hasApplied
        $hasAppliedMethod = <<<'EOT'

    /**
     * Ελέγχει αν ένας οδηγός έχει ήδη υποβάλει αίτηση για μια αγγελία
     * 
     * @param int $driverId Το ID του οδηγού
     * @param int $listingId Το ID της αγγελίας
     * @return bool Αν έχει υποβάλει αίτηση ή όχι
     */
    public function hasApplied($driverId, $listingId)
    {
        try {
            // Δημιουργία του SQL ερωτήματος
            $sql = "SELECT COUNT(*) FROM {$this->table} WHERE driver_id = ? AND job_listing_id = ?";

            // Εκτέλεση του ερωτήματος
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$driverId, $listingId]);
            $count = $stmt->fetchColumn();

            return $count > 0;
        } catch (\PDOException $e) {
            Logger::error('PDO exception in hasApplied', [
                'message' => $e->getMessage(),
                'driver_id' => $driverId,
                'listing_id' => $listingId
            ]);
            throw new DatabaseException('Failed to check if driver has applied', (int)$e->getCode(), $e, [
                'driver_id' => $driverId,
                'listing_id' => $listingId
            ]);
        }
    }
EOT;

        // Εύρεση της τελευταίας μεθόδου στην κλάση
        $lastMethodPos = strrpos($jobApplicationRepositoryContent, '}');
        $classEndPos = strrpos($jobApplicationRepositoryContent, '}', $lastMethodPos - strlen($jobApplicationRepositoryContent) - 1);

        // Προσθήκη της μεθόδου πριν το τέλος της κλάσης
        $newContent = substr($jobApplicationRepositoryContent, 0, $classEndPos) . $hasAppliedMethod . "\n" . substr($jobApplicationRepositoryContent, $classEndPos);

        if (file_put_contents($jobApplicationRepositoryPath, $newContent)) {
            echo "Η μέθοδος hasApplied προστέθηκε με επιτυχία στο JobApplicationRepository.\n";
        } else {
            echo "Σφάλμα κατά την προσθήκη της μεθόδου hasApplied στο JobApplicationRepository.\n";
        }
    } else {
        echo "Η μέθοδος hasApplied υπάρχει ήδη στο JobApplicationRepository.\n";
    }
} else {
    echo "Το αρχείο JobApplicationRepository.php δεν βρέθηκε.\n";
}

// 3. Υλοποίηση της μεθόδου findSimilar στο JobListingRepository
$jobListingRepositoryPath = __DIR__ . '/Repositories/JobListingRepository.php';
if (file_exists($jobListingRepositoryPath)) {
    $jobListingRepositoryContent = file_get_contents($jobListingRepositoryPath);

    // Έλεγχος αν η μέθοδος findSimilar υπάρχει ήδη
    if (strpos($jobListingRepositoryContent, 'function findSimilar') === false) {
        // Προσθήκη της μεθόδου findSimilar
        $findSimilarMethod = <<<'EOT'

    /**
     * Βρίσκει παρόμοιες αγγελίες με βάση τα κριτήρια
     * 
     * @param array $criteria Τα κριτήρια αναζήτησης
     * @return array Οι παρόμοιες αγγελίες
     */
    public function findSimilar(array $criteria = [])
    {
        try {
            $query = "SELECT j.*, c.company_name
                     FROM {$this->table} j
                     LEFT JOIN companies c ON j.company_id = c.id
                     WHERE j.is_active = 1 AND (j.expires_at IS NULL OR j.expires_at > NOW())";
            $params = [];
            $conditions = [];

            // Προσθήκη των κριτηρίων
            if (isset($criteria['job_category']) && $criteria['job_category']) {
                $conditions[] = "j.job_category = :job_category";
                $params['job_category'] = $criteria['job_category'];
            }

            if (isset($criteria['job_type']) && $criteria['job_type']) {
                $conditions[] = "j.job_type = :job_type";
                $params['job_type'] = $criteria['job_type'];
            }

            if (isset($criteria['exclude_id']) && $criteria['exclude_id']) {
                $conditions[] = "j.id != :exclude_id";
                $params['exclude_id'] = $criteria['exclude_id'];
            }

            // Προσθήκη των συνθηκών στο ερώτημα
            if (!empty($conditions)) {
                $query .= " AND " . implode(" AND ", $conditions);
            }

            // Προσθήκη της ταξινόμησης
            $query .= " ORDER BY j.created_at DESC";

            // Προσθήκη του LIMIT
            $limit = isset($criteria['limit']) ? (int)$criteria['limit'] : 5;
            $query .= " LIMIT " . $limit;

            // Εκτέλεση του ερωτήματος
            $stmt = $this->pdo->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return [
                'results' => $results,
                'pagination' => [
                    'total' => count($results),
                    'page' => 1,
                    'limit' => $limit,
                    'pages' => 1
                ]
            ];
        } catch (\PDOException $e) {
            throw new \Drivejob\Core\Exceptions\DatabaseException('Failed to find similar job listings', (int)$e->getCode(), $e, [
                'criteria' => $criteria
            ]);
        }
    }
EOT;

        // Εύρεση της τελευταίας μεθόδου στην κλάση
        $lastMethodPos = strrpos($jobListingRepositoryContent, '}');
        $classEndPos = strrpos($jobListingRepositoryContent, '}', $lastMethodPos - strlen($jobListingRepositoryContent) - 1);

        // Προσθήκη της μεθόδου πριν το τέλος της κλάσης
        $newContent = substr($jobListingRepositoryContent, 0, $classEndPos) . $findSimilarMethod . "\n" . substr($jobListingRepositoryContent, $classEndPos);

        if (file_put_contents($jobListingRepositoryPath, $newContent)) {
            echo "Η μέθοδος findSimilar προστέθηκε με επιτυχία στο JobListingRepository.\n";
        } else {
            echo "Σφάλμα κατά την προσθήκη της μεθόδου findSimilar στο JobListingRepository.\n";
        }
    } else {
        echo "Η μέθοδος findSimilar υπάρχει ήδη στο JobListingRepository.\n";
    }
} else {
    echo "Το αρχείο JobListingRepository.php δεν βρέθηκε.\n";
}

echo "Η διόρθωση των controllers και η υλοποίηση των μεθόδων που λείπουν ολοκληρώθηκε.\n";
