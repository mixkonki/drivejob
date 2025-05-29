<?php

namespace Drivejob\Services\AI;

class ScoreCalculator
{
    /**
     * Weights for different matching factors
     */
    private const WEIGHTS = [
        'skill_match' => 0.35,
        'location_match' => 0.25,
        'experience_match' => 0.25,
        'availability_match' => 0.15
    ];

    /**
     * Calculate overall score from individual scores
     */
    public function calculateOverallScore(array $scores): float
    {
        $weightedSum = 0;
        $totalWeight = 0;

        foreach (self::WEIGHTS as $factor => $weight) {
            if (isset($scores[$factor])) {
                $weightedSum += $scores[$factor] * $weight;
                $totalWeight += $weight;
            }
        }

        // Normalize if not all factors are present
        if ($totalWeight > 0 && $totalWeight < 1) {
            return $weightedSum / $totalWeight;
        }

        return $weightedSum;
    }

    /**
     * Apply business rules to adjust score
     */
    public function applyBusinessRules(float $baseScore, array $driverFeatures, array $jobFeatures): float
    {
        $adjustedScore = $baseScore;

        // Boost for exact license match
        if ($this->hasExactLicenseMatch($driverFeatures, $jobFeatures)) {
            $adjustedScore *= 1.1;
        }

        // Penalty for overqualification
        if ($this->isOverqualified($driverFeatures, $jobFeatures)) {
            $adjustedScore *= 0.9;
        }

        // Boost for high-rated drivers
        if (($driverFeatures['avg_rating'] ?? 0) >= 4.5) {
            $adjustedScore *= 1.05;
        }

        // Penalty for salary mismatch
        if ($this->hasSalaryMismatch($driverFeatures, $jobFeatures)) {
            $adjustedScore *= 0.85;
        }

        // Ensure score stays within bounds
        return max(0, min(1, $adjustedScore));
    }

    /**
     * Calculate confidence score for the match
     */
    public function calculateConfidence(array $driverFeatures, array $jobFeatures): float
    {
        $confidence = 1.0;

        // Reduce confidence for incomplete profiles
        $driverCompleteness = $this->calculateProfileCompleteness($driverFeatures);
        $jobCompleteness = $this->calculateJobCompleteness($jobFeatures);

        $confidence *= ($driverCompleteness + $jobCompleteness) / 2;

        // Reduce confidence for new users
        if (($driverFeatures['review_count'] ?? 0) < 3) {
            $confidence *= 0.8;
        }

        // Reduce confidence for vague job requirements
        if (empty($jobFeatures['required_certifications']) && empty($jobFeatures['vehicle_type'])) {
            $confidence *= 0.7;
        }

        return $confidence;
    }

    /**
     * Check for exact license match
     */
    private function hasExactLicenseMatch(array $driverFeatures, array $jobFeatures): bool
    {
        $driverLicenses = $driverFeatures['licenses'] ?? [];
        $requiredLicense = $jobFeatures['required_license'] ?? '';

        return !empty($requiredLicense) && in_array($requiredLicense, $driverLicenses);
    }

    /**
     * Check if driver is overqualified
     */
    private function isOverqualified(array $driverFeatures, array $jobFeatures): bool
    {
        $driverExperience = $driverFeatures['years_experience'] ?? 0;
        $requiredExperience = $jobFeatures['min_experience'] ?? 0;

        // Consider overqualified if driver has 10+ years more than required
        return ($driverExperience - $requiredExperience) > 10;
    }

    /**
     * Check for salary mismatch
     */
    private function hasSalaryMismatch(array $driverFeatures, array $jobFeatures): bool
    {
        $driverMinSalary = $driverFeatures['min_salary'] ?? 0;
        $jobMaxSalary = $jobFeatures['salary_range']['max'] ?? PHP_INT_MAX;

        return $driverMinSalary > $jobMaxSalary;
    }

    /**
     * Calculate driver profile completeness
     */
    private function calculateProfileCompleteness(array $features): float
    {
        $requiredFields = [
            'years_experience',
            'licenses',
            'location',
            'available_immediately',
            'preferred_schedule'
        ];

        $completedFields = 0;
        foreach ($requiredFields as $field) {
            if (!empty($features[$field])) {
                $completedFields++;
            }
        }

        return $completedFields / count($requiredFields);
    }

    /**
     * Calculate job listing completeness
     */
    private function calculateJobCompleteness(array $features): float
    {
        $requiredFields = [
            'required_license',
            'min_experience',
            'location',
            'salary_range',
            'schedule_type'
        ];

        $completedFields = 0;
        foreach ($requiredFields as $field) {
            if (!empty($features[$field])) {
                $completedFields++;
            }
        }

        return $completedFields / count($requiredFields);
    }

    /**
     * Generate match insights
     */
    public function generateInsights(array $scores, array $driverFeatures, array $jobFeatures): array
    {
        $insights = [];

        // Skill match insights
        if ($scores['skill_match'] < 0.5) {
            $insights[] = [
                'type' => 'warning',
                'category' => 'skills',
                'message' => 'Οι δεξιότητες του οδηγού δεν ταιριάζουν πλήρως με τις απαιτήσεις'
            ];
        } elseif ($scores['skill_match'] > 0.9) {
            $insights[] = [
                'type' => 'success',
                'category' => 'skills',
                'message' => 'Εξαιρετική αντιστοιχία δεξιοτήτων!'
            ];
        }

        // Location insights
        if ($scores['location_match'] < 0.6) {
            $insights[] = [
                'type' => 'info',
                'category' => 'location',
                'message' => 'Η απόσταση μπορεί να είναι πρόκληση'
            ];
        }

        // Experience insights
        if ($scores['experience_match'] > 0.95) {
            $insights[] = [
                'type' => 'success',
                'category' => 'experience',
                'message' => 'Ιδανική εμπειρία για τη θέση'
            ];
        }

        // Availability insights
        if ($scores['availability_match'] < 0.5) {
            $insights[] = [
                'type' => 'warning',
                'category' => 'availability',
                'message' => 'Πιθανή ασυμβατότητα προγράμματος'
            ];
        }

        return $insights;
    }
}
