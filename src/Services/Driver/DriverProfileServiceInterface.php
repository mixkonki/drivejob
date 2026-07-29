<?php

namespace Drivejob\Services\Driver;

/**
 * Διεπαφή για την υπηρεσία διαχείρισης των προφίλ οδηγών
 */
interface DriverProfileServiceInterface
{
    /**
     * Δημιουργεί έναν νέο λογαριασμό οδηγού
     * 
     * @param array $data Δεδομένα νέου οδηγού
     * @return int|false ID του νέου οδηγού ή false σε περίπτωση αποτυχίας
     */
    public function registerDriver($data);

    /**
     * Ανάκτηση ολοκληρωμένου προφίλ οδηγού με όλες τις λεπτομέρειες
     * 
     * @param int $driverId ID του οδηγού
     * @return array|false Δεδομένα προφίλ οδηγού ή false αν δεν βρέθηκε
     */
    public function getDriverProfile($driverId);

    /**
     * Ενημέρωση βασικών πληροφοριών οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $data Δεδομένα προς ενημέρωση
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateBasicInfo($driverId, $data);

    /**
     * Ενημέρωση προφίλ οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $data Δεδομένα προφίλ
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateProfile($driverId, $data);

    /**
     * Ενημέρωση προφίλ οδηγού με επεξεργασία αρχείων
     * 
     * @param int $driverId ID του οδηγού
     * @param array $data Δεδομένα προφίλ
     * @param array $files Αρχεία που ανεβάζονται
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateProfileWithFiles($driverId, $data, $files);

    /**
     * Ενημέρωση κωδικού πρόσβασης
     * 
     * @param int $driverId ID του οδηγού
     * @param string $currentPassword Τρέχων κωδικός
     * @param string $newPassword Νέος κωδικός
     * @return bool Επιτυχία/αποτυχία
     */
    public function updatePassword($driverId, $currentPassword, $newPassword);

    /**
     * Ανάκτηση συνοπτικών στοιχείων προφίλ οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array Συνοπτικά στοιχεία οδηγού
     */
    public function getDriverSummary($driverId);

    /**
     * Ανάκτηση οδηγών με βάση κριτήρια αναζήτησης
     * 
     * @param array $params Παράμετροι αναζήτησης
     * @param int $page Αριθμός σελίδας
     * @param int $limit Αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Αποτελέσματα αναζήτησης
     */
    public function searchDrivers($params, $page = 1, $limit = 10);

    /**
     * Ανάκτηση πρόσφατων διαθέσιμων οδηγών
     * 
     * @param int $limit Μέγιστος αριθμός αποτελεσμάτων
     * @return array Λίστα οδηγών
     */
    public function getRecentAvailableDrivers($limit = 5);

    /**
     * Ανάκτηση κορυφαίων αξιολογημένων οδηγών
     * 
     * @param int $limit Μέγιστος αριθμός αποτελεσμάτων
     * @return array Λίστα οδηγών
     */
    public function getTopRatedDrivers($limit = 5);

    /**
     * Ανάκτηση των αδειών και πιστοποιήσεων οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array Πληροφορίες αδειών και πιστοποιήσεων
     */
    public function getDriverCertifications($driverId);

    /**
     * Διαγραφή λογαριασμού οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteDriver($driverId);

    /**
     * Ενημέρωση συνολικής βαθμολογίας οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverRating($driverId);

    /**
     * Προσθήκη αξιολόγησης για οδηγό
     * 
     * @param int $driverId ID του οδηγού
     * @param int $companyId ID της εταιρείας
     * @param float $rating Βαθμολογία (0-5)
     * @param string $comment Σχόλιο
     * @return bool Επιτυχία/αποτυχία
     */
    public function addDriverReview($driverId, $companyId, $rating, $comment = '');

    /**
     * Ενημέρωση δεξιοτήτων οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $skillsData Δεδομένα δεξιοτήτων
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverSkills($driverId, $skillsData);

    /**
     * Ενημέρωση εμπειρίας οχημάτων οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $vehicleExperience Δεδομένα εμπειρίας οχημάτων
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverVehicleExperience($driverId, $vehicleExperience);

    /**
     * Ενημέρωση αυτοαξιολόγησης οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $assessmentData Δεδομένα αυτοαξιολόγησης
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverAssessment($driverId, $assessmentData);

    /**
     * Ενημέρωση πιστοποιητικών εκπαίδευσης οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $certifications Δεδομένα πιστοποιητικών
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverCertifications($driverId, $certifications);

    /**
     * Προσθήκη συμβάντος για οδηγό
     * 
     * @param int $driverId ID του οδηγού
     * @param array $incidentData Δεδομένα συμβάντος
     * @return int|bool ID του συμβάντος ή false σε αποτυχία
     */
    public function addDriverIncident($driverId, $incidentData);
}
