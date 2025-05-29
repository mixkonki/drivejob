<?php

namespace Drivejob\Services\AI;

/**
 * Προηγμένος υπολογιστής βαθμολογίας για το AI matching
 * Χρησιμοποιεί weighted scoring και machine learning patterns
 */
class AdvancedScoreCalculator
{
    /**
     * Βάρη για κάθε κατηγορία matching
     */
    private const WEIGHTS = [
        'skill_match' => 0.35,      // 35% - Δεξιότητες και άδειες
        'location_match' => 0.25,   // 25% - Τοποθεσία
        'experience_match' => 0.25, // 25% - Εμπειρία
        'availability_match' => 0.15 // 15% - Διαθεσιμότητα
    ];

    /**
     * Υπολογισμός προηγμένης βαθμολογίας με ML patterns
     */
    public function calculateAdvancedScore(array $scores): float
    {
        // Βασικός weighted υπολογισμός
        $weightedScore = 0;
        foreach (self::WEIGHTS as $category => $weight) {
            if (isset($scores[$category])) {
                $weightedScore += $scores[$category] * $weight;
            }
        }

        // Εφαρμογή bonus/penalties
        $finalScore = $this->applyModifiers($weightedScore, $scores);

        // Κανονικοποίηση στο εύρος 0-1
        return max(0, min(1, $finalScore));
    }

    /**
     * Εφαρμογή modifiers βάσει patterns
     */
    private function applyModifiers(float $baseScore, array $scores): float
    {
        $modifiedScore = $baseScore;

        // Perfect match bonus
        if ($this->isPerfectMatch($scores)) {
            $modifiedScore *= 1.1; // 10% bonus
        }

        // Critical mismatch penalty
        if ($this->hasCriticalMismatch($scores)) {
            $modifiedScore *= 0.8; // 20% penalty
        }

        // Synergy bonus
        $synergyBonus = $this->calculateSynergyBonus($scores);
        $modifiedScore += $synergyBonus;

        return $modifiedScore;
    }

    /**
     * Έλεγχος για τέλειο ταίριασμα
     */
    private function isPerfectMatch(array $scores): bool
    {
        foreach ($scores as $score) {
            if ($score < 0.9) {
                return false;
            }
        }
        return true;
    }

    /**
     * Έλεγχος για κρίσιμη ασυμφωνία
     */
    private function hasCriticalMismatch(array $scores): bool
    {
        // Αν οι δεξιότητες είναι κάτω από 30%, είναι κρίσιμο
        if (isset($scores['skill_match']) && $scores['skill_match'] < 0.3) {
            return true;
        }

        // Αν η τοποθεσία είναι πολύ μακριά (κάτω από 20%)
        if (isset($scores['location_match']) && $scores['location_match'] < 0.2) {
            return true;
        }

        return false;
    }

    /**
     * Υπολογισμός synergy bonus
     */
    private function calculateSynergyBonus(array $scores): float
    {
        $bonus = 0;

        // Αν έχει και καλές δεξιότητες και καλή εμπειρία
        if (isset($scores['skill_match']) && isset($scores['experience_match'])) {
            if ($scores['skill_match'] > 0.8 && $scores['experience_match'] > 0.8) {
                $bonus += 0.05; // 5% bonus
            }
        }

        // Αν είναι κοντά και διαθέσιμος άμεσα
        if (isset($scores['location_match']) && isset($scores['availability_match'])) {
            if ($scores['location_match'] > 0.8 && $scores['availability_match'] > 0.9) {
                $bonus += 0.03; // 3% bonus
            }
        }

        return $bonus;
    }

    /**
     * Δημιουργία insights με βάση τα scores
     */
    public function generateAdvancedInsights(array $scores, array $driverFeatures, array $jobFeatures): array
    {
        $insights = [];

        // Skill insights
        if (isset($scores['skill_match'])) {
            $insights[] = $this->generateSkillInsight($scores['skill_match'], $driverFeatures, $jobFeatures);
        }

        // Location insights
        if (isset($scores['location_match'])) {
            $insights[] = $this->generateLocationInsight($scores['location_match'], $driverFeatures, $jobFeatures);
        }

        // Experience insights
        if (isset($scores['experience_match'])) {
            $insights[] = $this->generateExperienceInsight($scores['experience_match'], $driverFeatures, $jobFeatures);
        }

        // Availability insights
        if (isset($scores['availability_match'])) {
            $insights[] = $this->generateAvailabilityInsight($scores['availability_match'], $driverFeatures, $jobFeatures);
        }

        // Overall recommendation
        $overallScore = $this->calculateAdvancedScore($scores);
        $insights[] = $this->generateOverallRecommendation($overallScore, $scores);

        return array_filter($insights); // Remove null values
    }

