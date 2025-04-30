<?php

namespace Drivejob\Services;

use Drivejob\Models\DriversModel;
use Drivejob\Core\Logger;

class DriverCertificationService
{
    private $driversModel;

    public function __construct(DriversModel $driversModel)
    {
        $this->driversModel = $driversModel;
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
                    'defensive_driving',
                    'eco_driving',
                    'night_driving',
                    'mountain_driving',
                    'extreme_conditions',
                    'loading_securing',
                    'emergency_response',
                    'first_aid',
                    'dangerous_goods',
                    'tacograph_compliance',
                    'customer_service',
                    'time_management',
                    'route_planning',
                    'conflict_resolution',
                    'multilingual',
                    'vehicle_maintenance',
                    'troubleshooting',
                    'digital_tachograph',
                    'gps_systems',
                    'logistics_software'
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

                // Αποθήκευση των δεξιοτήτων
                $this->driversModel->updateDriverSkills($driverId, $skillsData);
            } else {
                // Αν δεν επιλέχθηκαν δεξιότητες, μηδενίζουμε όλες τις δεξιότητες
                $emptySkills = [];
                $allSkillFields = [
                    'defensive_driving',
                    'eco_driving',
                    'night_driving',
                    'mountain_driving',
                    'extreme_conditions',
                    'loading_securing',
                    'emergency_response',
                    'first_aid',
                    'dangerous_goods',
                    'tacograph_compliance',
                    'customer_service',
                    'time_management',
                    'route_planning',
                    'conflict_resolution',
                    'multilingual',
                    'vehicle_maintenance',
                    'troubleshooting',
                    'digital_tachograph',
                    'gps_systems',
                    'logistics_software'
                ];

                foreach ($allSkillFields as $field) {
                    $emptySkills[$field] = 0;
                }

                // Αποθήκευση μηδενικών δεξιοτήτων
                $this->driversModel->updateDriverSkills($driverId, $emptySkills);
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
                $this->driversModel->updateProfile($driverId, $languageData);
            }

            // 3. Ενημέρωση σεμιναρίων και πρόσθετων δεξιοτήτων
            $additionalData = [
                'training_seminars' => isset($formData['training_seminars']) ? 1 : 0,
                'training_details' => $formData['training_details'] ?? '',
                'additional_skills' => $formData['additional_skills'] ?? ''
            ];

            // Ενημέρωση των πρόσθετων δεδομένων
            $this->driversModel->updateProfile($driverId, $additionalData);

            // 4. Ενημέρωση πιστοποιήσεων
            if (isset($formData['certifications']) && is_array($formData['certifications'])) {
                $this->updateCertifications($driverId, $formData['certifications']);
            } else {
                // Αν δεν υπάρχουν πιστοποιήσεις, διαγράφουμε τις υπάρχουσες
                $this->driversModel->deleteDriverCertifications($driverId);
            }

            // 5. Ενημέρωση εμπειρίας οχημάτων
            if (isset($formData['vehicle_experience']) && is_array($formData['vehicle_experience'])) {
                $this->driversModel->updateDriverVehicleExperience($driverId, $formData['vehicle_experience']);
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
            $this->driversModel->deleteDriverCertifications($driverId);

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
                $this->driversModel->addDriverCertifications($driverId, $processedCertifications);
            }

            return true;
        } catch (\Exception $e) {
            Logger::error('Error in updateCertifications: ' . $e->getMessage());
            return false;
        }
    }
}
