<?php

namespace Drivejob\Services\AI;

use Drivejob\Core\Logger;

/**
 * Score Calculator για AI Matching
 * Υπολογίζει συνολικά scores από individual matching scores
 */
class ScoreCalculator
{
    /**
     * Βάρη για τα διάφορα κριτήρια ταιριάσματος
     */
    private array $weights = [
        'skill_match' => 0.35,
        'location_match' => 0.25,
        'experience_match' => 0.25,
        'availability_match' => 0.15
    ];

    /**
     * Υπολογίζει το συνολικό σκορ από individual scores
     */
    public function calculateOverallScore(array $scores): float
    {
        try {
            $totalScore = 0.0;
            $totalWeight = 0.0;

            foreach ($this->weights as $criterion => $weight) {
                if (isset($scores[$criterion])) {
                    $totalScore += $scores[$criterion] * $weight;
                    $totalWeight += $weight;
                }
            }

            // Κανονικοποίηση σε κλίμακα 0-100
            $normalizedScore = $totalWeight > 0 ? ($totalScore / $totalWeight) * 100 : 0.0;

            // Εφαρμογή bonus/penalty factors
            $finalScore = $this->applyBonusFactors($normalizedScore, $scores);

            return min(100.0, max(0.0, $finalScore));
        } catch (\Exception $e) {
            Logger::error('Error calculating overall score: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Εφαρμόζει bonus/penalty factors στο σκορ
     */
    private function applyBonusFactors(float $baseScore, array $scores): float
    {
        $adjustedScore = $baseScore;

        // Bonus για υψηλό skill match
        if (($scores['skill_match'] ?? 0) >= 0.9) {
            $adjustedScore *= 1.1; // 10% bonus
        }

        // Bonus για τέλεια location match
        if (($scores['location_match'] ?? 0) >= 0.95) {
            $adjustedScore *= 1.05; // 5% bonus
        }

        // Penalty για πολύ χαμηλό experience match
        if (($scores['experience_match'] ?? 0) < 0.3) {
            $adjustedScore *= 0.9; // 10% penalty
        }

        // Bonus για άμεση διαθεσιμότητα
        if (($scores['availability_match'] ?? 0) >= 0.95) {
            $adjustedScore *= 1.03; // 3% bonus
        }

        return $adjustedScore;
    }

    /**
     * Υπολογίζει confidence level για το matching
     */
    public function calculateConfidence(array $scores): float
    {
        try {
            $confidenceFactors = [];

            // Consistency check - όλα τα scores είναι παρόμοια
            $scoreValues = array_values($scores);
            $avgScore = array_sum($scoreValues) / count($scoreValues);
            $variance = 0;

            foreach ($scoreValues as $score) {
                $variance += pow($score - $avgScore, 2);
            }
            $variance /= count($scoreValues);
            $standardDeviation = sqrt($variance);

            // Χαμηλή διακύμανση = υψηλή εμπιστοσύνη
            $consistencyFactor = max(0, 1 - ($standardDeviation * 2));
            $confidenceFactors[] = $consistencyFactor;

            // Completeness check - πόσα scores έχουμε
            $completeness = count($scores) / 4; // Αναμένουμε 4 scores
            $confidenceFactors[] = $completeness;

            // Quality check - μέσος όρος των scores
            $qualityFactor = $avgScore;
            $confidenceFactors[] = $qualityFactor;

            // Συνολικό confidence
            $overallConfidence = array_sum($confidenceFactors) / count($confidenceFactors);

            return min(1.0, max(0.0, $overallConfidence));
        } catch (\Exception $e) {
            Logger::error('Error calculating confidence: ' . $e->getMessage());
            return 0.5; // Default confidence
        }
    }

    /**
     * Παρέχει detailed breakdown του scoring
     */
    public function getScoreBreakdown(array $scores): array
    {
        $breakdown = [
            'individual_scores' => $scores,
            'weights' => $this->weights,
            'weighted_scores' => [],
            'overall_score' => $this->calculateOverallScore($scores),
            'confidence' => $this->calculateConfidence($scores),
            'recommendations' => []
        ];

        // Υπολογισμός weighted scores
        foreach ($this->weights as $criterion => $weight) {
            if (isset($scores[$criterion])) {
                $breakdown['weighted_scores'][$criterion] = $scores[$criterion] * $weight;
            }
        }

        // Προτάσεις βελτίωσης
        $breakdown['recommendations'] = $this->generateRecommendations($scores);

        return $breakdown;
    }

    /**
     * Δημιουργεί προτάσεις βελτίωσης με βάση τα scores
     */
    private function generateRecommendations(array $scores): array
    {
        $recommendations = [];

        // Skill match recommendations
        if (($scores['skill_match'] ?? 0) < 0.5) {
            $recommendations[] = [
                'category' => 'skills',
                'priority' => 'high',
                'message' => 'Χαμηλό skill match - εξετάστε πρόσθετες πιστοποιήσεις ή εκπαίδευση'
            ];
        }

        // Location match recommendations
        if (($scores['location_match'] ?? 0) < 0.4) {
            $recommendations[] = [
                'category' => 'location',
                'priority' => 'medium',
                'message' => 'Μεγάλη απόσταση - εξετάστε remote work ή relocation options'
            ];
        }

        // Experience match recommendations
        if (($scores['experience_match'] ?? 0) < 0.6) {
            $recommendations[] = [
                'category' => 'experience',
                'priority' => 'medium',
                'message' => 'Ανεπαρκής εμπειρία - εξετάστε training programs ή mentoring'
            ];
        }

        // Availability match recommendations
        if (($scores['availability_match'] ?? 0) < 0.7) {
            $recommendations[] = [
                'category' => 'availability',
                'priority' => 'low',
                'message' => 'Ασυμβατότητα ωραρίου - εξετάστε flexible scheduling options'
            ];
        }

        return $recommendations;
    }

    /**
     * Ενημερώνει τα βάρη των κριτηρίων
     */
    public function updateWeights(array $newWeights): void
    {
        foreach ($newWeights as $criterion => $weight) {
            if (isset($this->weights[$criterion]) && $weight >= 0 && $weight <= 1) {
                $this->weights[$criterion] = $weight;
            }
        }

        // Κανονικοποίηση των βαρών ώστε το άθροισμα να είναι 1
        $totalWeight = array_sum($this->weights);
        if ($totalWeight > 0) {
            foreach ($this->weights as $criterion => $weight) {
                $this->weights[$criterion] = $weight / $totalWeight;
            }
        }
    }

    /**
     * Επιστρέφει τα τρέχοντα βάρη
     */
    public function getWeights(): array
    {
        return $this->weights;
    }

    /**
     * Υπολογίζει similarity score μεταξύ δύο feature vectors
     */
    public function calculateSimilarity(array $features1, array $features2): float
    {
        try {
            $similarities = [];

            // Cosine similarity για numeric features
            $numericFeatures1 = $this->extractNumericFeatures($features1);
            $numericFeatures2 = $this->extractNumericFeatures($features2);

            if (!empty($numericFeatures1) && !empty($numericFeatures2)) {
                $similarities[] = $this->cosineSimilarity($numericFeatures1, $numericFeatures2);
            }

            // Jaccard similarity για categorical features
            $categoricalFeatures1 = $this->extractCategoricalFeatures($features1);
            $categoricalFeatures2 = $this->extractCategoricalFeatures($features2);

            if (!empty($categoricalFeatures1) && !empty($categoricalFeatures2)) {
                $similarities[] = $this->jaccardSimilarity($categoricalFeatures1, $categoricalFeatures2);
            }

            return !empty($similarities) ? array_sum($similarities) / count($similarities) : 0.0;
        } catch (\Exception $e) {
            Logger::error('Error calculating similarity: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Εξάγει numeric features από feature vector
     */
    private function extractNumericFeatures(array $features): array
    {
        $numeric = [];

        foreach ($features as $key => $value) {
            if (is_numeric($value)) {
                $numeric[$key] = (float)$value;
            }
        }

        return $numeric;
    }

    /**
     * Εξάγει categorical features από feature vector
     */
    private function extractCategoricalFeatures(array $features): array
    {
        $categorical = [];

        foreach ($features as $key => $value) {
            if (is_array($value)) {
                $categorical = array_merge($categorical, $value);
            } elseif (is_string($value) && !is_numeric($value)) {
                $categorical[] = $value;
            }
        }

        return array_unique($categorical);
    }

    /**
     * Υπολογίζει cosine similarity
     */
    private function cosineSimilarity(array $vector1, array $vector2): float
    {
        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        $allKeys = array_unique(array_merge(array_keys($vector1), array_keys($vector2)));

        foreach ($allKeys as $key) {
            $val1 = $vector1[$key] ?? 0;
            $val2 = $vector2[$key] ?? 0;

            $dotProduct += $val1 * $val2;
            $magnitude1 += $val1 * $val1;
            $magnitude2 += $val2 * $val2;
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0.0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }

    /**
     * Υπολογίζει Jaccard similarity
     */
    private function jaccardSimilarity(array $set1, array $set2): float
    {
        $intersection = array_intersect($set1, $set2);
        $union = array_unique(array_merge($set1, $set2));

        if (empty($union)) {
            return 0.0;
        }

        return count($intersection) / count($union);
    }
}
