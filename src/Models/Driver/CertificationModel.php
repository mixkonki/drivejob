<?php

namespace Drivejob\Models\Driver;

use PDO;
use PDOException;
use Drivejob\Core\Logger;
use Drivejob\Models\BaseModel;

/**
 * Μοντέλο για τη διαχείριση των πιστοποιήσεων των οδηγών (ADR, άδειες χειριστή, ταχογράφος κλπ)
 */
class CertificationModel extends BaseModel
{
    /**
     * Constructor
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo, 'driver_certifications');
    }

    /**
     * Επιστρέφει τις πιστοποιήσεις και τα σεμινάρια ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array Λίστα πιστοποιήσεων και σεμιναρίων
     */
    public function getDriverCertifications($driverId)
    {
        return $this->select(['driver_id' => $driverId], '*', ['date' => 'DESC']);
    }

    /**
     * Διαγράφει όλες τις πιστοποιήσεις και τα σεμινάρια ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteDriverCertifications($driverId)
    {
        return $this->delete(['driver_id' => $driverId]);
    }

    /**
     * Προσθέτει πιστοποιήσεις και σεμινάρια για έναν οδηγό
     * 
     * @param int $driverId ID του οδηγού
     * @param array $certifications Λίστα πιστοποιήσεων
     * @return bool Επιτυχία/αποτυχία
     */
    public function addDriverCertifications($driverId, $certifications)
    {
        try {
            // Διαγραφή προηγούμενων πιστοποιήσεων
            $this->deleteDriverCertifications($driverId);

            // Αν δεν υπάρχουν νέες πιστοποιήσεις, επιστρέφουμε επιτυχία
            if (empty($certifications)) {
                return true;
            }

            // Προσθήκη των νέων πιστοποιήσεων
            foreach ($certifications as $cert) {
                if (empty($cert['title'])) {
                    continue; // Παραλείπουμε πιστοποιήσεις χωρίς τίτλο
                }

                $data = [
                    'driver_id' => $driverId,
                    'title' => $cert['title'],
                    'provider' => $cert['provider'] ?? null,
                    'date' => $cert['date'] ?? null,
                    'expiry' => $cert['expiry'] ?? null,
                    'description' => $cert['description'] ?? null
                ];

                $result = $this->insert($data);

                if ($result === false) {
                    return false;
                }
            }

            return true;
        } catch (PDOException $e) {
            Logger::error('Error in addDriverCertifications: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημερώνει μια πιστοποίηση οδηγού
     * 
     * @param int $certificationId ID της πιστοποίησης
     * @param int $driverId ID του οδηγού για επαλήθευση
     * @param array $data Δεδομένα της πιστοποίησης
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverCertification($certificationId, $driverId, $data)
    {
        $updateData = [
            'title' => $data['title'],
            'provider' => $data['provider'] ?? null,
            'date' => $data['date'] ?? null,
            'expiry' => $data['expiry'] ?? null,
            'description' => $data['description'] ?? null
        ];

        return $this->update($updateData, ['id' => $certificationId, 'driver_id' => $driverId]);
    }

    /**
     * Διαγράφει μια πιστοποίηση οδηγού
     * 
     * @param int $certificationId ID της πιστοποίησης
     * @param int $driverId ID του οδηγού για επαλήθευση
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteDriverCertification($certificationId, $driverId)
    {
        return $this->delete(['id' => $certificationId, 'driver_id' => $driverId]);
    }

    // -------------------- ADR ΠΙΣΤΟΠΟΙΗΤΙΚΑ --------------------

    /**
     * Λαμβάνει το πιστοποιητικό ADR του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array|null Στοιχεία πιστοποιητικού ή null
     */
    public function getDriverADRCertificate($driverId)
    {
        // Αλλαγή πίνακα για αυτή τη μέθοδο
        $table = 'driver_adr_certificates';
        $sql = "SELECT * FROM $table WHERE driver_id = ? LIMIT 1";
        return $this->queryOne($sql, [$driverId]);
    }

    /**
     * Διαγράφει το πιστοποιητικό ADR του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteDriverADRCertificate($driverId)
    {
        try {
            $table = 'driver_adr_certificates';
            $sql = "DELETE FROM $table WHERE driver_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$driverId]);

            if ($result) {
                // Ενημέρωση του flag στον πίνακα drivers
                $updateFlag = $this->pdo->prepare("UPDATE drivers SET adr_certificate = 0 WHERE id = ?");
                $updateFlag->execute([$driverId]);
            }

            return $result;
        } catch (PDOException $e) {
            Logger::error('Error in deleteDriverADRCertificate: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημερώνει το πιστοποιητικό ADR του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $adrData Δεδομένα πιστοποιητικού ADR
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverADRCertificate($driverId, $adrData)
    {
        try {
            // Έλεγχος αν υπάρχει ήδη εγγραφή
            $existingCert = $this->getDriverADRCertificate($driverId);
            $table = 'driver_adr_certificates';

            if ($existingCert) {
                // Ενημέρωση υπάρχουσας εγγραφής
                $sql = "UPDATE $table 
                        SET adr_type = ?, certificate_number = ?, expiry_date = ? 
                        WHERE driver_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute([
                    $adrData['adr_type'],
                    $adrData['certificate_number'],
                    $adrData['expiry_date'] ?: null,
                    $driverId
                ]);
            } else {
                // Δημιουργία νέας εγγραφής
                $sql = "INSERT INTO $table 
                        (driver_id, adr_type, certificate_number, expiry_date) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute([
                    $driverId,
                    $adrData['adr_type'],
                    $adrData['certificate_number'],
                    $adrData['expiry_date'] ?: null
                ]);
            }

            if ($result) {
                // Ενημέρωση του flag στον πίνακα drivers
                $updateFlag = $this->pdo->prepare("UPDATE drivers SET adr_certificate = 1, adr_certificate_expiry = ? WHERE id = ?");
                $updateFlag->execute([$adrData['expiry_date'] ?: null, $driverId]);
            }

            return $result;
        } catch (PDOException $e) {
            Logger::error('Error in updateDriverADRCertificate: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ανακτά τα πιστοποιητικά ADR ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array Τα πιστοποιητικά ADR του οδηγού
     */
    public function getDriverAdrCertificates($driverId)
    {
        $table = 'driver_adr_certificates';
        $sql = "SELECT * FROM $table WHERE driver_id = ?";
        return $this->query($sql, [$driverId]) ?: [];
    }

    /**
     * Μορφοποίηση των πιστοποιητικών ADR σε αναγνώσιμη μορφή
     * 
     * @param array $adrCertificates Τα πιστοποιητικά ADR
     * @return array Μορφοποιημένα πιστοποιητικά
     */
    public function formatAdrCertificates($adrCertificates)
    {
        $types = [];
        $detail = null;

        foreach ($adrCertificates as $cert) {
            $types[] = $cert['adr_type'];

            // Κρατάμε το πιο πρόσφατο πιστοποιητικό
            if (
                $detail === null ||
                (isset($cert['expiry_date']) && isset($detail['expiry_date']) &&
                    strtotime($cert['expiry_date']) > strtotime($detail['expiry_date']))
            ) {
                $detail = $cert;
            }
        }

        return [
            'has_adr' => !empty($types),
            'types' => $types,
            'types_text' => implode(', ', $types),
            'detail' => $detail
        ];
    }

    // -------------------- ΚΑΡΤΑ ΤΑΧΟΓΡΑΦΟΥ --------------------

    /**
     * Ανακτά τα στοιχεία της κάρτας ταχογράφου ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array|null Τα στοιχεία της κάρτας ταχογράφου ή null αν δεν υπάρχει
     */
    public function getDriverTachographCard($driverId)
    {
        $table = 'driver_tachograph_cards';
        $sql = "SELECT * FROM $table WHERE driver_id = ? LIMIT 1";
        return $this->queryOne($sql, [$driverId]);
    }

    /**
     * Ενημερώνει τα δεδομένα της κάρτας ταχογράφου ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $tachographData Δεδομένα της κάρτας ταχογράφου
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverTachographCard($driverId, $tachographData)
    {
        try {
            // Έλεγχος αν υπάρχει ήδη εγγραφή
            $existingCard = $this->getDriverTachographCard($driverId);
            $table = 'driver_tachograph_cards';

            if ($existingCard) {
                // Ενημέρωση υπάρχουσας εγγραφής
                $sql = "UPDATE $table SET card_number = ?, expiry_date = ? WHERE driver_id = ?";
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([
                    $tachographData['card_number'],
                    $tachographData['expiry_date'] ?: null,
                    $driverId
                ]);
            } else {
                // Δημιουργία νέας εγγραφής
                $sql = "INSERT INTO $table (driver_id, card_number, expiry_date) VALUES (?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([
                    $driverId,
                    $tachographData['card_number'],
                    $tachographData['expiry_date'] ?: null
                ]);
            }
        } catch (PDOException $e) {
            Logger::error('Error in updateDriverTachographCard: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Διαγράφει την κάρτα ταχογράφου του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteDriverTachographCard($driverId)
    {
        try {
            $table = 'driver_tachograph_cards';
            $sql = "DELETE FROM $table WHERE driver_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$driverId]);
        } catch (PDOException $e) {
            Logger::error('Error in deleteDriverTachographCard: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ανάκτηση των καρτών ταχογράφου ενός οδηγού
     * 
     * @param int $driverId Το ID του οδηγού
     * @return array Οι κάρτες ταχογράφου του οδηγού
     */
    public function getDriverTachographCards($driverId)
    {
        $table = 'driver_tachograph_cards';
        $sql = "SELECT * FROM $table WHERE driver_id = ?";
        return $this->query($sql, [$driverId]) ?: [];
    }

    /**
     * Μορφοποίηση των καρτών ταχογράφου σε αναγνώσιμη μορφή
     * 
     * @param array $tachographCards Οι κάρτες ταχογράφου
     * @return array Μορφοποιημένες κάρτες
     */
    public function formatTachographCards($tachographCards)
    {
        return [
            'has_tachograph' => !empty($tachographCards),
            'detail' => !empty($tachographCards) ? $tachographCards[0] : null
        ];
    }

    // -------------------- ΆΔΕΙΕΣ ΧΕΙΡΙΣΤΗ ΜΗΧΑΝΗΜΑΤΩΝ --------------------

    /**
     * Λαμβάνει την άδεια χειριστή μηχανημάτων έργου του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array|null Στοιχεία άδειας χειριστή ή null
     */
    public function getDriverOperatorLicense($driverId)
    {
        try {
            $table = 'driver_operator_licenses';
            $sql = "SELECT * FROM $table WHERE driver_id = ? LIMIT 1";
            return $this->queryOne($sql, [$driverId]);
        } catch (PDOException $e) {
            Logger::error("Σφάλμα λήψης άδειας χειριστή: " . $e->getMessage(), "CertificationModel");
            return null;
        }
    }

    /**
     * Διαγράφει την άδεια χειριστή μηχανημάτων έργου του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteDriverOperatorLicense($driverId)
    {
        try {
            // Πρώτα βρίσκουμε την άδεια χειριστή για να πάρουμε το ID της
            $license = $this->getDriverOperatorLicense($driverId);

            if ($license) {
                // Διαγραφή των υποειδικοτήτων
                $this->deleteDriverOperatorSubSpecialities($license['id']);

                // Διαγραφή της άδειας χειριστή
                $table = 'driver_operator_licenses';
                $sql = "DELETE FROM $table WHERE driver_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute([$driverId]);

                if ($result) {
                    // Ενημέρωση του flag στον πίνακα drivers
                    $updateFlag = $this->pdo->prepare("UPDATE drivers SET 
                        operator_license = 0, 
                        operator_license_expiry = NULL 
                        WHERE id = ?");
                    $updateFlag->execute([$driverId]);
                }

                return $result;
            }

            return true; // Δεν υπήρχε άδεια για διαγραφή
        } catch (PDOException $e) {
            Logger::error("Σφάλμα διαγραφής άδειας χειριστή: " . $e->getMessage(), "CertificationModel");
            return false;
        }
    }

    /**
     * Ενημερώνει την άδεια χειριστή μηχανημάτων έργου του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $operatorData Δεδομένα άδειας χειριστή
     * @return int|false ID της άδειας ή false σε αποτυχία
     */
    public function updateDriverOperatorLicense($driverId, $operatorData)
    {
        try {
            // Έλεγχος αν υπάρχει ήδη εγγραφή
            $existingLicense = $this->getDriverOperatorLicense($driverId);
            $table = 'driver_operator_licenses';

            if ($existingLicense) {
                // Ενημέρωση υπάρχουσας εγγραφής
                $sql = "UPDATE $table 
                        SET speciality = ?, license_number = ?, expiry_date = ? 
                        WHERE driver_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $success = $stmt->execute([
                    $operatorData['speciality'],
                    $operatorData['license_number'],
                    $operatorData['expiry_date'] ?: null,
                    $driverId
                ]);

                // Ενημέρωση του flag και της ημερομηνίας λήξης στον πίνακα drivers
                if ($success) {
                    $updateDriver = $this->pdo->prepare("UPDATE drivers SET 
                        operator_license = 1, 
                        operator_license_expiry = ? 
                        WHERE id = ?");
                    $updateDriver->execute([$operatorData['expiry_date'] ?: null, $driverId]);

                    return $existingLicense['id'];
                } else {
                    return false;
                }
            } else {
                // Δημιουργία νέας εγγραφής
                $sql = "INSERT INTO $table 
                        (driver_id, speciality, license_number, expiry_date) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $success = $stmt->execute([
                    $driverId,
                    $operatorData['speciality'],
                    $operatorData['license_number'],
                    $operatorData['expiry_date'] ?: null
                ]);

                if ($success) {
                    $licenseId = $this->pdo->lastInsertId();

                    // Ενημέρωση του flag και της ημερομηνίας λήξης στον πίνακα drivers
                    $updateDriver = $this->pdo->prepare("UPDATE drivers SET 
                        operator_license = 1, 
                        operator_license_expiry = ? 
                        WHERE id = ?");
                    $updateDriver->execute([$operatorData['expiry_date'] ?: null, $driverId]);

                    return $licenseId;
                } else {
                    return false;
                }
            }
        } catch (PDOException $e) {
            Logger::error('Error in updateDriverOperatorLicense: ' . $e->getMessage(), "CertificationModel");
            return false;
        }
    }

    /**
     * Λαμβάνει τις υποειδικότητες της άδειας χειριστή μηχανημάτων
     * 
     * @param int $operatorLicenseId ID της άδειας χειριστή
     * @return array Λίστα υποειδικοτήτων
     */
    public function getDriverOperatorSubSpecialities($operatorLicenseId)
    {
        try {
            $table = 'driver_operator_sub_specialities';
            // Έλεγχος ύπαρξης πίνακα
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE '$table'");
            if ($tableCheck->rowCount() == 0) {
                Logger::warning("Ο πίνακας $table δεν υπάρχει", "CertificationModel");
                return [];
            }

            // Έλεγχος αν η στήλη group_type υπάρχει στον πίνακα
            $columns = $this->pdo->query("SHOW COLUMNS FROM $table LIKE 'group_type'");
            $hasGroupTypeColumn = $columns->rowCount() > 0;

            // Έλεγχος ύπαρξης πίνακα groups
            $groupsTable = 'driver_operator_sub_speciality_groups';
            $hasGroupsTable = $this->checkTableExists($groupsTable);

            if ($hasGroupTypeColumn) {
                // Χρήση της στήλης group_type
                $sql = "SELECT id, sub_speciality, group_type FROM $table WHERE operator_license_id = ? ORDER BY sub_speciality";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$operatorLicenseId]);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                return $result;
            } else if ($hasGroupsTable) {
                // Συνδυασμός με τον πίνακα ομάδων
                $sql = "SELECT dos.id, dos.sub_speciality, COALESCE(dosg.group_type, 'A') as group_type 
                    FROM $table dos
                    LEFT JOIN $groupsTable dosg ON dos.id = dosg.sub_speciality_id
                    WHERE dos.operator_license_id = ?
                    ORDER BY dos.sub_speciality";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$operatorLicenseId]);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                return $result;
            } else {
                // Χωρίς πληροφορίες ομάδας
                $sql = "SELECT id, sub_speciality, 'A' as group_type FROM $table WHERE operator_license_id = ? ORDER BY sub_speciality";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$operatorLicenseId]);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                return $result;
            }
        } catch (PDOException $e) {
            Logger::error("Σφάλμα λήψης υποειδικοτήτων: " . $e->getMessage(), "CertificationModel");
            return [];
        }
    }

    /**
     * Διαγράφει τις υποειδικότητες της άδειας χειριστή μηχανημάτων
     * 
     * @param int $operatorLicenseId ID της άδειας χειριστή
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteDriverOperatorSubSpecialities($operatorLicenseId)
    {
        try {
            $table = 'driver_operator_sub_specialities';
            // Έλεγχος ύπαρξης πίνακα υποειδικοτήτων
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE '$table'");
            if ($tableCheck->rowCount() == 0) {
                return true; // Θεωρούμε επιτυχία αφού δεν υπάρχει τίποτα για διαγραφή
            }

            // Έλεγχος ύπαρξης πίνακα ομάδων
            $groupsTable = 'driver_operator_sub_speciality_groups';
            $hasGroupsTable = $this->checkTableExists($groupsTable);

            // Αν υπάρχει ο πίνακας ομάδων και δεν έχουμε foreign key constraint
            if ($hasGroupsTable) {
                try {
                    // Πρώτα βρίσκουμε τα IDs των υποειδικοτήτων
                    $findSql = "SELECT id FROM $table WHERE operator_license_id = ?";
                    $findStmt = $this->pdo->prepare($findSql);
                    $findStmt->execute([$operatorLicenseId]);
                    $subSpecialityIds = $findStmt->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($subSpecialityIds)) {
                        // Διαγραφή από τον πίνακα ομάδων
                        $idList = implode(',', array_fill(0, count($subSpecialityIds), '?'));
                        $groupDeleteSql = "DELETE FROM $groupsTable WHERE sub_speciality_id IN ($idList)";

                        $groupDeleteStmt = $this->pdo->prepare($groupDeleteSql);
                        $groupDeleteStmt->execute($subSpecialityIds);
                    }
                } catch (PDOException $e) {
                    Logger::error("Σφάλμα διαγραφής από τον πίνακα ομάδων: " . $e->getMessage(), "CertificationModel");
                }
            }

            // Διαγραφή των υποειδικοτήτων
            $sql = "DELETE FROM $table WHERE operator_license_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$operatorLicenseId]);

            return $result;
        } catch (PDOException $e) {
            Logger::error("Σφάλμα διαγραφής υποειδικοτήτων: " . $e->getMessage(), "CertificationModel");
            return false;
        }
    }

    /**
     * Προσθέτει μια υποειδικότητα στην άδεια χειριστή μηχανημάτων
     * 
     * @param int $operatorLicenseId ID της άδειας χειριστή
     * @param string $subSpeciality Κωδικός υποειδικότητας
     * @param string $groupType Τύπος ομάδας (A ή B)
     * @return bool Επιτυχία/αποτυχία
     */
    public function addDriverOperatorSubSpeciality($operatorLicenseId, $subSpeciality, $groupType = 'A')
    {
        try {
            $table = 'driver_operator_sub_specialities';
            // Έλεγχος εγκυρότητας του $groupType
            if ($groupType !== 'A' && $groupType !== 'B') {
                $groupType = 'A';
            }

            // Έλεγχος αν υπάρχει ήδη η υποειδικότητα (για αποφυγή διπλοεγγραφών)
            $checkSql = "SELECT COUNT(*) FROM $table 
                     WHERE operator_license_id = ? AND sub_speciality = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$operatorLicenseId, $subSpeciality]);
            $exists = $checkStmt->fetchColumn() > 0;

            if ($exists) {
                // Διαγραφή της υπάρχουσας εγγραφής
                $deleteSql = "DELETE FROM $table 
                         WHERE operator_license_id = ? AND sub_speciality = ?";
                $deleteStmt = $this->pdo->prepare($deleteSql);
                $deleteStmt->execute([$operatorLicenseId, $subSpeciality]);
            }

            // Έλεγχος αν η στήλη group_type υπάρχει στον πίνακα
            $columns = $this->pdo->query("SHOW COLUMNS FROM $table LIKE 'group_type'");
            $hasGroupTypeColumn = $columns->rowCount() > 0;

            // Αν δεν υπάρχει η στήλη group_type, προσπάθησε να την προσθέσεις
            if (!$hasGroupTypeColumn) {
                try {
                    $alterSql = "ALTER TABLE $table 
                            ADD COLUMN group_type CHAR(1) DEFAULT 'A' AFTER sub_speciality";
                    $this->pdo->exec($alterSql);
                    $hasGroupTypeColumn = true;
                } catch (PDOException $e) {
                    Logger::error("Σφάλμα προσθήκης στήλης group_type: " . $e->getMessage(), "CertificationModel");
                }
            }

            // Εισαγωγή της υποειδικότητας
            if ($hasGroupTypeColumn) {
                $sql = "INSERT INTO $table 
                    (operator_license_id, sub_speciality, group_type) 
                    VALUES (?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute([$operatorLicenseId, $subSpeciality, $groupType]);

                return $result;
            } else {
                // Παλιός τρόπος με χωριστό πίνακα για τις ομάδες
                // Προσθήκη της υποειδικότητας
                $sql = "INSERT INTO $table 
                    (operator_license_id, sub_speciality) 
                    VALUES (?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute([$operatorLicenseId, $subSpeciality]);

                if ($result) {
                    $subSpecialityId = $this->pdo->lastInsertId();

                    // Έλεγχος ύπαρξης πίνακα ομάδων
                    $groupsTable = 'driver_operator_sub_speciality_groups';
                    $hasGroupsTable = $this->checkTableExists($groupsTable);

                    if (!$hasGroupsTable) {
                        // Δημιουργία του πίνακα ομάδων αν δεν υπάρχει
                        try {
                            $createTableSql = "
                                CREATE TABLE IF NOT EXISTS $groupsTable (
                                    id INT NOT NULL AUTO_INCREMENT,
                                    sub_speciality_id INT NOT NULL,
                                    group_type CHAR(1) NOT NULL DEFAULT 'A',
                                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                    PRIMARY KEY (id),
                                    KEY (sub_speciality_id),
                                    FOREIGN KEY (sub_speciality_id) REFERENCES $table(id) ON DELETE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                            ";
                            $this->pdo->exec($createTableSql);
                            $hasGroupsTable = true;
                        } catch (PDOException $e) {
                            Logger::error("Σφάλμα δημιουργίας πίνακα ομάδων: " . $e->getMessage(), "CertificationModel");
                        }
                    }

                    // Προσθήκη της ομάδας
                    if ($hasGroupsTable) {
                        try {
                            $groupSql = "INSERT INTO $groupsTable 
                                     (sub_speciality_id, group_type) 
                                     VALUES (?, ?)";
                            $groupStmt = $this->pdo->prepare($groupSql);
                            $groupResult = $groupStmt->execute([$subSpecialityId, $groupType]);
                        } catch (PDOException $e) {
                            Logger::error("Σφάλμα προσθήκης στον πίνακα ομάδων: " . $e->getMessage(), "CertificationModel");
                        }
                    }
                }

                return $result;
            }
        } catch (PDOException $e) {
            Logger::error("Γενικό σφάλμα προσθήκης υποειδικότητας: " . $e->getMessage(), "CertificationModel");
            return false;
        }
    }

    /**
     * Ανακτά τις άδειες χειριστή μηχανημάτων έργου ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array Οι άδειες χειριστή μηχανημάτων έργου του οδηγού
     */
    public function getDriverOperatorLicenses($driverId)
    {
        try {
            $licenseTable = 'driver_operator_licenses';
            $specTable = 'driver_operator_sub_specialities';
            $sql = "SELECT ol.*, GROUP_CONCAT(DISTINCT os.sub_speciality) AS sub_specialities
                FROM $licenseTable ol
                LEFT JOIN $specTable os ON ol.id = os.operator_license_id
                WHERE ol.driver_id = :driver_id
                GROUP BY ol.id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['driver_id' => $driverId]);
            $operatorLicenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Ανάκτηση των υποειδικοτήτων για κάθε άδεια
            foreach ($operatorLicenses as &$license) {
                $license['sub_specialities'] = $this->getDriverOperatorSubSpecialities($license['id']);
            }

            return $operatorLicenses;
        } catch (PDOException $e) {
            Logger::error('Error getting driver operator licenses: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Μορφοποίηση των αδειών χειριστή σε αναγνώσιμη μορφή
     * 
     * @param array $operatorLicenses Οι άδειες χειριστή
     * @return array Μορφοποιημένες άδειες
     */
    public function formatOperatorLicenses($operatorLicenses)
    {
        $specialities = [];
        $subSpecialities = [];
        $groupedSubSpecialities = [];

        foreach ($operatorLicenses as $license) {
            if (!empty($license['speciality'])) {
                $specialities[] = $license['speciality'];

                if (!empty($license['sub_specialities'])) {
                    foreach ($license['sub_specialities'] as $subSpec) {
                        $subSpecialities[] = $subSpec['sub_speciality'];

                        $group = $subSpec['group_type'];
                        if (!isset($groupedSubSpecialities[$group])) {
                            $groupedSubSpecialities[$group] = [];
                        }
                        $groupedSubSpecialities[$group][] = $subSpec['sub_speciality'];
                    }
                }
            }
        }

        // Μορφοποίηση των ομαδοποιημένων υποκατηγοριών
        $groupedText = [];
        foreach ($groupedSubSpecialities as $group => $items) {
            $groupedText[] = "Ομάδα {$group}: " . implode(', ', $items);
        }

        return [
            'has_operator_license' => !empty($specialities),
            'specialities' => $specialities,
            'specialities_text' => implode(', ', $specialities),
            'sub_specialities' => $subSpecialities,
            'sub_specialities_text' => implode(', ', $subSpecialities),
            'grouped_text' => implode(' | ', $groupedText),
            'details' => !empty($operatorLicenses) ? $operatorLicenses[0] : null
        ];
    }

    // -------------------- ΕΙΔΙΚΈΣ ΆΔΕΙΕΣ --------------------

    /**
     * Ανακτά τις ειδικές άδειες ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array Οι ειδικές άδειες του οδηγού
     */
    public function getDriverSpecialLicenses($driverId)
    {
        try {
            $table = 'driver_special_licenses';
            $sql = "SELECT * FROM $table WHERE driver_id = :driver_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['driver_id' => $driverId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('Error getting driver special licenses: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Λαμβάνει μια συγκεκριμένη ειδική άδεια του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param string $licenseType Τύπος ειδικής άδειας
     * @return array|null Στοιχεία ειδικής άδειας ή null
     */
    public function getDriverSpecialLicenseByType($driverId, $licenseType)
    {
        try {
            $table = 'driver_special_licenses';
            $sql = "SELECT * FROM $table WHERE driver_id = ? AND license_type = ? LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$driverId, $licenseType]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('Error in getDriverSpecialLicenseByType: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Ενημερώνει τις ειδικές άδειες του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $specialLicenses Λίστα με ειδικές άδειες
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverSpecialLicenses($driverId, $specialLicenses)
    {
        try {
            // Διαγραφή όλων των προηγούμενων εγγραφών
            $this->deleteDriverSpecialLicenses($driverId);

            // Αν δεν υπάρχουν νέες άδειες, επιστρέφουμε true
            if (empty($specialLicenses)) {
                return true;
            }

            $table = 'driver_special_licenses';
            // Εισαγωγή των νέων αδειών
            $sql = "INSERT INTO $table (driver_id, license_type, license_number, expiry_date, details) 
                    VALUES (?, ?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);

            foreach ($specialLicenses as $license) {
                if (empty($license['license_type'])) {
                    continue; // Παραλείπουμε εγγραφές χωρίς τύπο άδειας
                }

                $result = $stmt->execute([
                    $driverId,
                    $license['license_type'],
                    $license['license_number'] ?: null,
                    $license['expiry_date'] ?: null,
                    $license['details'] ?: null
                ]);

                if (!$result) {
                    Logger::error('Failed to insert special license: ' . print_r($license, true));
                    return false;
                }
            }

            return true;
        } catch (PDOException $e) {
            Logger::error('Error in updateDriverSpecialLicenses: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Διαγράφει όλες τις ειδικές άδειες του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteDriverSpecialLicenses($driverId)
    {
        try {
            $table = 'driver_special_licenses';
            $sql = "DELETE FROM $table WHERE driver_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$driverId]);
        } catch (PDOException $e) {
            Logger::error('Error in deleteDriverSpecialLicenses: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Διαγράφει μια συγκεκριμένη ειδική άδεια του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param string $licenseType Τύπος ειδικής άδειας
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteDriverSpecialLicenseByType($driverId, $licenseType)
    {
        try {
            $table = 'driver_special_licenses';
            $sql = "DELETE FROM $table WHERE driver_id = ? AND license_type = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$driverId, $licenseType]);
        } catch (PDOException $e) {
            Logger::error('Error in deleteDriverSpecialLicenseByType: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Προσθέτει μια ειδική άδεια για τον οδηγό
     * 
     * @param int $driverId ID του οδηγού
     * @param string $licenseType Τύπος ειδικής άδειας
     * @param string $licenseNumber Αριθμός άδειας
     * @param string $expiryDate Ημερομηνία λήξης
     * @param string $details Λεπτομέρειες άδειας
     * @return bool Επιτυχία/αποτυχία
     */
    public function addDriverSpecialLicense($driverId, $licenseType, $licenseNumber, $expiryDate, $details = null)
    {
        try {
            $table = 'driver_special_licenses';
            $sql = "INSERT INTO $table (driver_id, license_type, license_number, expiry_date, details) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$driverId, $licenseType, $licenseNumber, $expiryDate, $details]);
        } catch (PDOException $e) {
            Logger::error('Error in addDriverSpecialLicense: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Μορφοποίηση των ειδικών αδειών σε αναγνώσιμη μορφή
     * 
     * @param array $specialLicenses Οι ειδικές άδειες
     * @return array Μορφοποιημένες άδειες
     */
    public function formatSpecialLicenses($specialLicenses)
    {
        $types = [];

        foreach ($specialLicenses as $license) {
            $types[] = $license['license_type'];
        }

        return [
            'has_special_licenses' => !empty($specialLicenses),
            'types' => $types,
            'details' => $specialLicenses
        ];
    }

    /**
     * Ανάκτηση ολοκληρωμένων πληροφοριών αδειών και πιστοποιήσεων για εμφάνιση
     * 
     * @param int $driverId Το ID του οδηγού
     * @return array Πλήρεις πληροφορίες πιστοποιήσεων
     */
    public function getDriverCertificationsDetails($driverId)
    {
        $result = [
            'success' => false,
            'driver' => null,
            'licenses' => [],
            'adr_certificates' => [],
            'operator_licenses' => [],
            'tachograph_cards' => [],
            'special_licenses' => []
        ];

        try {
            // Ανάκτηση βασικών πληροφοριών οδηγού
            $sql = "SELECT * FROM drivers WHERE id = :driver_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['driver_id' => $driverId]);
            $driver = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$driver) {
                return $result;
            }

            $result['driver'] = $driver;
            $result['success'] = true;

            // 1. Άδειες οδήγησης
            $licenseTable = 'driver_licenses';
            $sql = "SELECT * FROM $licenseTable WHERE driver_id = :driver_id ORDER BY license_type";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['driver_id' => $driverId]);
            $result['licenses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 2. Πιστοποιητικά ADR
            $adrTable = 'driver_adr_certificates';
            $sql = "SELECT * FROM $adrTable WHERE driver_id = :driver_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['driver_id' => $driverId]);
            $result['adr_certificates'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Άδειες χειριστή
            $operatorTable = 'driver_operator_licenses';
            $sql = "SELECT ol.* FROM $operatorTable ol WHERE ol.driver_id = :driver_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['driver_id' => $driverId]);
            $operatorLicenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Για κάθε άδεια χειριστή, ανάκτηση των υποκατηγοριών
            $subSpecTable = 'driver_operator_sub_specialities';
            foreach ($operatorLicenses as &$license) {
                $sql = "SELECT oss.* FROM $subSpecTable oss 
                    WHERE oss.operator_license_id = :license_id 
                    ORDER BY oss.group_type, oss.sub_speciality";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(['license_id' => $license['id']]);
                $license['sub_specialities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $result['operator_licenses'] = $operatorLicenses;

            // 4. Κάρτες ταχογράφου
            $tachoTable = 'driver_tachograph_cards';
            $sql = "SELECT * FROM $tachoTable WHERE driver_id = :driver_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['driver_id' => $driverId]);
            $result['tachograph_cards'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 5. Ειδικές άδειες
            $specialTable = 'driver_special_licenses';
            $sql = "SELECT * FROM $specialTable WHERE driver_id = :driver_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['driver_id' => $driverId]);
            $result['special_licenses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $result;
        } catch (PDOException $e) {
            Logger::error('Error getting driver certifications: ' . $e->getMessage());
            return $result;
        }
    }

    /**
     * Λαμβάνει τους οδηγούς με άδειες που λήγουν σύντομα (ADR και Operator)
     * 
     * @return array Λίστα οδηγών με άδειες που λήγουν
     */
    public function getDriversWithExpiringCertifications()
    {
        try {
            $expiryPeriods = [
                'adr_certificate' => [
                    'period' => '1 year',
                    'table' => 'driver_adr_certificates',
                    'join' => 'dac.driver_id = d.id',
                    'expiry_field' => 'dac.expiry_date',
                    'type_field' => 'dac.adr_type'
                ],
                'operator_license' => [
                    'period' => '11 years',
                    'table' => 'driver_operator_licenses',
                    'join' => 'dol.driver_id = d.id',
                    'expiry_field' => 'dol.expiry_date',
                    'type_field' => 'dol.speciality'
                ]
            ];

            $results = [];

            foreach ($expiryPeriods as $licenseType => $config) {
                $targetDate = date('Y-m-d', strtotime('+' . $config['period']));

                $sql = "
                    SELECT d.id, d.first_name, d.last_name, d.email, 
                           '$licenseType' as type, 
                           {$config['expiry_field']} as expiry_date,
                           {$config['type_field']} as license_type
                    FROM drivers d 
                    JOIN {$config['table']} ON {$config['join']} 
                    WHERE {$config['expiry_field']} <= ? 
                      AND {$config['expiry_field']} >= CURRENT_DATE()
                ";

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$targetDate]);
                $results[$licenseType] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $results;
        } catch (PDOException $e) {
            Logger::error('Error in getDriversWithExpiringCertifications: ' . $e->getMessage());
            return [
                'adr_certificates' => [],
                'operator_licenses' => []
            ];
        }
    }
}
