<?php

namespace Drivejob\Services\JobListing;

use Drivejob\Core\Exceptions\ValidationException;
use Drivejob\Core\Exceptions\DatabaseException;
use Drivejob\Repositories\JobListingRepository;
use Drivejob\Repositories\JobTagRepository;

/**
 * Υπηρεσία διαχείρισης αγγελιών
 */
class JobListingService implements JobListingServiceInterface
{
    private $jobListingRepository;
    private $jobTagRepository;

    /**
     * Constructor
     *
     * @param JobListingRepository $jobListingRepository
     * @param JobTagRepository $jobTagRepository
     */
    public function __construct(
        JobListingRepository $jobListingRepository,
        JobTagRepository $jobTagRepository = null
    ) {
        $this->jobListingRepository = $jobListingRepository;
        $this->jobTagRepository = $jobTagRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function createJobListing(array $data)
    {
        // Επικύρωση δεδομένων
        $this->validateJobListingData($data);

        // Δημιουργία της αγγελίας
        $jobListingId = $this->jobListingRepository->create($data);

        // Δημιουργία των tags
        if (isset($data['tags']) && is_array($data['tags']) && $this->jobTagRepository !== null) {
            foreach ($data['tags'] as $tag) {
                $tagData = [
                    'job_listing_id' => $jobListingId,
                    'tag' => $tag
                ];
                $this->jobTagRepository->create($tagData);
            }
        }

        return $jobListingId;
    }

    /**
     * {@inheritdoc}
     */
    public function updateJobListing(int $id, array $data)
    {
        // Επικύρωση δεδομένων
        $this->validateJobListingData($data);

        // Ενημέρωση της αγγελίας
        $result = $this->jobListingRepository->update($id, $data);

        // Ενημέρωση των tags
        if (isset($data['tags']) && is_array($data['tags']) && $this->jobTagRepository !== null) {
            // Διαγραφή των υπαρχόντων tags
            $this->jobTagRepository->deleteByJobListing($id);

            // Δημιουργία των νέων tags
            foreach ($data['tags'] as $tag) {
                $tagData = [
                    'job_listing_id' => $id,
                    'tag' => $tag
                ];
                $this->jobTagRepository->create($tagData);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteJobListing(int $id)
    {
        // Διαγραφή των tags
        if ($this->jobTagRepository !== null) {
            $this->jobTagRepository->deleteByJobListing($id);
        }

        // Διαγραφή της αγγελίας
        return $this->jobListingRepository->delete($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findJobListing(int $id)
    {
        // Εύρεση της αγγελίας
        $jobListing = $this->jobListingRepository->find($id);

        if (!$jobListing) {
            return null;
        }

        // Εύρεση των tags
        if ($this->jobTagRepository !== null) {
            $jobListing['tags'] = $this->jobTagRepository->findByJobListing($id);
        }

        return $jobListing;
    }

    /**
     * {@inheritdoc}
     */
    public function searchJobListings(array $criteria, int $page = 1, int $limit = 10)
    {
        return $this->jobListingRepository->searchListings($criteria, $page, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function incrementViews(int $id)
    {
        return $this->jobListingRepository->incrementViews($id);
    }

    /**
     * {@inheritdoc}
     */
    public function isOwner(int $id, int $userId, string $userRole)
    {
        $jobListing = $this->jobListingRepository->find($id);

        if (!$jobListing) {
            return false;
        }

        if ($userRole === 'driver' && $jobListing['driver_id'] === $userId) {
            return true;
        }

        if ($userRole === 'company' && $jobListing['company_id'] === $userId) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function validateJobListingData(array $data)
    {
        $errors = [];

        // Έλεγχος υποχρεωτικών πεδίων
        $requiredFields = ['title', 'description', 'location', 'job_type', 'job_category'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[$field] = "Το πεδίο $field είναι υποχρεωτικό.";
            }
        }

        // Έλεγχος μήκους πεδίων
        $fieldLengths = [
            'title' => 100,
            'location' => 100,
            'contact_email' => 100,
            'contact_phone' => 20
        ];

        foreach ($fieldLengths as $field => $maxLength) {
            if (isset($data[$field]) && strlen($data[$field]) > $maxLength) {
                $errors[$field] = "Το πεδίο $field δεν μπορεί να υπερβαίνει τους $maxLength χαρακτήρες.";
            }
        }

        // Έλεγχος τύπου αγγελίας
        $validJobCategories = ['cargo_transport', 'passenger_transport', 'machinery_operator', 'machinery_assistant', 'other'];
        if (isset($data['job_category']) && !in_array($data['job_category'], $validJobCategories)) {
            $errors['job_category'] = "Ο τύπος αγγελίας δεν είναι έγκυρος. Επιτρεπτές τιμές: " . implode(', ', $validJobCategories);
        }

        // Έλεγχος τύπου εργασίας
        $validJobTypes = ['full_time', 'part_time', 'contract', 'temporary', 'seasonal', 'freelance', 'internship'];
        if (isset($data['job_type']) && !in_array($data['job_type'], $validJobTypes)) {
            $errors['job_type'] = "Ο τύπος εργασίας δεν είναι έγκυρος. Επιτρεπτές τιμές: " . implode(', ', $validJobTypes);
        }

        // Έλεγχος εύρους μισθού
        $validSalaryRanges = ['0-1000', '1000-1500', '1500-2000', '2000-2500', '2500-3000', '3000+', 'negotiable'];
        if (isset($data['salary_range']) && !empty($data['salary_range']) && !in_array($data['salary_range'], $validSalaryRanges)) {
            $errors['salary_range'] = "Το εύρος μισθού δεν είναι έγκυρο. Επιτρεπτές τιμές: " . implode(', ', $validSalaryRanges);
        }

        // Έλεγχος περιόδου μισθού
        $validSalaryPeriods = ['hour', 'day', 'week', 'month', 'year'];
        if (isset($data['salary_period']) && !empty($data['salary_period']) && !in_array($data['salary_period'], $validSalaryPeriods)) {
            $errors['salary_period'] = "Η περίοδος μισθού δεν είναι έγκυρη. Επιτρεπτές τιμές: " . implode(', ', $validSalaryPeriods);
        }

        // Έλεγχος ελάχιστου και μέγιστου μισθού
        if (isset($data['salary_min']) && isset($data['salary_max']) && $data['salary_min'] > $data['salary_max']) {
            $errors['salary_min'] = "Ο ελάχιστος μισθός δεν μπορεί να είναι μεγαλύτερος από τον μέγιστο.";
        }

        // Έλεγχος διαθεσιμότητας
        $validAvailabilities = ['immediate', '1_week', '2_weeks', '1_month', 'negotiable'];
        if (isset($data['availability']) && !empty($data['availability']) && !in_array($data['availability'], $validAvailabilities)) {
            $errors['availability'] = "Η διαθεσιμότητα δεν είναι έγκυρη. Επιτρεπτές τιμές: " . implode(', ', $validAvailabilities);
        }

        // Έλεγχος ημερομηνίας λήξης
        if (isset($data['expires_at']) && !empty($data['expires_at'])) {
            $expiresAt = strtotime($data['expires_at']);
            $now = time();
            if ($expiresAt === false) {
                $errors['expires_at'] = "Η ημερομηνία λήξης δεν είναι έγκυρη.";
            } elseif ($expiresAt < $now) {
                $errors['expires_at'] = "Η ημερομηνία λήξης δεν μπορεί να είναι στο παρελθόν.";
            }
        }

        // Έλεγχος email
        if (isset($data['contact_email']) && !empty($data['contact_email']) && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['contact_email'] = "Το email επικοινωνίας δεν είναι έγκυρο.";
        }

        // Έλεγχος τηλεφώνου
        if (isset($data['contact_phone']) && !empty($data['contact_phone']) && !preg_match('/^[0-9+\-\s()]{6,20}$/', $data['contact_phone'])) {
            $errors['contact_phone'] = "Το τηλέφωνο επικοινωνίας δεν είναι έγκυρο.";
        }

        // Έλεγχος ετών εμπειρίας
        if (isset($data['experience_years']) && !is_numeric($data['experience_years'])) {
            $errors['experience_years'] = "Τα έτη εμπειρίας πρέπει να είναι αριθμός.";
        } elseif (isset($data['experience_years']) && $data['experience_years'] < 0) {
            $errors['experience_years'] = "Τα έτη εμπειρίας δεν μπορούν να είναι αρνητικά.";
        }

        // Έλεγχος τύπου αγγελίας (job_offer ή job_search)
        if (isset($data['listing_type']) && !in_array($data['listing_type'], ['job_offer', 'job_search'])) {
            $errors['listing_type'] = "Ο τύπος αγγελίας πρέπει να είναι 'job_offer' ή 'job_search'.";
        }

        // Έλεγχος τύπων οχημάτων
        if (isset($data['vehicle_types']) && !empty($data['vehicle_types'])) {
            $vehicleTypes = is_array($data['vehicle_types']) ? $data['vehicle_types'] : explode(',', $data['vehicle_types']);
            $validVehicleTypes = ['car', 'van', 'truck', 'bus', 'motorcycle', 'tractor', 'excavator', 'crane', 'forklift', 'other'];
            foreach ($vehicleTypes as $vehicleType) {
                if (!in_array($vehicleType, $validVehicleTypes)) {
                    $errors['vehicle_types'] = "Ο τύπος οχήματος '$vehicleType' δεν είναι έγκυρος. Επιτρεπτές τιμές: " . implode(', ', $validVehicleTypes);
                    break;
                }
            }
        }

        // Αν υπάρχουν σφάλματα, ρίχνουμε exception
        if (!empty($errors)) {
            throw new ValidationException("Τα δεδομένα της αγγελίας δεν είναι έγκυρα.", $errors);
        }
    }
}