    /**
     * Δημιουργία insight για δεξιότητες
     */
    private function generateSkillInsight(float $score, array $driverFeatures, array $jobFeatures): array
    {
        $insight = [
            'category' => 'skills',
            'score' => $score,
            'icon' => 'fas fa-certificate'
        ];

        if ($score >= 0.9) {
            $insight['type'] = 'success';
            $insight['message'] = 'Εξαιρετική αντιστοιχία δεξιοτήτων! Διαθέτετε όλα τα απαιτούμενα προσόντα.';
        } elseif ($score >= 0.7) {
            $insight['type'] = 'info';
            $insight['message'] = 'Πολύ καλή αντιστοιχία δεξιοτήτων. Διαθέτετε τα περισσότερα απαιτούμενα προσόντα.';

            // Προσδιορισμός των προσόντων που λείπουν
            $missingSkills = $this->findMissingSkills($driverFeatures, $jobFeatures);
            if (!empty($missingSkills)) {
                $insight['suggestions'] = [
                    'title' => 'Προτεινόμενες βελτιώσεις:',
                    'items' => $missingSkills
                ];
            }
        } elseif ($score >= 0.5) {
            $insight['type'] = 'warning';
            $insight['message'] = 'Μέτρια αντιστοιχία δεξιοτήτων. Υπάρχουν ευκαιρίες βελτίωσης.';
        } else {
            $insight['type'] = 'danger';
            $insight['message'] = 'Χαμηλή αντιστοιχία δεξιοτήτων. Απαιτείται σημαντική βελτίωση των προσόντων.';
        }

        return $insight;
    }

    /**
     * Δημιουργία insight για τοποθεσία
     */
    private function generateLocationInsight(float $score, array $driverFeatures, array $jobFeatures): array
    {
        $insight = [
            'category' => 'location',
            'score' => $score,
            'icon' => 'fas fa-map-marker-alt'
        ];

        if ($score >= 0.9) {
            $insight['type'] = 'success';
            $insight['message'] = 'Πολύ κοντινή απόσταση! Ιδανική τοποθεσία για καθημερινή μετακίνηση.';
        } elseif ($score >= 0.7) {
            $insight['type'] = 'info';
            $insight['message'] = 'Λογική απόσταση. Εφικτή καθημερινή μετακίνηση.';
        } elseif ($score >= 0.5) {
            $insight['type'] = 'warning';
            $insight['message'] = 'Σχετικά μεγάλη απόσταση. Μπορεί να απαιτείται μετεγκατάσταση.';
        } else {
            $insight['type'] = 'danger';
            $insight['message'] = 'Πολύ μεγάλη απόσταση. Συνιστάται μετεγκατάσταση.';
        }

        return $insight;
    }

    /**
     * Δημιουργία insight για εμπειρία
     */
    private function generateExperienceInsight(float $score, array $driverFeatures, array $jobFeatures): array
    {
        $insight = [
            'category' => 'experience',
            'score' => $score,
            'icon' => 'fas fa-briefcase'
        ];

        $driverExp = $driverFeatures['years_experience'] ?? 0;
        $requiredExp = $jobFeatures['min_experience'] ?? 0;

        if ($score >= 0.9) {
            $insight['type'] = 'success';
            $insight['message'] = sprintf(
                'Εξαιρετική εμπειρία! Έχετε %d έτη εμπειρίας (απαιτούνται %d).',
                $driverExp,
                $requiredExp
            );
        } elseif ($score >= 0.7) {
            $insight['type'] = 'info';
            $insight['message'] = 'Επαρκής εμπειρία για τη θέση.';
        } else {
            $insight['type'] = 'warning';
            $insight['message'] = sprintf(
                'Λιγότερη εμπειρία από την απαιτούμενη (%d έτη αντί για %d).',
                $driverExp,
                $requiredExp
            );
        }

        return $insight;
    }

