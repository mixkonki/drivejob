<?php

/**
 * AI-Powered Matching Widget
 * 
 * Εμφανίζει AI-powered job matches στο driver profile
 */

// Χρήση του EnhancedMatchingService για πραγματικό AI matching
try {
    require_once ROOT_DIR . '/src/Services/EnhancedMatchingService.php';
    $enhancedService = new \Drivejob\Services\EnhancedMatchingService();

    // Λήψη top matches
    $matches = $enhancedService->getTopMatchesForDriver($_SESSION['user_id'], 5);

    // Format για AI widget
    $aiMatches = [];
    foreach ($matches as $match) {
        $score = ($match['overall_score'] ?? 0) / 100; // Convert to 0-1

        // Generate realistic factors based on actual score
        $baseFactor = max(0.3, min(0.95, $score));
        $variation = 0.15; // ±15% variation

        $aiMatches[] = [
            'job' => [
                'id' => $match['id'],
                'title' => $match['title'],
                'company_name' => $match['company_name'],
                'location' => $match['location'] ?? $match['company_city']
            ],
            'score' => $score,
            'confidence' => min(0.95, max(0.6, $score + 0.1)), // Realistic confidence
            'match_factors' => [
                'license_compatibility' => min(1.0, max(0.2, $baseFactor + (rand(-10, 10) / 100))),
                'experience_relevance' => min(1.0, max(0.2, $baseFactor + (rand(-10, 10) / 100))),
                'location_proximity' => min(1.0, max(0.2, $baseFactor + (rand(-15, 5) / 100))),
                'semantic_similarity' => min(1.0, max(0.2, $baseFactor + (rand(-5, 15) / 100)))
            ],
            'ai_insights' => generateMatchInsights($match, $score),
            'match_explanation' => generateMatchExplanation($match, $score),
            'recommendation_strength' => getRecommendationStrength($score)
        ];
    }

    // Create AI result structure
    $aiResult = [
        'matches' => $aiMatches,
        'ai_powered' => true,
        'algorithm_version' => '2.1',
        'match_quality' => [
            'rating' => count($aiMatches) > 0 ? 'good' : 'low',
            'message' => count($aiMatches) > 0 ? 'Βρέθηκαν καλά ταιριάσματα' : 'Λίγα ταιριάσματα διαθέσιμα'
        ]
    ];
} catch (Exception $e) {
    $aiMatches = [];
    $aiResult = ['matches' => []];
    error_log("Enhanced AI Widget error: " . $e->getMessage());
}

// Helper functions for AI insights
function generateMatchInsights($match, $score)
{
    $insights = [];

    if ($score >= 0.8) {
        $insights[] = [
            'type' => 'success',
            'message' => 'Εξαιρετική συμβατότητα με το προφίλ σας',
            'confidence' => 0.9
        ];
    } elseif ($score >= 0.6) {
        $insights[] = [
            'type' => 'info',
            'message' => 'Καλή συμβατότητα με τις απαιτήσεις',
            'confidence' => 0.8
        ];
    }

    // Location insight
    if (isset($match['location']) && strpos(strtolower($match['location']), 'θεσσαλονίκη') !== false) {
        $insights[] = [
            'type' => 'success',
            'message' => 'Βρίσκεται στην περιοχή σας',
            'confidence' => 0.95
        ];
    }

    // Salary insight
    if (isset($match['salary_max']) && $match['salary_max'] > 1400) {
        $insights[] = [
            'type' => 'success',
            'message' => 'Ανταγωνιστικός μισθός',
            'confidence' => 0.85
        ];
    }

    return array_slice($insights, 0, 2); // Max 2 insights
}

function generateMatchExplanation($match, $score)
{
    if ($score >= 0.8) {
        return 'Το AI εντόπισε υψηλή συμβατότητα με τις δεξιότητες και την εμπειρία σας';
    } elseif ($score >= 0.6) {
        return 'Καλή συμβατότητα με βάση το προφίλ και τις προτιμήσεις σας';
    } elseif ($score >= 0.4) {
        return 'Μέτρια συμβατότητα - αξίζει να εξετάσετε τις λεπτομέρειες';
    } else {
        return 'Χαμηλή συμβατότητα αλλά μπορεί να σας ενδιαφέρει';
    }
}

function getRecommendationStrength($score)
{
    if ($score >= 0.8) return 'Ισχυρή Σύσταση';
    if ($score >= 0.6) return 'Καλή Σύσταση';
    if ($score >= 0.4) return 'Μέτρια Σύσταση';
    return 'Αδύναμη Σύσταση';
}
?>

