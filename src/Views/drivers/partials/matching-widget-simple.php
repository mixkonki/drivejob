<?php

/**
 * Simple AI Matching Widget που δεν χρησιμοποιεί API calls
 * Καλεί απευθείας το MatchingService για να αποφύγει session issues
 */

// Αποφυγή session conflicts
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'driver') {
    echo '<div class="ai-matching-widget"><p>Συνδεθείτε για να δείτε τις προτάσεις εργασίας.</p></div>';
    return;
}

$driverId = $_SESSION['user_id'];

try {
    // Χρήση του MatchingService απευθείας
    require_once ROOT_DIR . '/src/Services/MatchingService.php';

    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();
    $matchingService = new \Drivejob\Services\Matching\MatchingEngine($pdo);
    $result = $matchingService->driverMatches($driverId, 1, 3);

    $matches = $result['results'] ?? [];
} catch (Exception $e) {
    error_log("Matching Widget Error: " . $e->getMessage());
    $matches = [];
}
?>

<section class="profile-section ai-matching-widget">
    <h3><i class="fas fa-robot"></i> AI Προτάσεις Εργασίας</h3>

    <div id="ai-matches-container">
        <?php if (!empty($matches)): ?>
            <?php foreach ($matches as $match): ?>
                <div class="match-item">
                    <div class="match-score <?php echo $match['match_score'] >= 90 ? 'high' : ($match['match_score'] >= 70 ? 'medium' : 'low'); ?>">
                        <?php echo round($match['match_score']); ?>%
                    </div>
                    <div class="match-title">
                        <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $match['company_listing_id']; ?>">
                            <?php echo htmlspecialchars($match['title']); ?>
                        </a>
                    </div>
                    <div class="match-company">
                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($match['company_name']); ?>
                    </div>
                    <div class="match-location">
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($match['location']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-matches">
                <i class="fas fa-search fa-2x text-muted mb-2"></i>
                <p>Δεν βρέθηκαν προτάσεις αυτή τη στιγμή.</p>
                <p class="small">Ενημερώστε το προφίλ σας για καλύτερα αποτελέσματα.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="widget-footer text-center mt-3">
        <a href="<?php echo BASE_URL; ?>drivers/job-matches" class="btn btn-sm btn-outline-primary">
            Δείτε όλες τις προτάσεις <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</section>

<style>
    .ai-matching-widget {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .ai-matching-widget h3 {
        color: #333;
        font-size: 1.1rem;
        margin-bottom: 15px;
    }

    .ai-matching-widget .match-item {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 12px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
        position: relative;
    }

    .ai-matching-widget .match-item:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .ai-matching-widget .match-title {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.95rem;
        margin-bottom: 5px;
        padding-right: 60px;
        /* Space for score badge */
    }

    .ai-matching-widget .match-title a {
        color: #2c3e50;
        text-decoration: none;
    }

    .ai-matching-widget .match-title a:hover {
        color: #007bff;
    }

    .ai-matching-widget .match-company {
        color: #7f8c8d;
        font-size: 0.85rem;
        margin-bottom: 3px;
    }

    .ai-matching-widget .match-location {
        color: #95a5a6;
        font-size: 0.85rem;
    }

    .ai-matching-widget .match-score {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        font-size: 0.9rem;
    }

    .ai-matching-widget .match-score.high {
        background: linear-gradient(135deg, #28a745, #20c997);
    }

    .ai-matching-widget .match-score.medium {
        background: linear-gradient(135deg, #ffc107, #fd7e14);
    }

    .ai-matching-widget .match-score.low {
        background: linear-gradient(135deg, #dc3545, #e83e8c);
    }

    .ai-matching-widget .no-matches {
        text-align: center;
        color: #7f8c8d;
        padding: 20px;
    }

    .ai-matching-widget .widget-footer {
        border-top: 1px solid #e0e0e0;
        padding-top: 15px;
    }

    .ai-matching-widget .btn {
        padding: 8px 16px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 0.9rem;
    }

    .ai-matching-widget .btn-outline-primary {
        color: #007bff;
        border: 1px solid #007bff;
        background: transparent;
    }

    .ai-matching-widget .btn-outline-primary:hover {
        background: #007bff;
        color: white;
    }
</style>
</section>