    /**
     * Δημιουργία insight για διαθεσιμότητα
     */
    private function generateAvailabilityInsight(float $score, array $driverFeatures, array $jobFeatures): array
    {
        $insight = [
            'category' => 'availability',
            'score' => $score,
            'icon' => 'fas fa-clock'
        ];

        if ($score >= 0.9) {
            $insight['type'] = 'success';
            $insight['message'] = 'Άμεσα διαθέσιμος/η! Τέλεια αντιστοιχία με τις απαιτήσεις της θέσης.';
        } elseif ($score >= 0.7) {
            $insight['type'] = 'info';
            $insight['message'] = 'Καλή αντιστοιχία διαθεσιμότητας.';
        } else {
            $insight['type'] = 'warning';
            $insight['message'] = 'Η διαθεσιμότητά σας μπορεί να μην ταιριάζει πλήρως με τις απαιτήσεις.';
        }

        return $insight;
    }

    /**
     * Δημιουργία συνολικής σύστασης
     */
    private function generateOverallRecommendation(float $overallScore, array $scores): array
    {
        $recommendation = [
            'category' => 'overall',
            'score' => $overallScore,
            'icon' => 'fas fa-star'
        ];

        if ($overallScore >= 0.85) {
            $recommendation['type'] = 'success';
            $recommendation['message'] = 'Εξαιρετική αντιστοιχία! Συνιστούμε ανεπιφύλακτα να υποβάλετε αίτηση.';
            $recommendation['action'] = 'Υποβάλετε αίτηση τώρα';
        } elseif ($overallScore >= 0.7) {
            $recommendation['type'] = 'info';
            $recommendation['message'] = 'Πολύ καλή αντιστοιχία. Αξίζει να εξετάσετε αυτή τη θέση.';
            $recommendation['action'] = 'Μάθετε περισσότερα';
        } elseif ($overallScore >= 0.5) {
            $recommendation['type'] = 'warning';
            $recommendation['message'] = 'Μέτρια αντιστοιχία. Εξετάστε προσεκτικά πριν υποβάλετε αίτηση.';
            $recommendation['action'] = 'Δείτε λεπτομέρειες';
        } else {
            $recommendation['type'] = 'danger';
            $recommendation['message'] = 'Χαμηλή αντιστοιχία. Ίσως να μην είναι η κατάλληλη θέση για εσάς.';
            $recommendation['action'] = 'Δείτε άλλες προτάσεις';
        }

        // Προσθήκη των κορυφαίων δυνατών και αδύνατων σημείων
        $recommendation['strengths'] = $this->getTopStrengths($scores);
        $recommendation['weaknesses'] = $this->getTopWeaknesses($scores);

        return $recommendation;
    }

    /**
     * Εύρεση των προσόντων που λείπουν
     */
    private function findMissingSkills(array $driverFeatures, array $jobFeatures): array
    {
        $missing = [];

        // Έλεγχος αδειών
        if (isset($jobFeatures['required_license']) && !in_array($jobFeatures['required_license'], $driverFeatures['licenses'] ?? [])) {
            $missing[] = 'Απόκτηση άδειας ' . $jobFeatures['required_license'];
        }

        // Έλεγχος πιστοποιήσεων
        if (isset($jobFeatures['required_certifications'])) {
            $driverCerts = $driverFeatures['certifications'] ?? [];
            foreach ($jobFeatures['required_certifications'] as $cert) {
                if (!in_array($cert, $driverCerts)) {
                    $missing[] = 'Απόκτηση πιστοποίησης ' . $cert;
                }
            }
        }

        return array_slice($missing, 0, 3); // Return max 3 suggestions
    }

    /**
     * Λήψη των δυνατών σημείων
     */
    private function getTopStrengths(array $scores): array
    {
        $strengths = [];

        foreach ($scores as $category => $score) {
            if ($score >= 0.8) {
                $strengths[] = $this->getCategoryLabel($category);
            }
        }

        return array_slice($strengths, 0, 2);
    }

    /**
     * Λήψη των αδύνατων σημείων
     */
    private function getTopWeaknesses(array $scores): array
    {
        $weaknesses = [];

        foreach ($scores as $category => $score) {
            if ($score < 0.5) {
                $weaknesses[] = $this->getCategoryLabel($category);
            }
        }

        return array_slice($weaknesses, 0, 2);
    }

    /**
     * Μετάφραση κατηγορίας σε ετικέτα
     */
    private function getCategoryLabel(string $category): string
    {
        $labels = [
            'skill_match' => 'Δεξιότητες & Προσόντα',
            'location_match' => 'Τοποθεσία',
            'experience_match' => 'Εμπειρία',
            'availability_match' => 'Διαθεσιμότητα'
        ];

        return $labels[$category] ?? $category;
    }
}
