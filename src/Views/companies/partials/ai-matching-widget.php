<?php

/**
 * AI Matching Widget για Εταιρείες
 * 
 * Εμφανίζει AI-powered προτάσεις οδηγών για την εταιρεία
 */

// Λήψη AI matches για την εταιρεία
try {
    require_once ROOT_DIR . '/src/Services/AIMatchingService.php';
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();
    $aiMatchingService = new \Drivejob\Services\Matching\MatchingEngine($pdo);

    // Για εταιρείες, βρίσκουμε οδηγούς που ταιριάζουν με τις ανοιχτές θέσεις τους
    $companyId = $_SESSION['user_id'];
    $aiMatches = $aiMatchingService->companyDriverMatches($companyId, 1, 5);
} catch (Exception $e) {
    error_log("AI Matching Widget Error: " . $e->getMessage());
    $aiMatches = ['matches' => [], 'total' => 0];
}
?>

<div class="ai-matching-widget">
    <div class="ai-widget-header">
        <div class="ai-branding">
            <div class="ai-icon">
                <i class="fas fa-robot"></i>
            </div>
            <div class="ai-title">
                <h3>AI Matching System</h3>
                <span class="ai-version">Powered by GPT-4 • v2.1</span>
            </div>
        </div>
        <div class="ai-status">
            <span class="status-indicator active"></span>
            <span class="status-text">AI Active</span>
        </div>
    </div>

    <div class="ai-widget-content">
        <?php if (!empty($aiMatches['matches'])): ?>
            <div class="ai-matches-section">
                <h4><i class="fas fa-users"></i> Προτεινόμενοι Οδηγοί</h4>
                <div class="ai-matches-list">
                    <?php foreach (array_slice($aiMatches['matches'], 0, 3) as $match): ?>
                        <div class="ai-match-item">
                            <div class="match-header">
                                <div class="driver-info">
                                    <div class="driver-avatar">
                                        <?php if (isset($match['driver']['profile_image']) && $match['driver']['profile_image']): ?>
                                            <img src="<?php echo BASE_URL . htmlspecialchars($match['driver']['profile_image']); ?>" alt="Driver">
                                        <?php else: ?>
                                            <i class="fas fa-user"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="driver-details">
                                        <h5><?php echo htmlspecialchars($match['driver']['first_name'] . ' ' . $match['driver']['last_name']); ?></h5>
                                        <span class="driver-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($match['driver']['city'] ?? 'Δεν έχει οριστεί'); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="match-score-circle">
                                    <svg viewBox="0 0 36 36" class="circular-chart">
                                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                                        <path class="circle" stroke-dasharray="<?php echo round($match['score'] * 100); ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                                        <text x="18" y="20.35" class="percentage"><?php echo round($match['score'] * 100); ?>%</text>
                                    </svg>
                                </div>
                            </div>

                            <div class="match-factors">
                                <div class="factor-grid">
                                    <?php
                                    $factors = $match['match_factors'] ?? [];
                                    $factorLabels = [
                                        'license_compatibility' => 'Άδειες',
                                        'experience_relevance' => 'Εμπειρία',
                                        'location_proximity' => 'Τοποθεσία',
                                        'availability_alignment' => 'Διαθεσιμότητα'
                                    ];

                                    foreach ($factorLabels as $key => $label):
                                        if (isset($factors[$key])):
                                            $score = round($factors[$key] * 100);
                                    ?>
                                            <div class="factor-item">
                                                <span class="factor-label"><?php echo $label; ?></span>
                                                <div class="factor-bar">
                                                    <div class="factor-fill" style="width: <?php echo $score; ?>%"></div>
                                                </div>
                                                <span class="factor-score"><?php echo $score; ?>%</span>
                                            </div>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </div>
                            </div>

                            <?php if (!empty($match['ai_insights'])): ?>
                                <div class="ai-insights">
                                    <h6><i class="fas fa-lightbulb"></i> AI Insights</h6>
                                    <?php foreach (array_slice($match['ai_insights'], 0, 2) as $insight): ?>
                                        <div class="insight-item <?php echo $insight['type']; ?>">
                                            <i class="insight-icon fas fa-<?php echo $insight['type'] === 'success' ? 'check-circle' : ($insight['type'] === 'warning' ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
                                            <span class="insight-text"><?php echo htmlspecialchars($insight['message']); ?></span>
                                            <?php if (isset($insight['confidence'])): ?>
                                                <span class="confidence-badge"><?php echo round($insight['confidence'] * 100); ?>%</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="match-actions">
                                <a href="<?php echo BASE_URL; ?>drivers/profile/<?php echo $match['driver']['user_id']; ?>" class="btn-view-profile">
                                    <i class="fas fa-user"></i> Προβολή Προφίλ
                                </a>
                                <button class="btn-contact-driver" data-driver-id="<?php echo $match['driver']['user_id']; ?>">
                                    <i class="fas fa-envelope"></i> Επικοινωνία
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="ai-widget-footer">
                    <div class="ai-stats">
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $aiMatches['total']; ?></span>
                            <span class="stat-label">Συνολικά Matches</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo count(array_filter($aiMatches['matches'], function ($m) {
                                                            return $m['score'] >= 0.8;
                                                        })); ?></span>
                            <span class="stat-label">High Quality</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">GPT-4</span>
                            <span class="stat-label">AI Model</span>
                        </div>
                    </div>

                    <a href="<?php echo BASE_URL; ?>companies/driver-matches" class="btn-view-all">
                        <i class="fas fa-eye"></i> Προβολή Όλων (<?php echo $aiMatches['total']; ?>)
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="no-matches">
                <div class="no-matches-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h4>Δεν βρέθηκαν ταιριάσματα</h4>
                <p>Το AI σύστημα δεν βρήκε κατάλληλους οδηγούς αυτή τη στιγμή.</p>
                <div class="suggestions">
                    <h6>Προτάσεις:</h6>
                    <ul>
                        <li>Δημιουργήστε περισσότερες αγγελίες εργασίας</li>
                        <li>Ενημερώστε τις απαιτήσεις των θέσεων</li>
                        <li>Επεκτείνετε την γεωγραφική περιοχή αναζήτησης</li>
                    </ul>
                </div>
                <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn-create-listing">
                    <i class="fas fa-plus"></i> Δημιουργία Αγγελίας
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .ai-matching-widget {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 0;
        margin-bottom: 25px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        overflow: hidden;
        color: white;
    }

    .ai-widget-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 25px;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
    }

    .ai-branding {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .ai-icon {
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .ai-title h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }

    .ai-version {
        font-size: 12px;
        opacity: 0.8;
    }

    .ai-status {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
    }

    .status-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #4CAF50;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }

        100% {
            opacity: 1;
        }
    }

    .ai-widget-content {
        padding: 25px;
    }

    .ai-matches-section h4 {
        margin-bottom: 20px;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .ai-match-item {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .match-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .driver-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .driver-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .driver-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .driver-details h5 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }

    .driver-location {
        font-size: 13px;
        opacity: 0.8;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .match-score-circle {
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
        stroke-width: 2.8;
    }

    .circle {
        fill: none;
        stroke: #4CAF50;
        stroke-width: 2.8;
        stroke-linecap: round;
        animation: progress 1s ease-out forwards;
    }

    .percentage {
        fill: white;
        font-family: sans-serif;
        font-size: 0.5em;
        text-anchor: middle;
        font-weight: bold;
    }

    @keyframes progress {
        0% {
            stroke-dasharray: 0 100;
        }
    }

    .match-factors {
        margin-bottom: 15px;
    }

    .factor-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .factor-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
    }

    .factor-label {
        min-width: 60px;
        font-weight: 500;
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
        background: linear-gradient(90deg, #4CAF50, #8BC34A);
        border-radius: 2px;
        transition: width 0.8s ease;
    }

    .factor-score {
        font-weight: 600;
        min-width: 35px;
        text-align: right;
    }

    .ai-insights {
        margin-bottom: 15px;
    }

    .ai-insights h6 {
        font-size: 14px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .insight-item {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        font-size: 13px;
        padding: 8px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 6px;
    }

    .insight-icon {
        font-size: 14px;
    }

    .insight-item.success .insight-icon {
        color: #4CAF50;
    }

    .insight-item.warning .insight-icon {
        color: #FF9800;
    }

    .insight-item.info .insight-icon {
        color: #2196F3;
    }

    .insight-text {
        flex: 1;
    }

    .confidence-badge {
        background: rgba(255, 255, 255, 0.2);
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 600;
    }

    .match-actions {
        display: flex;
        gap: 10px;
    }

    .btn-view-profile,
    .btn-contact-driver {
        flex: 1;
        padding: 8px 12px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 6px;
        background: rgba(255, 255, 255, 0.1);
        color: white;
        text-decoration: none;
        font-size: 12px;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .btn-view-profile:hover,
    .btn-contact-driver:hover {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        text-decoration: none;
    }

    .ai-widget-footer {
        border-top: 1px solid rgba(255, 255, 255, 0.2);
        padding-top: 20px;
        margin-top: 20px;
    }

    .ai-stats {
        display: flex;
        justify-content: space-around;
        margin-bottom: 15px;
    }

    .stat-item {
        text-align: center;
    }

    .stat-value {
        display: block;
        font-size: 18px;
        font-weight: bold;
        color: #4CAF50;
    }

    .stat-label {
        font-size: 11px;
        opacity: 0.8;
    }

    .btn-view-all {
        display: block;
        width: 100%;
        padding: 12px;
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 8px;
        color: white;
        text-decoration: none;
        text-align: center;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-view-all:hover {
        background: rgba(255, 255, 255, 0.3);
        color: white;
        text-decoration: none;
    }

    .no-matches {
        text-align: center;
        padding: 30px 20px;
    }

    .no-matches-icon {
        font-size: 48px;
        margin-bottom: 20px;
        opacity: 0.6;
    }

    .no-matches h4 {
        margin-bottom: 10px;
    }

    .no-matches p {
        opacity: 0.8;
        margin-bottom: 20px;
    }

    .suggestions {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        text-align: left;
    }

    .suggestions h6 {
        margin-bottom: 10px;
    }

    .suggestions ul {
        margin: 0;
        padding-left: 20px;
    }

    .suggestions li {
        margin-bottom: 5px;
        font-size: 14px;
    }

    .btn-create-listing {
        display: inline-block;
        padding: 12px 24px;
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 8px;
        color: white;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-create-listing:hover {
        background: rgba(255, 255, 255, 0.3);
        color: white;
        text-decoration: none;
    }
</style>

<script>
    // Contact driver functionality
    document.addEventListener('DOMContentLoaded', function() {
        const contactButtons = document.querySelectorAll('.btn-contact-driver');

        contactButtons.forEach(button => {
            button.addEventListener('click', function() {
                const driverId = this.getAttribute('data-driver-id');

                // Redirect to messaging system or open modal
                window.location.href = `<?php echo BASE_URL; ?>companies/messages/new?driver_id=${driverId}`;
            });
        });
    });
</script>