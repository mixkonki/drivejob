<?php

namespace Drivejob\Models;

/**
 * Alias για το Drivejob\Models\Driver\ProfileModel
 * 
 * Αυτή η κλάση δημιουργήθηκε για να διατηρήσει τη συμβατότητα με τον υπάρχοντα κώδικα
 * που χρησιμοποιεί το namespace Drivejob\Models\ProfileModel αντί για το σωστό
 * Drivejob\Models\Driver\ProfileModel.
 */
class ProfileModel extends Driver\ProfileModel
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
