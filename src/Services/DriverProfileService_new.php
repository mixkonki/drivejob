<?php

namespace Drivejob\Services;

use PDO;
use PDOException;
use Drivejob\Core\Logger;
use Drivejob\Models\Driver\ProfileModel;
use Drivejob\Models\Driver\LicenseModel;
use Drivejob\Models\Driver\CertificationModel;
use Drivejob\Models\Driver\SkillModel;
use Drivejob\Models\Driver\RatingModel;
use Drivejob\Models\Driver\IncidentModel;

/**
 * Υπηρεσία για τη διαχείριση των προφίλ οδηγών
 */
class DriverProfileService
{
    private $pdo;
    private $profileModel;
    private $licenseModel;
    private $certificationModel;
    private $skillModel;
    private $ratingModel;
    private $incidentModel;

    /**
     * Constructor
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->profileModel = new ProfileModel($pdo);
        $this->licenseModel = new LicenseModel($pdo);
        $this->certificationModel = new CertificationModel($pdo);
        $this->skillModel = new SkillModel($pdo);
        $this->ratingModel = new RatingModel($pdo);
        $this->incidentModel = new IncidentModel($pdo);
    }

    // Εδώ θα προσθέσουμε τις μεθόδους
}
