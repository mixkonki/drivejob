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

        // Έλεγχος τύπου αγγελίας
        if (isset($data['job_category']) && !in_array($data['job_category'], ['cargo_transport', 'passenger_transport', 'machinery_operator', 'machinery_assistant'])) {
            $errors['job_category'] = "Ο τύπος αγγελίας δεν είναι έγκυρος.";
        }

        // Έλεγχος τύπου εργασίας
        if (isset($data['job_type']) && !in_array($data['job_type'], ['full_time', 'part_time', 'contract', 'temporary', 'seasonal'])) {
            $errors['job_type'] = "Ο τύπος εργασίας δεν είναι έγκυρος.";
        }

        // Έλεγχος εύρους μισθού
        if (isset($data['salary_range']) && !empty($data['salary_range']) && !in_array($data['salary_range'], ['0-1000', '1000-1500', '1500-2000', '2000-2500', '2500+'])) {
            $errors['salary_range'] = "Το εύρος μισθού δεν είναι έγκυρο.";
        }

        // Έλεγχος διαθεσιμότητας
        if (isset($data['availability']) && !empty($data['availability']) && !in_array($data['availability'], ['immediate', '1_week', '2_weeks', '1_month', 'negotiable'])) {
            $errors['availability'] = "Η διαθεσιμότητα δεν είναι έγκυρη.";
        }

        // Αν υπάρχουν σφάλματα, ρίχνουμε exception
        if (!empty($errors)) {
            throw new ValidationException("Τα δεδομένα της αγγελίας δεν είναι έγκυρα.", $errors);
        }
    }
}
