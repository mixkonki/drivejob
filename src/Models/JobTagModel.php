<?php

namespace Drivejob\Models;

use Drivejob\Core\Logger;

/**
 * Μοντέλο για τη διαχείριση των tags των αγγελιών εργασίας
 */
class JobTagModel
{
    private $pdo;

    /**
     * Κατασκευαστής του μοντέλου
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Επιστρέφει όλα τα διαθέσιμα tags
     *
     * @return array Τα διαθέσιμα tags
     */
    public function getAllTags()
    {
        try {
            $query = "SELECT * FROM job_tags ORDER BY name ASC";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            Logger::error('Error in getAllTags: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Επιστρέφει ένα tag με βάση το ID
     *
     * @param int $tagId ID του tag
     * @return array|false Το tag ή false αν δεν βρέθηκε
     */
    public function getTagById($tagId)
    {
        try {
            $query = "SELECT * FROM job_tags WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$tagId]);

            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            Logger::error('Error in getTagById: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Επιστρέφει ένα tag με βάση το όνομα
     *
     * @param string $tagName Όνομα του tag
     * @return array|false Το tag ή false αν δεν βρέθηκε
     */
    public function getTagByName($tagName)
    {
        try {
            $query = "SELECT * FROM job_tags WHERE name = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$tagName]);

            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            Logger::error('Error in getTagByName: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Δημιουργεί ένα νέο tag
     *
     * @param string $name Όνομα του tag
     * @param string $category Κατηγορία του tag (προαιρετικό)
     * @param string $description Περιγραφή του tag (προαιρετικό)
     * @return int|false ID του νέου tag ή false σε περίπτωση αποτυχίας
     */
    public function createTag($name, $category = null, $description = null)
    {
        try {
            // Έλεγχος αν υπάρχει ήδη tag με το ίδιο όνομα
            $existingTag = $this->getTagByName($name);
            if ($existingTag) {
                return $existingTag['id'];
            }

            $query = "INSERT INTO job_tags (name, category, description) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($query);

            if ($stmt->execute([$name, $category, $description])) {
                return $this->pdo->lastInsertId();
            }

            return false;
        } catch (\PDOException $e) {
            Logger::error('Error in createTag: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημερώνει ένα υπάρχον tag
     *
     * @param int $tagId ID του tag
     * @param array $data Δεδομένα για ενημέρωση
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateTag($tagId, $data)
    {
        try {
            $fields = [];
            $values = [];

            foreach ($data as $field => $value) {
                $fields[] = "$field = ?";
                $values[] = $value;
            }

            $values[] = $tagId;

            $query = "UPDATE job_tags SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($query);

            return $stmt->execute($values);
        } catch (\PDOException $e) {
            Logger::error('Error in updateTag: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Διαγράφει ένα tag
     *
     * @param int $tagId ID του tag
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteTag($tagId)
    {
        try {
            // Διαγραφή των συσχετίσεων με αγγελίες
            $this->removeTagFromAllListings($tagId);

            // Διαγραφή του tag
            $query = "DELETE FROM job_tags WHERE id = ?";
            $stmt = $this->pdo->prepare($query);

            return $stmt->execute([$tagId]);
        } catch (\PDOException $e) {
            Logger::error('Error in deleteTag: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Επιστρέφει τα tags μιας αγγελίας
     *
     * @param int $listingId ID της αγγελίας
     * @return array Τα tags της αγγελίας
     */
    public function getTagsByListingId($listingId)
    {
        try {
            $query = "SELECT t.* FROM job_listing_tags jlt
                      JOIN job_tags t ON jlt.tag_id = t.id
                      WHERE jlt.job_listing_id = ?
                      ORDER BY t.name ASC";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$listingId]);

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            Logger::error('Error in getTagsByListingId: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Προσθέτει ένα tag σε μια αγγελία
     *
     * @param int $listingId ID της αγγελίας
     * @param int $tagId ID του tag
     * @return bool Επιτυχία/αποτυχία
     */
    public function addTagToListing($listingId, $tagId)
    {
        try {
            // Έλεγχος αν υπάρχει ήδη η συσχέτιση
            $query = "SELECT COUNT(*) FROM job_listing_tags WHERE job_listing_id = ? AND tag_id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$listingId, $tagId]);

            if ($stmt->fetchColumn() > 0) {
                return true; // Η συσχέτιση υπάρχει ήδη
            }

            // Προσθήκη της συσχέτισης
            $query = "INSERT INTO job_listing_tags (job_listing_id, tag_id) VALUES (?, ?)";
            $stmt = $this->pdo->prepare($query);

            return $stmt->execute([$listingId, $tagId]);
        } catch (\PDOException $e) {
            Logger::error('Error in addTagToListing: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Αφαιρεί ένα tag από μια αγγελία
     *
     * @param int $listingId ID της αγγελίας
     * @param int $tagId ID του tag
     * @return bool Επιτυχία/αποτυχία
     */
    public function removeTagFromListing($listingId, $tagId)
    {
        try {
            $query = "DELETE FROM job_listing_tags WHERE job_listing_id = ? AND tag_id = ?";
            $stmt = $this->pdo->prepare($query);

            return $stmt->execute([$listingId, $tagId]);
        } catch (\PDOException $e) {
            Logger::error('Error in removeTagFromListing: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Αφαιρεί ένα tag από όλες τις αγγελίες
     *
     * @param int $tagId ID του tag
     * @return bool Επιτυχία/αποτυχία
     */
    public function removeTagFromAllListings($tagId)
    {
        try {
            $query = "DELETE FROM job_listing_tags WHERE tag_id = ?";
            $stmt = $this->pdo->prepare($query);

            return $stmt->execute([$tagId]);
        } catch (\PDOException $e) {
            Logger::error('Error in removeTagFromAllListings: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημερώνει τα tags μιας αγγελίας
     *
     * @param int $listingId ID της αγγελίας
     * @param array $tagIds IDs των tags
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateListingTags($listingId, $tagIds)
    {
        try {
            // Διαγραφή όλων των υπαρχόντων συσχετίσεων
            $query = "DELETE FROM job_listing_tags WHERE job_listing_id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$listingId]);

            // Προσθήκη των νέων συσχετίσεων
            if (!empty($tagIds)) {
                $query = "INSERT INTO job_listing_tags (job_listing_id, tag_id) VALUES (?, ?)";
                $stmt = $this->pdo->prepare($query);

                foreach ($tagIds as $tagId) {
                    $stmt->execute([$listingId, $tagId]);
                }
            }

            return true;
        } catch (\PDOException $e) {
            Logger::error('Error in updateListingTags: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Επιστρέφει τις αγγελίες που έχουν ένα συγκεκριμένο tag
     *
     * @param int $tagId ID του tag
     * @param int $page Αριθμός σελίδας
     * @param int $limit Αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Αποτελέσματα αναζήτησης και πληροφορίες σελιδοποίησης
     */
    public function getListingsByTagId($tagId, $page = 1, $limit = 10)
    {
        try {
            // Βασικό ερώτημα
            $query = "SELECT jl.* FROM job_listings jl
                      JOIN job_listing_tags jlt ON jl.id = jlt.job_listing_id
                      WHERE jlt.tag_id = ? AND jl.is_active = 1
                      ORDER BY jl.created_at DESC";

            // Μέτρηση συνολικών αποτελεσμάτων
            $countQuery = "SELECT COUNT(*) FROM job_listings jl
                           JOIN job_listing_tags jlt ON jl.id = jlt.job_listing_id
                           WHERE jlt.tag_id = ? AND jl.is_active = 1";

            $countStmt = $this->pdo->prepare($countQuery);
            $countStmt->execute([$tagId]);
            $totalResults = $countStmt->fetchColumn();

            // Προσθήκη σελιδοποίησης
            $offset = ($page - 1) * $limit;
            $query .= " LIMIT ?, ?";

            // Εκτέλεση του ερωτήματος
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$tagId, $offset, $limit]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Προσθήκη των tags σε κάθε αγγελία
            foreach ($results as &$listing) {
                $listing['tags'] = $this->getTagsByListingId($listing['id']);
            }

            return [
                'results' => $results,
                'pagination' => [
                    'total' => $totalResults,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($totalResults / $limit)
                ]
            ];
        } catch (\PDOException $e) {
            Logger::error('Error in getListingsByTagId: ' . $e->getMessage());

            return [
                'results' => [],
                'pagination' => [
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => 0
                ]
            ];
        }
    }

    /**
     * Αναζητά tags με βάση το όνομα
     *
     * @param string $searchTerm Όρος αναζήτησης
     * @param int $limit Μέγιστος αριθμός αποτελεσμάτων
     * @return array Τα tags που ταιριάζουν με τον όρο αναζήτησης
     */
    public function searchTags($searchTerm, $limit = 10)
    {
        try {
            $query = "SELECT * FROM job_tags WHERE name LIKE ? ORDER BY name ASC LIMIT ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute(['%' . $searchTerm . '%', $limit]);

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            Logger::error('Error in searchTags: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Επιστρέφει τα πιο δημοφιλή tags
     *
     * @param int $limit Μέγιστος αριθμός αποτελεσμάτων
     * @return array Τα πιο δημοφιλή tags
     */
    public function getPopularTags($limit = 10)
    {
        try {
            $query = "SELECT t.*, COUNT(jlt.job_listing_id) as usage_count
                      FROM job_tags t
                      JOIN job_listing_tags jlt ON t.id = jlt.tag_id
                      GROUP BY t.id
                      ORDER BY usage_count DESC
                      LIMIT ?";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$limit]);

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            Logger::error('Error in getPopularTags: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Επιστρέφει τα tags ομαδοποιημένα ανά κατηγορία
     *
     * @return array Τα tags ομαδοποιημένα ανά κατηγορία
     */
    public function getTagsByCategory()
    {
        try {
            $query = "SELECT * FROM job_tags ORDER BY category, name";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();

            $tags = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $groupedTags = [];

            foreach ($tags as $tag) {
                $category = $tag['category'] ?: 'Άλλα';
                if (!isset($groupedTags[$category])) {
                    $groupedTags[$category] = [];
                }

                $groupedTags[$category][] = $tag;
            }

            return $groupedTags;
        } catch (\PDOException $e) {
            Logger::error('Error in getTagsByCategory: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Επιστρέφει προτεινόμενα tags για μια αγγελία με βάση την περιγραφή
     *
     * @param string $description Περιγραφή της αγγελίας
     * @param int $limit Μέγιστος αριθμός αποτελεσμάτων
     * @return array Προτεινόμενα tags
     */
    public function getSuggestedTags($description, $limit = 5)
    {
        try {
            // Λήψη όλων των tags
            $allTags = $this->getAllTags();
            $suggestedTags = [];

            // Έλεγχος για κάθε tag αν υπάρχει στην περιγραφή
            foreach ($allTags as $tag) {
                if (stripos($description, $tag['name']) !== false) {
                    $suggestedTags[] = $tag;

                    if (count($suggestedTags) >= $limit) {
                        break;
                    }
                }
            }

            return $suggestedTags;
        } catch (\Exception $e) {
            Logger::error('Error in getSuggestedTags: ' . $e->getMessage());
            return [];
        }
    }
}
