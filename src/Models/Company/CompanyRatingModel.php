<?php

namespace Drivejob\Models\Company;

use PDO;
use PDOException;
use Drivejob\Core\Logger;
use Drivejob\Models\BaseModel;

/**
 * Μοντέλο για τη διαχείριση των αξιολογήσεων των εταιρειών
 */
class CompanyRatingModel extends BaseModel
{
    /**
     * Constructor
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo, 'company_ratings');
    }

    /**
     * Επιστρέφει τα δεδομένα αξιολόγησης μιας εταιρείας
     * 
     * @param int $companyId Το ID της εταιρείας
     * @return array|null Οι βαθμολογίες της εταιρείας ή null αν δεν υπάρχουν
     */
    public function getCompanyRatingDetails($companyId)
    {
        try {
            return $this->selectOne(['company_id' => $companyId]);
        } catch (PDOException $e) {
            Logger::error('Error getting company rating details: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Υπολογίζει και ενημερώνει τη συνολική βαθμολογία της εταιρείας
     * 
     * @param int $companyId ID της εταιρείας
     * @param array $ratings Οι βαθμολογίες της εταιρείας
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateCompanyRating($companyId, $ratings)
    {
        try {
            // Έλεγχος αν υπάρχει ήδη εγγραφή
            $existingRating = $this->getCompanyRatingDetails($companyId);

            // Προσθήκη της ημερομηνίας τελευταίας ενημέρωσης
            $ratings['last_updated'] = date('Y-m-d H:i:s');

            if ($existingRating) {
                // Ενημέρωση υπάρχουσας βαθμολογίας
                return $this->update($ratings, ['company_id' => $companyId]);
            } else {
                // Δημιουργία νέας βαθμολογίας
                $ratings['company_id'] = $companyId;
                return $this->insert($ratings) !== false;
            }
        } catch (PDOException $e) {
            Logger::error('Error in updateCompanyRating: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ανάκτηση της μέσης βαθμολογίας μιας εταιρείας
     * 
     * @param int $companyId Το ID της εταιρείας
     * @return float Η μέση βαθμολογία της εταιρείας
     */
    public function getCompanyRating($companyId)
    {
        try {
            // Πρώτα προσπαθούμε να πάρουμε την τιμή από τον πίνακα company_ratings
            $rating = $this->selectOne(['company_id' => $companyId], 'total_score');

            if ($rating && isset($rating['total_score'])) {
                // Μετατροπή του score 0-100 σε βαθμολογία 0-5
                return min(5, round($rating['total_score'] / 20, 1));
            }

            // Αν δεν υπάρχει, ελέγχουμε τον πίνακα company_reviews
            $reviewsTable = 'company_reviews';
            $sql = "SELECT AVG(rating) as avg_rating FROM $reviewsTable WHERE company_id = :company_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['company_id' => $companyId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && isset($result['avg_rating'])) {
                return round($result['avg_rating'], 1);
            }

            // Αν δεν υπάρχει ούτε στις αξιολογήσεις, ελέγχουμε το πεδίο rating του πίνακα companies
            $companiesTable = 'companies';
            $sql = "SELECT rating FROM $companiesTable WHERE id = :company_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['company_id' => $companyId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && isset($result['rating'])) {
                return $result['rating'];
            }

            // Αλλιώς επιστρέφουμε 0
            return 0;
        } catch (PDOException $e) {
            Logger::error('Error getting company rating: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Ανάκτηση των αξιολογήσεων μιας εταιρείας
     * 
     * @param int $companyId Το ID της εταιρείας
     * @return array Οι αξιολογήσεις της εταιρείας
     */
    public function getCompanyReviews($companyId)
    {
        try {
            $reviewsTable = 'company_reviews';
            $driversTable = 'drivers';

            $sql = "SELECT r.*, CONCAT(d.first_name, ' ', d.last_name) as driver_name
                FROM $reviewsTable r
                LEFT JOIN $driversTable d ON r.driver_id = d.id
                WHERE r.company_id = :company_id
                ORDER BY r.created_at DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['company_id' => $companyId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('Error getting company reviews: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Προσθήκη νέας αξιολόγησης για μια εταιρεία
     * 
     * @param int $companyId ID της εταιρείας
     * @param int $driverId ID του οδηγού
     * @param float $rating Βαθμολογία (0-5)
     * @param string $comment Σχόλιο
     * @return bool Επιτυχία/αποτυχία
     */
    public function addCompanyReview($companyId, $driverId, $rating, $comment = '')
    {
        try {
            $reviewsTable = 'company_reviews';
            $sql = "INSERT INTO $reviewsTable (company_id, driver_id, rating, comment, created_at) 
                    VALUES (:company_id, :driver_id, :rating, :comment, NOW())";

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                'company_id' => $companyId,
                'driver_id' => $driverId,
                'rating' => $rating,
                'comment' => $comment
            ]);

            if ($result) {
                // Ενημέρωση της μέσης βαθμολογίας στον πίνακα companies
                $this->updateCompanyAverageRating($companyId);
            }

            return $result;
        } catch (PDOException $e) {
            Logger::error('Error adding company review: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημέρωση της μέσης βαθμολογίας μιας εταιρείας στον πίνακα companies
     * 
     * @param int $companyId ID της εταιρείας
     * @return bool Επιτυχία/αποτυχία
     */
    private function updateCompanyAverageRating($companyId)
    {
        try {
            $reviewsTable = 'company_reviews';
            $companiesTable = 'companies';

            // Υπολογισμός της μέσης βαθμολογίας
            $sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as count 
                    FROM $reviewsTable WHERE company_id = :company_id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['company_id' => $companyId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && isset($result['avg_rating'])) {
                // Ενημέρωση του πίνακα companies
                $sql = "UPDATE $companiesTable SET 
                        rating = :rating, 
                        rating_count = :count 
                        WHERE id = :company_id";

                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([
                    'rating' => round($result['avg_rating'], 1),
                    'count' => $result['count'],
                    'company_id' => $companyId
                ]);
            }

            return false;
        } catch (PDOException $e) {
            Logger::error('Error updating company average rating: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Υπολογίζει τη συνολική βαθμολογία μιας εταιρείας συνδυάζοντας διάφορα κριτήρια
     * 
     * @param int $companyId ID της εταιρείας
     * @return array Συνολική βαθμολογία και επιμέρους βαθμολογίες
     */
    public function calculateTotalRating($companyId)
    {
        // Προεπιλεγμένες τιμές
        $ratings = [
            'reliability_score' => 0,
            'communication_score' => 0,
            'payment_score' => 0,
            'working_conditions_score' => 0,
            'total_score' => 0
        ];

        try {
            // Ανάκτηση των αξιολογήσεων της εταιρείας
            $reviews = $this->getCompanyReviews($companyId);

            if (empty($reviews)) {
                return $ratings;
            }

            // Υπολογισμός των επιμέρους βαθμολογιών
            $reliabilitySum = 0;
            $communicationSum = 0;
            $paymentSum = 0;
            $workingConditionsSum = 0;
            $count = count($reviews);

            foreach ($reviews as $review) {
                // Αν υπάρχουν επιμέρους βαθμολογίες στις αξιολογήσεις
                $reliabilitySum += $review['reliability_rating'] ?? $review['rating'] ?? 0;
                $communicationSum += $review['communication_rating'] ?? $review['rating'] ?? 0;
                $paymentSum += $review['payment_rating'] ?? $review['rating'] ?? 0;
                $workingConditionsSum += $review['working_conditions_rating'] ?? $review['rating'] ?? 0;
            }

            // Υπολογισμός των μέσων βαθμολογιών
            $ratings['reliability_score'] = $count > 0 ? ($reliabilitySum / $count) * 20 : 0;
            $ratings['communication_score'] = $count > 0 ? ($communicationSum / $count) * 20 : 0;
            $ratings['payment_score'] = $count > 0 ? ($paymentSum / $count) * 20 : 0;
            $ratings['working_conditions_score'] = $count > 0 ? ($workingConditionsSum / $count) * 20 : 0;

            // Υπολογισμός της συνολικής βαθμολογίας
            $ratings['total_score'] =
                ($ratings['reliability_score'] * 0.25) +
                ($ratings['communication_score'] * 0.25) +
                ($ratings['payment_score'] * 0.25) +
                ($ratings['working_conditions_score'] * 0.25);

            return $ratings;
        } catch (\Exception $e) {
            Logger::error('Error in calculateTotalRating: ' . $e->getMessage());
            return $ratings;
        }
    }
}
