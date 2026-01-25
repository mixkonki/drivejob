<?php

namespace Drivejob\Services\Rating;

/**
 * Διεπαφή για την υπηρεσία αξιολογήσεων
 */
interface RatingServiceInterface
{
    /**
     * Επιστρέφει τα δεδομένα αξιολόγησης ενός οδηγού
     * 
     * @param int $driverId Το ID του οδηγού
     * @return array|null Οι βαθμολογίες του οδηγού ή null αν δεν υπάρχουν
     */
    public function getDriverRatingDetails(int $driverId): ?array;

    /**
     * Υπολογίζει και ενημερώνει τη συνολική βαθμολογία του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $ratings Οι βαθμολογίες του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverRating(int $driverId, array $ratings): bool;

    /**
     * Ανάκτηση της μέσης βαθμολογίας ενός οδηγού
     * 
     * @param int $driverId Το ID του οδηγού
     * @return float Η μέση βαθμολογία του οδηγού
     */
    public function getDriverRating(int $driverId): float;

    /**
     * Ανάκτηση των αξιολογήσεων ενός οδηγού
     * 
     * @param int $driverId Το ID του οδηγού
     * @return array Οι αξιολογήσεις του οδηγού
     */
    public function getDriverReviews(int $driverId): array;

    /**
     * Προσθήκη νέας αξιολόγησης για έναν οδηγό
     * 
     * @param int $driverId ID του οδηγού
     * @param int $companyId ID της εταιρείας
     * @param float $rating Βαθμολογία (0-5)
     * @param string $comment Σχόλιο
     * @param array $detailedRatings Λεπτομερείς βαθμολογίες (προαιρετικό)
     * @return bool Επιτυχία/αποτυχία
     */
    public function addDriverReview(int $driverId, int $companyId, float $rating, string $comment = '', array $detailedRatings = []): bool;

    /**
     * Υπολογίζει τη συνολική βαθμολογία ενός οδηγού συνδυάζοντας διάφορα κριτήρια
     * 
     * @param int $driverId ID του οδηγού
     * @return array Συνολική βαθμολογία και επιμέρους βαθμολογίες
     */
    public function calculateDriverTotalRating(int $driverId): array;

    /**
     * Επιστρέφει τα δεδομένα αξιολόγησης μιας εταιρείας
     * 
     * @param int $companyId Το ID της εταιρείας
     * @return array|null Οι βαθμολογίες της εταιρείας ή null αν δεν υπάρχουν
     */
    public function getCompanyRatingDetails(int $companyId): ?array;

    /**
     * Υπολογίζει και ενημερώνει τη συνολική βαθμολογία της εταιρείας
     * 
     * @param int $companyId ID της εταιρείας
     * @param array $ratings Οι βαθμολογίες της εταιρείας
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateCompanyRating(int $companyId, array $ratings): bool;

    /**
     * Ανάκτηση της μέσης βαθμολογίας μιας εταιρείας
     * 
     * @param int $companyId Το ID της εταιρείας
     * @return float Η μέση βαθμολογία της εταιρείας
     */
    public function getCompanyRating(int $companyId): float;

    /**
     * Ανάκτηση των αξιολογήσεων μιας εταιρείας
     * 
     * @param int $companyId Το ID της εταιρείας
     * @return array Οι αξιολογήσεις της εταιρείας
     */
    public function getCompanyReviews(int $companyId): array;

    /**
     * Προσθήκη νέας αξιολόγησης για μια εταιρεία
     * 
     * @param int $companyId ID της εταιρείας
     * @param int $driverId ID του οδηγού
     * @param float $rating Βαθμολογία (0-5)
     * @param string $comment Σχόλιο
     * @param array $detailedRatings Λεπτομερείς βαθμολογίες (προαιρετικό)
     * @return bool Επιτυχία/αποτυχία
     */
    public function addCompanyReview(int $companyId, int $driverId, float $rating, string $comment = '', array $detailedRatings = []): bool;

    /**
     * Υπολογίζει τη συνολική βαθμολογία μιας εταιρείας συνδυάζοντας διάφορα κριτήρια
     * 
     * @param int $companyId ID της εταιρείας
     * @return array Συνολική βαθμολογία και επιμέρους βαθμολογίες
     */
    public function calculateCompanyTotalRating(int $companyId): array;

    /**
     * Ελέγχει αν ένας οδηγός έχει ήδη αξιολογήσει μια εταιρεία
     * 
     * @param int $driverId ID του οδηγού
     * @param int $companyId ID της εταιρείας
     * @return bool Αν ο οδηγός έχει ήδη αξιολογήσει την εταιρεία
     */
    public function hasDriverReviewedCompany(int $driverId, int $companyId): bool;

    /**
     * Ελέγχει αν μια εταιρεία έχει ήδη αξιολογήσει έναν οδηγό
     * 
     * @param int $companyId ID της εταιρείας
     * @param int $driverId ID του οδηγού
     * @return bool Αν η εταιρεία έχει ήδη αξιολογήσει τον οδηγό
     */
    public function hasCompanyReviewedDriver(int $companyId, int $driverId): bool;
}
