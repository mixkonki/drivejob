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
                    'issue_date'  => $formData['tachograph_card_issue'] ?? null,
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
    /**
     * Άδειες χειριστή ΜΕ v2 (25/08/2026) — ΠΟΛΛΕΣ άδειες ανά χειριστή.
     *
     * Από τα πραγματικά βιβλιάρια: κάθε άδεια = Ομάδα (Α/Β) + Ειδικότητα
     * (1-9) + αριθμός + ημ. χορήγησης, και καλύπτει είτε το σύνολο της
     * ειδικότητας είτε συγκεκριμένες υποειδικότητες. Ο Αρ. Μητρώου και η
     * θεώρηση («ισχύς έως») είναι ΚΟΙΝΑ για το βιβλιάριο — η θεώρηση
     * αποθηκεύεται ίδια σε κάθε γραμμή (expiry_date) ώστε τα υπάρχοντα
     * σημεία ανάγνωσης (matching, προφίλ) να συνεχίσουν να δουλεύουν.
     *
     * Πεδία φόρμας: operator_license (master checkbox),
     * operator_registry_number, operator_inspection_until,
     * op_lic[N][speciality|group|number|issue_date|covers_all],
     * op_lic[N][subs][] (κωδικοί π.χ. "2.7").
     */
    public function handleOperatorLicense($driverId, $formData)
    {
        try {
            if (!isset($formData['operator_license']) || $formData['operator_license'] != 1) {
                // Ο οδηγός δήλωσε ότι ΔΕΝ έχει άδεια χειριστή.
                $this->certificationModel->updateOperatorRegistryNumber($driverId, null);
                return $this->certificationModel->deleteDriverOperatorLicense($driverId);
            }

            $inspectionUntil = $formData['operator_inspection_until'] ?? null;
            $rows = $formData['op_lic'] ?? [];
            $licenses = [];

            foreach ((array) $rows as $row) {
                $speciality = trim((string) ($row['speciality'] ?? ''));
                if (!isset(\Drivejob\Helpers\OperatorSpecialities::SPECIALITIES[$speciality])) {
                    continue; // κενό/άκυρο block — αγνοείται
                }

                $group = strtoupper(trim((string) ($row['group'] ?? '')));
                if (!in_array($group, ['A', 'B'], true)) {
                    $group = 'A';
                }

                $coversAll = !empty($row['covers_all']) && $row['covers_all'] == '1';

                // Υποειδικότητες: μόνο έγκυρες ΚΑΙ της ίδιας ειδικότητας.
                $subs = [];
                if (!$coversAll) {
                    foreach ((array) ($row['subs'] ?? []) as $sub) {
                        $sub = trim((string) $sub);
                        if (\Drivejob\Helpers\OperatorSpecialities::isValidSub($sub)
                            && strpos($sub, $speciality . '.') === 0) {
                            $subs[$sub] = $sub;
                        }
                    }
                }

                $licenses[] = [
                    'speciality'     => $speciality,
                    'group_type'     => $group,
                    'license_number' => trim((string) ($row['number'] ?? '')),
                    'issue_date'     => $row['issue_date'] ?? null,
                    'covers_all'     => $coversAll,
                    'expiry_date'    => $inspectionUntil,
                    'subs'           => array_values($subs),
                ];
            }

            $this->certificationModel->updateOperatorRegistryNumber(
                $driverId,
                trim((string) ($formData['operator_registry_number'] ?? '')) ?: null
            );

            return $this->certificationModel->replaceDriverOperatorLicenses($driverId, $licenses);
        } catch (\Exception $e) {
            Logger::error('Error in handleOperatorLicense: ' . $e->getMessage(), ['context' => 'DriverLicenseService']);
            return false;
        }
    }
}
