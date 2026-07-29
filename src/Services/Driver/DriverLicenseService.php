<?php

namespace Drivejob\Services\Driver;

use PDO;
use Drivejob\Core\Logger;
use Drivejob\Models\Driver\LicenseModel;
use Drivejob\Models\Driver\CertificationModel;

class DriverLicenseService
{
    private $pdo;
    private $licenseModel;
    private $certificationModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->licenseModel = new LicenseModel($pdo);
        $this->certificationModel = new CertificationModel($pdo);
    }

    /**
     * Διαχειρίζεται τις άδειες οδήγησης
     *
     * @param int $driverId ID του οδηγού
     * @param array $formData Δεδομένα από τη φόρμα
     * @return bool Επιτυχία ή αποτυχία
     */
    public function handleDrivingLicenses($driverId, $formData)
    {
        try {
            // Διαγραφή προηγούμενων αδειών
            $this->licenseModel->deleteDriverLicenses($driverId);

            if (isset($formData['license_types']) && is_array($formData['license_types'])) {
                $licenseNumber = $formData['license_number'] ?? null;
                $licenseDocumentExpiry = $formData['license_document_expiry'] ?? null;

                foreach ($formData['license_types'] as $licenseType) {
                    $hasPei = false;
                    $peiExpiryC = null;
                    $peiExpiryD = null;

                    // Έλεγχος για ΠΕΙ στις κατηγορίες C και D
                    if (in_array($licenseType, ['C', 'CE', 'C1', 'C1E'])) {
                        $peiCheckboxName = 'has_pei_' . strtolower($licenseType);
                        if (isset($formData[$peiCheckboxName])) {
                            $hasPei = true;
                            $peiExpiryC = !empty($formData['pei_c_expiry']) ? $formData['pei_c_expiry'] : null;
                        }
                    } else if (in_array($licenseType, ['D', 'DE', 'D1', 'D1E'])) {
                        $peiCheckboxName = 'has_pei_' . strtolower($licenseType);
                        if (isset($formData[$peiCheckboxName])) {
                            $hasPei = true;
                            $peiExpiryD = !empty($formData['pei_d_expiry']) ? $formData['pei_d_expiry'] : null;
                        }
                    }

                    // Λήψη ημερομηνίας λήξης για τη συγκεκριμένη κατηγορία
                    $expiryDate = $formData['license_expiry'][$licenseType] ?? null;

                    $this->licenseModel->addDriverLicense(
                        $driverId,
                        $licenseType,
                        $hasPei,
                        $expiryDate,
                        $licenseNumber,
                        $peiExpiryC,
                        $peiExpiryD,
                        $licenseDocumentExpiry
                    );
                }
            }

            return true;
        } catch (\Exception $e) {
            Logger::error('Error in handleDrivingLicenses: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Διαχειρίζεται το πιστοποιητικό ADR
     *
     * @param int $driverId ID του οδηγού
     * @param array $formData Δεδομένα από τη φόρμα
     * @return bool Επιτυχία ή αποτυχία
     */
    public function handleADRCertificate($driverId, $formData)
    {
        try {
            if (isset($formData['adr_certificate']) && $formData['adr_certificate'] == 1) {
                $adrData = [
                    'adr_type' => $formData['adr_certificate_type'] ?? null,
                    'certificate_number' => $formData['adr_certificate_number'] ?? null,
                    'expiry_date' => $formData['adr_certificate_expiry'] ?? null
                ];

                return $this->certificationModel->updateDriverADRCertificate($driverId, $adrData);
            } else {
                // Αν δεν έχει επιλεγεί το ADR, διαγράφουμε τα στοιχεία
                return $this->certificationModel->deleteDriverADRCertificate($driverId);
            }
        } catch (\Exception $e) {
            Logger::error('Error in handleADRCertificate: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Διαχειρίζεται την κάρτα ταχογράφου
     *
     * @param int $driverId ID του οδηγού
     * @param array $formData Δεδομένα από τη φόρμα
     * @return bool Επιτυχία ή αποτυχία
     */
    public function handleTachographCard($driverId, $formData)
    {
        try {
            if (isset($formData['tachograph_card']) && $formData['tachograph_card'] == 1) {
                $tachographData = [
                    'card_number' => $formData['tachograph_card_number'] ?? null,
                    'expiry_date' => $formData['tachograph_card_expiry'] ?? null
                ];

                return $this->certificationModel->updateDriverTachographCard($driverId, $tachographData);
            } else {
                // Αν δεν έχει επιλεγεί η κάρτα ταχογράφου, διαγράφουμε τα στοιχεία
                return $this->certificationModel->deleteDriverTachographCard($driverId);
            }
        } catch (\Exception $e) {
            Logger::error('Error in handleTachographCard: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Διαχειρίζεται τις ειδικές άδειες
     *
     * @param int $driverId ID του οδηγού
     * @param array $formData Δεδομένα από τη φόρμα
     * @return bool Επιτυχία ή αποτυχία
     */
    public function handleSpecialLicenses($driverId, $formData)
    {
        try {
            // Διαγραφή των υπαρχουσών ειδικών αδειών
            $this->certificationModel->deleteDriverSpecialLicenses($driverId);

            // Αν έχουν υποβληθεί ειδικές άδειες, τις προσθέτουμε στη βάση
            if (isset($formData['special_license_type']) && is_array($formData['special_license_type'])) {
                foreach ($formData['special_license_type'] as $index => $type) {
                    // Αν ο τύπος άδειας δεν είναι κενός, προσθέτουμε την άδεια
                    if (!empty(trim($type))) {
                        $licenseNumber = $formData['special_license_number'][$index] ?? '';
                        $expiryDate = $formData['special_license_expiry'][$index] ?? null;
                        $details = $formData['special_license_details'][$index] ?? '';

                        $this->certificationModel->addDriverSpecialLicense($driverId, $type, $licenseNumber, $expiryDate, $details);
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            Logger::error('Error in handleSpecialLicenses: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Διαχειρίζεται την άδεια χειριστή μηχανημάτων έργου
     *
     * @param int $driverId ID του οδηγού
     * @param array $formData Δεδομένα από τη φόρμα
     * @return bool Επιτυχία ή αποτυχία
     */
    public function handleOperatorLicense($driverId, $formData)
    {
        try {
            if (isset($formData['operator_license']) && $formData['operator_license'] == 1) {
                // Δημιουργία του πίνακα δεδομένων
                $operatorData = [
                    'speciality' => $formData['operator_speciality'] ?? null,
                    'license_number' => $formData['operator_license_number'] ?? null,
                    'expiry_date' => $formData['operator_license_expiry'] ?? null
                ];

                // Ενημέρωση ή προσθήκη της άδειας χειριστή
                $operatorLicenseId = $this->certificationModel->updateDriverOperatorLicense($driverId, $operatorData);

                if ($operatorLicenseId) {
                    // Διαχείριση υποειδικοτήτων
                    $this->handleOperatorSubSpecialities($operatorLicenseId, $formData);
                    return true;
                }

                return false;
            } else {
                // Αν δεν έχει επιλεγεί η άδεια χειριστή, διαγράφουμε τα στοιχεία
                return $this->certificationModel->deleteDriverOperatorLicense($driverId);
            }
        } catch (\Exception $e) {
            Logger::error('Error in handleOperatorLicense: ' . $e->getMessage(), ['context' => 'DriverLicenseService']);
            return false;
        }
    }

    /**
     * Διαχειρίζεται τις υποειδικότητες της άδειας χειριστή
     *
     * @param int $operatorLicenseId ID της άδειας χειριστή
     * @param array $formData Δεδομένα από τη φόρμα
     * @return bool Επιτυχία ή αποτυχία
     */
    private function handleOperatorSubSpecialities($operatorLicenseId, $formData)
    {
        try {
            // Διαγραφή των υπαρχουσών υποειδικοτήτων
            $this->certificationModel->deleteDriverOperatorSubSpecialities($operatorLicenseId);

            // Λήψη των επιλεγμένων υποειδικοτήτων και ομάδων από JSON
            $selectedSubSpecialities = [];
            $selectedGroups = [];

            // Λήψη από το πεδίο JSON των υποειδικοτήτων
            if (isset($formData['all_selected_subspecialities']) && !empty($formData['all_selected_subspecialities'])) {
                try {
                    $selectedSubSpecialities = json_decode($formData['all_selected_subspecialities'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \Exception("Σφάλμα JSON υποειδικοτήτων: " . json_last_error_msg());
                    }
                } catch (\Exception $e) {
                    Logger::error("Σφάλμα αποκωδικοποίησης JSON υποειδικοτήτων: " . $e->getMessage(), ['context' => 'OperatorLicense']);
                    $selectedSubSpecialities = [];
                }
            }

            // Λήψη από το πεδίο JSON των ομάδων
            if (isset($formData['all_selected_groups']) && !empty($formData['all_selected_groups'])) {
                try {
                    $selectedGroups = json_decode($formData['all_selected_groups'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \Exception("Σφάλμα JSON ομάδων: " . json_last_error_msg());
                    }
                } catch (\Exception $e) {
                    Logger::error("Σφάλμα αποκωδικοποίησης JSON ομάδων: " . $e->getMessage(), ['context' => 'OperatorLicense']);
                    $selectedGroups = [];
                }
            }

            // Εναλλακτική μέθοδος λήψης αν η JSON μέθοδος αποτύχει
            if (empty($selectedSubSpecialities) && isset($formData['operator_sub_specialities'])) {
                $selectedSubSpecialities = is_array($formData['operator_sub_specialities'])
                    ? $formData['operator_sub_specialities']
                    : [$formData['operator_sub_specialities']];
            }

            // Προσθήκη των επιλεγμένων υποειδικοτήτων
            if (!empty($selectedSubSpecialities)) {
                foreach ($selectedSubSpecialities as $subSpeciality) {
                    // Καθορισμός της ομάδας (A ή B)
                    $groupType = 'A'; // Προεπιλογή

                    // Από το JSON αντικείμενο ομάδων
                    if (isset($selectedGroups[$subSpeciality])) {
                        $groupType = $selectedGroups[$subSpeciality];
                    }
                    // Από τα άμεσα πεδία της φόρμας
                    else if (isset($formData["group_{$subSpeciality}"])) {
                        $groupType = $formData["group_{$subSpeciality}"];
                    }

                    // Προσθήκη της υποειδικότητας με την ομάδα της
                    $this->certificationModel->addDriverOperatorSubSpeciality($operatorLicenseId, $subSpeciality, $groupType);
                }
            }

            return true;
        } catch (\Exception $e) {
            Logger::error('Error in handleOperatorSubSpecialities: ' . $e->getMessage());
            return false;
        }
    }
}
