<?php

namespace Drivejob\Services\Rating;

use PDO;
use Drivejob\Core\Logger;
use Drivejob\Core\Exceptions\ValidationException;
use Drivejob\Core\Exceptions\DatabaseException;
use Drivejob\Models\Driver\RatingModel;
use Drivejob\Models\Company\CompanyRatingModel;

/**
 * Υπηρεσία για τη διαχείριση των αξιολογήσεων
 */
class RatingService implements RatingServiceInterface
{
    /**
     * @var RatingModel Το μοντέλο για τις αξιολογήσεις οδηγών
     */
    private $driverRatingModel;

    /**
     * @var CompanyRatingModel Το μοντέλο για τις αξιολογήσεις εταιρειών
     */
    private $companyRatingModel;

    /**
     * Constructor
     *
     * @param PDO|null $pdo Η σύνδεση με τη βάση δεδομένων
     * @param RatingModel|null $driverRatingModel Το μοντέλο για τις αξιολογήσεις οδηγών
     * @param CompanyRatingModel|null $companyRatingModel Το μοντέλο για τις αξιολογήσεις εταιρειών
     */
    public function __construct(
        ?PDO $pdo = null,
        ?RatingModel $driverRatingModel = null,
        ?CompanyRatingModel $companyRatingModel = null
    ) {
        if ($pdo === null) {
            // Χρήση του Database class από το Core namespace
            $db = new \Drivejob\Core\Database();
            $pdo = $db->getConnection();
        }

        $this->driverRatingModel = $driverRatingModel ?? new RatingModel($pdo);
        $this->companyRatingModel = $companyRatingModel ?? new CompanyRatingModel($pdo);
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverRatingDetails(int $driverId): ?array
    {
        try {
            return $this->driverRatingModel->getDriverRatingDetails($driverId);
        } catch (\Exception $e) {
            Logger::error('Error in getDriverRatingDetails', [
                'driver_id' => $driverId,
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateDriverRating(int $driverId, array $ratings): bool
    {
        try {
            // Επικύρωση των δεδομένων
            $this->validateDriverRatings($ratings);

            // Ενημέρωση της βαθμολογίας
            return $this->driverRatingModel->updateDriverRating($driverId, $ratings);
        } catch (ValidationException $e) {
            Logger::error('Validation error in updateDriverRating', [
                'driver_id' => $driverId,
                'errors' => $e->getErrors()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Logger::error('Error in updateDriverRating', [
                'driver_id' => $driverId,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverRating(int $driverId): float
    {
        try {
            return $this->driverRatingModel->getDriverRating($driverId);
        } catch (\Exception $e) {
            Logger::error('Error in getDriverRating', [
                'driver_id' => $driverId,
                'message' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverReviews(int $driverId): array
    {
        try {
            return $this->driverRatingModel->getDriverReviews($driverId);
        } catch (\Exception $e) {
            Logger::error('Error in getDriverReviews', [
                'driver_id' => $driverId,
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addDriverReview(int $driverId, int $companyId, float $rating, string $comment = '', array $detailedRatings = []): bool
    {
        try {
            // Επικύρωση της βαθμολογίας
            $this->validateRating($rating);

            // Επικύρωση των λεπτομερών βαθμολογιών
            if (!empty($detailedRatings)) {
                foreach ($detailedRatings as $key => $value) {
                    $this->validateRating($value);
                }
            }

            // Έλεγχος αν η εταιρεία έχει ήδη αξιολογήσει τον οδηγό
            if ($this->hasCompanyReviewedDriver($companyId, $driverId)) {
                throw new ValidationException('Η εταιρεία έχει ήδη αξιολογήσει αυτόν τον οδηγό.', [
                    'rating' => 'Η εταιρεία έχει ήδη αξιολογήσει αυτόν τον οδηγό.'
                ]);
            }

            // Προσθήκη της αξιολόγησης
            $result = $this->driverRatingModel->addDriverReview($driverId, $companyId, $rating, $comment);

            // Αν η προσθήκη της αξιολόγησης ήταν επιτυχής και υπάρχουν λεπτομερείς βαθμολογίες
            if ($result && !empty($detailedRatings)) {
                // Λήψη του ID της αξιολόγησης που μόλις προστέθηκε
                $reviewId = $this->driverRatingModel->getLastInsertId();

                // Ενημέρωση των λεπτομερών βαθμολογιών
                $this->updateDriverReviewDetailedRatings($reviewId, $detailedRatings);
            }

            return $result;
        } catch (ValidationException $e) {
            Logger::error('Validation error in addDriverReview', [
                'driver_id' => $driverId,
                'company_id' => $companyId,
                'errors' => $e->getErrors()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Logger::error('Error in addDriverReview', [
                'driver_id' => $driverId,
                'company_id' => $companyId,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Ενημερώνει τις λεπτομερείς βαθμολογίες μιας αξιολόγησης οδηγού
     * 
     * @param int $reviewId ID της αξιολόγησης
     * @param array $detailedRatings Οι λεπτομερείς βαθμολογίες
     * @return bool Επιτυχία/αποτυχία
     */
    private function updateDriverReviewDetailedRatings(int $reviewId, array $detailedRatings): bool
    {
        try {
            $sql = "UPDATE driver_reviews SET 
                    professionalism_rating = :professionalism_rating,
                    driving_skills_rating = :driving_skills_rating,
                    reliability_rating = :reliability_rating,
                    communication_rating = :communication_rating,
                    technical_skills_rating = :technical_skills_rating
                    WHERE id = :review_id";

            $params = [
                'review_id' => $reviewId,
                'professionalism_rating' => $detailedRatings['professionalism_rating'] ?? null,
                'driving_skills_rating' => $detailedRatings['driving_skills_rating'] ?? null,
                'reliability_rating' => $detailedRatings['reliability_rating'] ?? null,
                'communication_rating' => $detailedRatings['communication_rating'] ?? null,
                'technical_skills_rating' => $detailedRatings['technical_skills_rating'] ?? null
            ];

            $stmt = $this->driverRatingModel->getPdo()->prepare($sql);
            return $stmt->execute($params);
        } catch (\Exception $e) {
            Logger::error('Error in updateDriverReviewDetailedRatings', [
                'review_id' => $reviewId,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function calculateDriverTotalRating(int $driverId): array
    {
        try {
            return $this->driverRatingModel->calculateTotalRating($driverId);
        } catch (\Exception $e) {
            Logger::error('Error in calculateDriverTotalRating', [
                'driver_id' => $driverId,
                'message' => $e->getMessage()
            ]);
            return [
                'skills_score' => 0,
                'safety_score' => 0,
                'professionalism_score' => 0,
                'technical_score' => 0,
                'total_score' => 0
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCompanyRatingDetails(int $companyId): ?array
    {
        try {
            return $this->companyRatingModel->getCompanyRatingDetails($companyId);
        } catch (\Exception $e) {
            Logger::error('Error in getCompanyRatingDetails', [
                'company_id' => $companyId,
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateCompanyRating(int $companyId, array $ratings): bool
    {
        try {
            // Επικύρωση των δεδομένων
            $this->validateCompanyRatings($ratings);

            // Ενημέρωση της βαθμολογίας
            return $this->companyRatingModel->updateCompanyRating($companyId, $ratings);
        } catch (ValidationException $e) {
            Logger::error('Validation error in updateCompanyRating', [
                'company_id' => $companyId,
                'errors' => $e->getErrors()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Logger::error('Error in updateCompanyRating', [
                'company_id' => $companyId,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCompanyRating(int $companyId): float
    {
        try {
            return $this->companyRatingModel->getCompanyRating($companyId);
        } catch (\Exception $e) {
            Logger::error('Error in getCompanyRating', [
                'company_id' => $companyId,
                'message' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCompanyReviews(int $companyId): array
    {
        try {
            return $this->companyRatingModel->getCompanyReviews($companyId);
        } catch (\Exception $e) {
            Logger::error('Error in getCompanyReviews', [
                'company_id' => $companyId,
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addCompanyReview(int $companyId, int $driverId, float $rating, string $comment = '', array $detailedRatings = []): bool
    {
        try {
            // Επικύρωση της βαθμολογίας
            $this->validateRating($rating);

            // Επικύρωση των λεπτομερών βαθμολογιών
            if (!empty($detailedRatings)) {
                foreach ($detailedRatings as $key => $value) {
                    $this->validateRating($value);
                }
            }

            // Έλεγχος αν ο οδηγός έχει ήδη αξιολογήσει την εταιρεία
            if ($this->hasDriverReviewedCompany($driverId, $companyId)) {
                throw new ValidationException('Ο οδηγός έχει ήδη αξιολογήσει αυτή την εταιρεία.', [
                    'rating' => 'Ο οδηγός έχει ήδη αξιολογήσει αυτή την εταιρεία.'
                ]);
            }

            // Προσθήκη της αξιολόγησης
            $result = $this->companyRatingModel->addCompanyReview($companyId, $driverId, $rating, $comment);

            // Αν η προσθήκη της αξιολόγησης ήταν επιτυχής και υπάρχουν λεπτομερείς βαθμολογίες
            if ($result && !empty($detailedRatings)) {
                // Λήψη του ID της αξιολόγησης που μόλις προστέθηκε
                $reviewId = $this->companyRatingModel->getLastInsertId();

                // Ενημέρωση των λεπτομερών βαθμολογιών
                $this->updateCompanyReviewDetailedRatings($reviewId, $detailedRatings);
            }

            return $result;
        } catch (ValidationException $e) {
            Logger::error('Validation error in addCompanyReview', [
                'company_id' => $companyId,
                'driver_id' => $driverId,
                'errors' => $e->getErrors()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Logger::error('Error in addCompanyReview', [
                'company_id' => $companyId,
                'driver_id' => $driverId,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Ενημερώνει τις λεπτομερείς βαθμολογίες μιας αξιολόγησης εταιρείας
     * 
     * @param int $reviewId ID της αξιολόγησης
     * @param array $detailedRatings Οι λεπτομερείς βαθμολογίες
     * @return bool Επιτυχία/αποτυχία
     */
    private function updateCompanyReviewDetailedRatings(int $reviewId, array $detailedRatings): bool
    {
        try {
            $sql = "UPDATE company_reviews SET 
                    reliability_rating = :reliability_rating,
                    communication_rating = :communication_rating,
                    payment_rating = :payment_rating,
                    working_conditions_rating = :working_conditions_rating
                    WHERE id = :review_id";

            $params = [
                'review_id' => $reviewId,
                'reliability_rating' => $detailedRatings['reliability_rating'] ?? null,
                'communication_rating' => $detailedRatings['communication_rating'] ?? null,
                'payment_rating' => $detailedRatings['payment_rating'] ?? null,
                'working_conditions_rating' => $detailedRatings['working_conditions_rating'] ?? null
            ];

            $stmt = $this->companyRatingModel->getPdo()->prepare($sql);
            return $stmt->execute($params);
        } catch (\Exception $e) {
            Logger::error('Error in updateCompanyReviewDetailedRatings', [
                'review_id' => $reviewId,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function calculateCompanyTotalRating(int $companyId): array
    {
        try {
            return $this->companyRatingModel->calculateTotalRating($companyId);
        } catch (\Exception $e) {
            Logger::error('Error in calculateCompanyTotalRating', [
                'company_id' => $companyId,
                'message' => $e->getMessage()
            ]);
            return [
                'reliability_score' => 0,
                'communication_score' => 0,
                'payment_score' => 0,
                'working_conditions_score' => 0,
                'total_score' => 0
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasDriverReviewedCompany(int $driverId, int $companyId): bool
    {
        try {
            $reviews = $this->companyRatingModel->getCompanyReviews($companyId);
            foreach ($reviews as $review) {
                if ($review['driver_id'] == $driverId) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            Logger::error('Error in hasDriverReviewedCompany', [
                'driver_id' => $driverId,
                'company_id' => $companyId,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasCompanyReviewedDriver(int $companyId, int $driverId): bool
    {
        try {
            $reviews = $this->driverRatingModel->getDriverReviews($driverId);
            foreach ($reviews as $review) {
                if ($review['company_id'] == $companyId) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            Logger::error('Error in hasCompanyReviewedDriver', [
                'company_id' => $companyId,
                'driver_id' => $driverId,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Επικύρωση της βαθμολογίας
     * 
     * @param float $rating Η βαθμολογία
     * @throws ValidationException Αν η βαθμολογία δεν είναι έγκυρη
     */
    private function validateRating(float $rating): void
    {
        if ($rating < 1 || $rating > 5) {
            throw new ValidationException('Η βαθμολογία πρέπει να είναι μεταξύ 1 και 5.', [
                'rating' => 'Η βαθμολογία πρέπει να είναι μεταξύ 1 και 5.'
            ]);
        }
    }

    /**
     * Επικύρωση των βαθμολογιών οδηγού
     * 
     * @param array $ratings Οι βαθμολογίες
     * @throws ValidationException Αν οι βαθμολογίες δεν είναι έγκυρες
     */
    private function validateDriverRatings(array $ratings): void
    {
        $errors = [];

        // Έλεγχος για τις επιμέρους βαθμολογίες
        $scoreFields = [
            'skills_score',
            'safety_score',
            'professionalism_score',
            'technical_score',
            'total_score'
        ];

        foreach ($scoreFields as $field) {
            if (isset($ratings[$field])) {
                if (!is_numeric($ratings[$field]) || $ratings[$field] < 0 || $ratings[$field] > 100) {
                    $errors[$field] = "Η βαθμολογία $field πρέπει να είναι μεταξύ 0 και 100.";
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException('Οι βαθμολογίες δεν είναι έγκυρες.', $errors);
        }
    }

    /**
     * Επικύρωση των βαθμολογιών εταιρείας
     * 
     * @param array $ratings Οι βαθμολογίες
     * @throws ValidationException Αν οι βαθμολογίες δεν είναι έγκυρες
     */
    private function validateCompanyRatings(array $ratings): void
    {
        $errors = [];

        // Έλεγχος για τις επιμέρους βαθμολογίες
        $scoreFields = [
            'reliability_score',
            'communication_score',
            'payment_score',
            'working_conditions_score',
            'total_score'
        ];

        foreach ($scoreFields as $field) {
            if (isset($ratings[$field])) {
                if (!is_numeric($ratings[$field]) || $ratings[$field] < 0 || $ratings[$field] > 100) {
                    $errors[$field] = "Η βαθμολογία $field πρέπει να είναι μεταξύ 0 και 100.";
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException('Οι βαθμολογίες δεν είναι έγκυρες.', $errors);
        }
    }
}
