<?php

namespace Drivejob\Services\Driver;

use PDO;
use Drivejob\Core\Logger;
use Drivejob\Models\Driver\SkillModel;
use Drivejob\Models\Driver\CertificationModel;
use Drivejob\Models\Driver\ProfileModel;

class DriverCertificationService
{
    private $pdo;
    private $skillModel;
    private $certificationModel;
    private $profileModel;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->skillModel = new SkillModel($pdo);
        $this->certificationModel = new CertificationModel($pdo);
        $this->profileModel = new ProfileModel($pdo);
    }

    /** Όλες οι στήλες-δεξιότητες του πίνακα driver_skills. */
    public const SKILL_FIELDS = [
        // Οδηγικές Ικανότητες
        'defensive_driving', 'eco_driving', 'night_driving', 'mountain_driving',
        'extreme_conditions', 'precision_handling',
        // Ασφάλεια & Συμμόρφωση
        'loading_securing', 'emergency_response', 'first_aid', 'dangerous_goods',
        'tacograph_compliance', 'fire_safety', 'vehicle_inspection',
        // Επαγγελματισμός
        'customer_service', 'time_management', 'route_planning', 'conflict_resolution',
        'multilingual', 'report_writing', 'inspection_behavior', 'border_crossing',
        // Τεχνικές Γνώσεις
        'vehicle_maintenance', 'troubleshooting', 'digital_tachograph', 'gps_systems',
        'logistics_software', 'technical_terms', 'equipment_handling', 'checklists_usage',
    ];

    /**
     * Αποθηκεύει ΜΟΝΟ τα checkboxes δεξιοτήτων (καρτέλα «Δεξιότητες»).
     *
     * Εστιασμένη μέθοδος (25/08/2026): η μεγάλη updateSkills() δεν
     * καλούνταν ΠΟΤΕ από τη ροή αποθήκευσης — δεξιότητες, γλώσσες και
     * σεμινάρια πετιούνταν σιωπηλά. Ο controller την καλεί ΜΟΝΟ όταν η
     * φόρμα δηλώνει την καρτέλα (skills_submitted), ώστε ένα POST χωρίς
     * την καρτέλα να μη μηδενίζει ό,τι υπάρχει.
     */
    public function updateSkillCheckboxes($driverId, array $post): bool
    {
        $skillsData = array_fill_keys(self::SKILL_FIELDS, 0);

        foreach ((array) ($post['skills'] ?? []) as $skill => $value) {
            if (array_key_exists($skill, $skillsData)) {
                $skillsData[$skill] = 1;
            }
        }

        // Σημάνσεις «μόνο για εμπορευματικές» — ίδια σημασιολογία με πριν.
        $freightOnly = $post['freight_only'] ?? null;
        if (is_array($freightOnly)) {
            $skillsData['freight_only_loading'] = isset($freightOnly['loading_securing']) ? 1 : 0;
            $skillsData['freight_only_dangerous'] = isset($freightOnly['dangerous_goods']) ? 1 : 0;
        } else {
            $skillsData['freight_only_loading'] = 1;
            $skillsData['freight_only_dangerous'] = 1;
        }

        return (bool) $this->skillModel->updateDriverSkills($driverId, $skillsData);
    }

    /**
     * Ενημερώνει τις δεξιότητες και τις πιστοποιήσεις του οδηγού
     *
     * @param int $driverId ID του οδηγού
     * @param array $formData Δεδομένα από τη φόρμα
     * @return bool Επιτυχία ή αποτυχία
     */
    public function updateSkills($driverId, $formData)
    {
        try {
            // Καταγραφή για debug
            Logger::info('Updating skills for driver ' . $driverId);

            // 1. Ενημέρωση δεξιοτήτων οδηγού
            if (isset($formData['skills'])) {
                $skillsData = [];
                $allSkillFields = [
                    // Οδηγικές Ικανότητες
                    'defensive_driving',
                    'eco_driving',
                    'night_driving',
                    'mountain_driving',
                    'extreme_conditions',
                    'precision_handling',

                    // Ασφάλεια & Συμμόρφωση
                    'loading_securing',
                    'emergency_response',
                    'first_aid',
                    'dangerous_goods',
                    'tacograph_compliance',
                    'fire_safety',
                    'vehicle_inspection',

                    // Επαγγελματισμός
                    'customer_service',
                    'time_management',
                    'route_planning',
                    'conflict_resolution',
                    'multilingual',
                    'report_writing',
                    'inspection_behavior',
                    'border_crossing',

                    // Τεχνικές Γνώσεις
                    'vehicle_maintenance',
                    'troubleshooting',
                    'digital_tachograph',
                    'gps_systems',
                    'logistics_software',
                    'technical_terms',
                    'equipment_handling',
                    'checklists_usage',

                    // Επιλογές μόνο για εμπορευματικές μεταφορές
                    'freight_only_loading',
                    'freight_only_dangerous'
                ];

                // Αρχικοποίηση όλων των δεξιοτήτων σε 0
                foreach ($allSkillFields as $field) {
                    $skillsData[$field] = 0;
                }

                // Ενημέρωση των επιλεγμένων δεξιοτήτων
                foreach ($formData['skills'] as $skill => $value) {
                    if (in_array($skill, $allSkillFields)) {
                        $skillsData[$skill] = 1;
                    }
                }

                // Διαχείριση των επιλογών που είναι μόνο για εμπορευματικές μεταφορές
                if (isset($formData['freight_only'])) {
                    // Αντιστοίχιση των ονομάτων των checkboxes με τις στήλες της βάσης δεδομένων
                    $freightOnlyMapping = [
                        'loading_securing' => 'freight_only_loading',
                        'dangerous_goods' => 'freight_only_dangerous'
                    ];

                    foreach ($formData['freight_only'] as $key => $value) {
                        if (isset($freightOnlyMapping[$key])) {
                            $skillsData[$freightOnlyMapping[$key]] = 1;
                        }
                    }
                } else {
                    // Προεπιλεγμένες τιμές αν δεν υπάρχουν επιλογές
                    $skillsData['freight_only_loading'] = 1;
                    $skillsData['freight_only_dangerous'] = 1;
                }

                // Αποθήκευση των δεξιοτήτων
                $this->skillModel->updateDriverSkills($driverId, $skillsData);
            } else {
                // Αν δεν επιλέχθηκαν δεξιότητες, μηδενίζουμε όλες τις δεξιότητες
                $emptySkills = [];
                $allSkillFields = [
                    // Οδηγικές Ικανότητες
                    'defensive_driving',
                    'eco_driving',
                    'night_driving',
                    'mountain_driving',
                    'extreme_conditions',
                    'precision_handling',

                    // Ασφάλεια & Συμμόρφωση
                    'loading_securing',
                    'emergency_response',
                    'first_aid',
                    'dangerous_goods',
                    'tacograph_compliance',
                    'fire_safety',
                    'vehicle_inspection',

                    // Επαγγελματισμός
                    'customer_service',
                    'time_management',
                    'route_planning',
                    'conflict_resolution',
                    'multilingual',
                    'report_writing',
                    'inspection_behavior',
                    'border_crossing',

                    // Τεχνικές Γνώσεις
                    'vehicle_maintenance',
                    'troubleshooting',
                    'digital_tachograph',
                    'gps_systems',
                    'logistics_software',
                    'technical_terms',
                    'equipment_handling',
                    'checklists_usage',

                    // Επιλογές μόνο για εμπορευματικές μεταφορές
                    'freight_only_loading',
                    'freight_only_dangerous'
                ];

                foreach ($allSkillFields as $field) {
                    $emptySkills[$field] = 0;
                }

                // Προεπιλεγμένες τιμές για τις επιλογές που είναι μόνο για εμπορευματικές μεταφορές
                // Αντιστοίχιση των ονομάτων των checkboxes με τις στήλες της βάσης δεδομένων
                $emptySkills['freight_only_loading'] = 1;
                $emptySkills['freight_only_dangerous'] = 1;

                // Αποθήκευση μηδενικών δεξιοτήτων
                $this->skillModel->updateDriverSkills($driverId, $emptySkills);
            }

            // 2. Ενημέρωση γλωσσικών ικανοτήτων
            if (isset($formData['languages'])) {
                $languageData = [
                    'language_greek' => $formData['languages']['greek'] ?? '',
                    'language_english' => $formData['languages']['english'] ?? '',
                    'language_german' => $formData['languages']['german'] ?? '',
                    'language_french' => $formData['languages']['french'] ?? '',
                    'language_italian' => $formData['languages']['italian'] ?? '',
                    'language_other_name' => $formData['languages']['other_name'] ?? '',
                    'language_other_level' => $formData['languages']['other_level'] ?? ''
                ];

                // Ενημέρωση των γλωσσικών ικανοτήτων
                $this->profileModel->updateProfile($driverId, $languageData);
            }

            // 3. Ενημέρωση σεμιναρίων και πρόσθετων δεξιοτήτων
            $additionalData = [
                'training_seminars' => isset($formData['training_seminars']) ? 1 : 0,
                'training_details' => $formData['training_details'] ?? '',
                'additional_skills' => $formData['additional_skills'] ?? ''
            ];

            // Ενημέρωση των πρόσθετων δεδομένων
            $this->profileModel->updateProfile($driverId, $additionalData);

            // 4. Ενημέρωση πιστοποιήσεων
            if (isset($formData['certifications']) && is_array($formData['certifications'])) {
                $this->updateCertifications($driverId, $formData['certifications']);
            } else {
                // Αν δεν υπάρχουν πιστοποιήσεις, διαγράφουμε τις υπάρχουσες
                $this->certificationModel->deleteDriverCertifications($driverId);
            }

            // 5. Ενημέρωση εμπειρίας οχημάτων
            if (isset($formData['vehicle_experience']) && is_array($formData['vehicle_experience'])) {
                $this->skillModel->updateDriverVehicleExperience($driverId, $formData['vehicle_experience']);
            }

            return true;
        } catch (\Exception $e) {
            Logger::error('Error in updateSkills: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημερώνει τις πιστοποιήσεις του οδηγού
     *
     * @param int $driverId ID του οδηγού
     * @param array $certifications Δεδομένα πιστοποιήσεων
     * @return bool Επιτυχία ή αποτυχία
     */
    private function updateCertifications($driverId, $certifications)
    {
        try {
            // Διαγραφή προηγούμενων πιστοποιήσεων
            $this->certificationModel->deleteDriverCertifications($driverId);

            $processedCertifications = [];

            foreach ($certifications as $cert) {
                if (!empty($cert['title'])) {
                    $processedCertifications[] = [
                        'title' => $cert['title'],
                        'provider' => $cert['provider'] ?? '',
                        'date' => $cert['date'] ?? null,
                        'expiry' => $cert['expiry'] ?? null,
                        'description' => $cert['description'] ?? ''
                    ];
                }
            }

            if (!empty($processedCertifications)) {
                $this->certificationModel->addDriverCertifications($driverId, $processedCertifications);
            }

            return true;
        } catch (\Exception $e) {
            Logger::error('Error in updateCertifications: ' . $e->getMessage());
            return false;
        }
    }
}
