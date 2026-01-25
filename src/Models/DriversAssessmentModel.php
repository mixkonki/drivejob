<?php

namespace Drivejob\Models;

/**
 * Alias για το Drivejob\Models\Driver\AssessmentModel
 * 
 * Αυτή η κλάση δημιουργήθηκε για να διατηρήσει τη συμβατότητα με τον υπάρχοντα κώδικα
 * που χρησιμοποιεί το namespace Drivejob\Models\DriversAssessmentModel αντί για το σωστό
 * Drivejob\Models\Driver\AssessmentModel.
 */
class DriversAssessmentModel extends Driver\AssessmentModel
{
    /**
     * Constructor
     *
     * @param \PDO $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
    }
}