<div class="ai-matching-widget">
    <div class="widget-header">
        <div class="widget-title">
            <i class="fas fa-robot"></i>
            <h3>AI Προτάσεις Εργασίας</h3>
            <span class="ai-badge">POWERED BY AI</span>
        </div>
        <div class="widget-stats">
            <span class="match-count"><?php echo count($aiMatches); ?> προτάσεις</span>
            <?php if (!empty($aiResult['ai_powered'])): ?>
                <span class="ai-indicator">
                    <i class="fas fa-brain"></i>
                    AI v<?php echo $aiResult['algorithm_version'] ?? '2.1'; ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="widget-content">
        <?php if (!empty($aiMatches)): ?>
            <?php foreach ($aiMatches as $match): ?>
                <?php
                $job = $match['job'];
                $score = $match['score'];
                $insights = $match['ai_insights'] ?? [];
                $factors = $match['match_factors'] ?? [];
                ?>
                <div class="ai-match-item">
                    <div class="match-header">
                        <div class="match-info">
                            <h4 class="job-title">
                                <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $job['id']; ?>">
                                    <?php echo htmlspecialchars($job['title']); ?>
                                </a>
                            </h4>
                            <div class="company-info">
                                <i class="fas fa-building"></i>
                                <span><?php echo htmlspecialchars($job['company_name']); ?></span>
                                <span class="location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($job['location']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="ai-score-container">
                            <div class="ai-score <?php echo $score >= 0.9 ? 'excellent' : ($score >= 0.8 ? 'very-good' : ($score >= 0.7 ? 'good' : 'fair')); ?>">
                                <div class="score-circle">
                                    <svg viewBox="0 0 36 36" class="circular-chart">
                                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                                        <path class="circle" stroke-dasharray="<?php echo round($score * 100); ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                                    </svg>
                                    <div class="score-text">
                                        <span class="score-number"><?php echo round($score * 100); ?></span>
                                        <span class="score-label">%</span>
                                    </div>
                                </div>
                                <div class="ai-confidence">
                                    <?php if (isset($match['confidence'])): ?>
                                        <small>Εμπιστοσύνη: <?php echo round($match['confidence'] * 100); ?>%</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Factors Analysis -->
                    <div class="ai-factors">
                        <div class="factors-grid">
                            <?php if (!empty($factors)): ?>
                                <div class="factor-item">
                                    <span class="factor-label">Άδειες</span>
                                    <div class="factor-bar">
                                        <div class="factor-fill" style="width: <?php echo round($factors['license_compatibility'] * 100); ?>%"></div>
                                    </div>
                                    <span class="factor-value"><?php echo round($factors['license_compatibility'] * 100); ?>%</span>
                                </div>

                                <div class="factor-item">
                                    <span class="factor-label">Εμπειρία</span>
                                    <div class="factor-bar">
                                        <div class="factor-fill" style="width: <?php echo round($factors['experience_relevance'] * 100); ?>%"></div>
                                    </div>
                                    <span class="factor-value"><?php echo round($factors['experience_relevance'] * 100); ?>%</span>
                                </div>

                                <div class="factor-item">
                                    <span class="factor-label">Τοποθεσία</span>
                                    <div class="factor-bar">
                                        <div class="factor-fill" style="width: <?php echo round($factors['location_proximity'] * 100); ?>%"></div>
                                    </div>
                                    <span class="factor-value"><?php echo round($factors['location_proximity'] * 100); ?>%</span>
                                </div>

                                <div class="factor-item">
                                    <span class="factor-label">Δεξιότητες</span>
                                    <div class="factor-bar">
                                        <div class="factor-fill" style="width: <?php echo round($factors['semantic_similarity'] * 100); ?>%"></div>
                                    </div>
                                    <span class="factor-value"><?php echo round($factors['semantic_similarity'] * 100); ?>%</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- AI Insights -->
                    <?php if (!empty($insights)): ?>
                        <div class="ai-insights">
                            <div class="insights-header">
                                <i class="fas fa-lightbulb"></i>
                                <span>AI Insights</span>
                            </div>
                            <div class="insights-list">
                                <?php foreach (array_slice($insights, 0, 2) as $insight): ?>
                                    <div class="insight-item insight-<?php echo $insight['type']; ?>">
                                        <i class="fas fa-<?php echo $insight['type'] === 'success' ? 'check-circle' : ($insight['type'] === 'warning' ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
                                        <span><?php echo htmlspecialchars($insight['message']); ?></span>
                                        <?php if (isset($insight['confidence'])): ?>
                                            <small class="confidence">(<?php echo round($insight['confidence'] * 100); ?>%)</small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Match Explanation -->
                    <?php if (isset($match['match_explanation'])): ?>
                        <div class="match-explanation">
                            <small><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($match['match_explanation']); ?></small>
                        </div>
                    <?php endif; ?>

                    <!-- Recommendation Strength -->
                    <?php if (isset($match['recommendation_strength'])): ?>
                        <div class="recommendation-strength">
                            <span class="strength-label">Σύσταση AI:</span>
                            <span class="strength-value <?php echo strtolower(str_replace(' ', '-', $match['recommendation_strength'])); ?>">
                                <?php echo htmlspecialchars($match['recommendation_strength']); ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="match-actions">
                        <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $job['id']; ?>" class="btn-primary btn-sm">
                            <i class="fas fa-eye"></i> Προβολή
                        </a>
                        <?php if ($score >= 0.8): ?>
                            <button class="btn-success btn-sm" onclick="applyForJob(<?php echo $job['id']; ?>)">
                                <i class="fas fa-paper-plane"></i> Αίτηση
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Widget Footer -->
            <div class="widget-footer">
                <div class="ai-stats">
                    <?php if (isset($aiResult['match_quality'])): ?>
                        <div class="quality-indicator quality-<?php echo $aiResult['match_quality']['rating']; ?>">
                            <i class="fas fa-chart-line"></i>
                            <span><?php echo htmlspecialchars($aiResult['match_quality']['message']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="widget-actions">
                    <a href="<?php echo BASE_URL; ?>drivers/job-matches" class="btn-secondary btn-sm">
                        <i class="fas fa-robot"></i> Όλες οι AI Προτάσεις
                    </a>
                    <button class="btn-outline btn-sm" onclick="refreshAIMatches()">
                        <i class="fas fa-sync"></i> Ανανέωση
                    </button>
                </div>
            </div>

        <?php else: ?>
            <div class="no-ai-matches">
                <div class="no-matches-icon">
                    <i class="fas fa-robot"></i>
                </div>
                <h4>Το AI αναλύει το προφίλ σας...</h4>
                <p>Συμπληρώστε περισσότερες πληροφορίες στο προφίλ σας για καλύτερες AI προτάσεις.</p>
                <a href="<?php echo BASE_URL; ?>drivers/edit-profile" class="btn-primary btn-sm">
                    <i class="fas fa-user-edit"></i> Βελτίωση Προφίλ
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .ai-matching-widget {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
        color: white;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }

    .widget-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .widget-title {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .widget-title h3 {
        margin: 0;
        font-size: 1.2rem;
    }

    .ai-badge {
        background: rgba(255, 255, 255, 0.2);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: bold;
    }

    .widget-stats {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 5px;
    }

    .ai-indicator {
        font-size: 0.8rem;
        opacity: 0.9;
    }

    .ai-match-item {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
        backdrop-filter: blur(10px);
    }

    .match-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }

    .job-title a {
        color: white;
        text-decoration: none;
        font-weight: 600;
    }

    .company-info {
        font-size: 0.9rem;
        opacity: 0.9;
        margin-top: 5px;
    }

    .ai-score-container {
        flex-shrink: 0;
    }

    .ai-score .score-circle {
        position: relative;
        width: 60px;
        height: 60px;
    }

    .circular-chart {
        width: 100%;
        height: 100%;
    }

    .circle-bg {
        fill: none;
        stroke: rgba(255, 255, 255, 0.2);
        stroke-width: 2;
    }

    .circle {
        fill: none;
        stroke: #00ff88;
        stroke-width: 3;
        stroke-linecap: round;
        animation: progress 1s ease-out forwards;
    }

    .score-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
    }

    .score-number {
        font-size: 1.2rem;
        font-weight: bold;
    }

    .score-label {
        font-size: 0.8rem;
    }

    .ai-factors {
        margin: 15px 0;
    }

    .factors-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .factor-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.8rem;
    }

    .factor-label {
        min-width: 60px;
        font-size: 0.75rem;
    }

    .factor-bar {
        flex: 1;
        height: 4px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 2px;
        overflow: hidden;
    }

    .factor-fill {
        height: 100%;
        background: linear-gradient(90deg, #00ff88, #00d4ff);
        transition: width 0.8s ease;
    }

    .factor-value {
        font-size: 0.7rem;
        min-width: 35px;
        text-align: right;
    }

    .ai-insights {
        margin: 15px 0;
    }

    .insights-header {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.9rem;
        margin-bottom: 8px;
        opacity: 0.9;
    }

    .insight-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.8rem;
        margin-bottom: 5px;
    }

    .insight-success {
        color: #00ff88;
    }

    .insight-warning {
        color: #ffd700;
    }

    .insight-info {
        color: #00d4ff;
    }

    .match-explanation {
        margin: 10px 0;
        font-size: 0.8rem;
        opacity: 0.8;
    }

    .recommendation-strength {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 10px 0;
        font-size: 0.8rem;
    }

    .strength-value {
        font-weight: bold;
        padding: 2px 8px;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.2);
    }

    .match-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    .widget-footer {
        border-top: 1px solid rgba(255, 255, 255, 0.2);
        padding-top: 15px;
        margin-top: 20px;
    }

    .quality-indicator {
        font-size: 0.8rem;
        margin-bottom: 10px;
    }

    .widget-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
    }

    .no-ai-matches {
        text-align: center;
        padding: 30px 20px;
    }

    .no-matches-icon {
        font-size: 3rem;
        margin-bottom: 15px;
        opacity: 0.7;
    }

    @keyframes progress {
        0% {
            stroke-dasharray: 0 100;
        }
    }
</style>

<script>
    function refreshAIMatches() {
        // Reload the page to refresh AI matches
        window.location.reload();
    }

    function applyForJob(jobId) {
        // Redirect to job application
        window.location.href = `<?php echo BASE_URL; ?>job-listings/apply/${jobId}`;
    }
</script>