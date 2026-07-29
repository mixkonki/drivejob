<?php

/**
 * AI Matching Widget Component για το Driver Dashboard
 * 
 * @param int $limit Αριθμός προτάσεων που θα εμφανιστούν (default: 5)
 * @param bool $showViewAll Εμφάνιση κουμπιού "Δείτε όλες" (default: true)
 */

$limit = $limit ?? 5;
$showViewAll = $showViewAll ?? true;
?>

<div class="card shadow-sm mb-4" id="ai-matching-widget">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-robot"></i> Προτεινόμενες Θέσεις Εργασίας
            <span class="badge bg-light text-primary float-end" id="match-count">0</span>
        </h5>
    </div>
    <div class="card-body p-0">
        <div id="job-matches-container">
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Φόρτωση...</span>
                </div>
                <p class="mt-2 text-muted">Αναζήτηση προτάσεων με AI...</p>
            </div>
        </div>
    </div>
    <?php if ($showViewAll): ?>
        <div class="card-footer text-center">
            <a href="<?php echo BASE_URL; ?>drivers/job-matches" class="btn btn-sm btn-primary">
                Δείτε όλες τις προτάσεις <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
    #ai-matching-widget .list-group-item {
        transition: all 0.3s ease;
    }

    #ai-matching-widget .list-group-item:hover {
        background-color: #f8f9fa;
        transform: translateX(5px);
    }

    #ai-matching-widget .match-score-badge {
        min-width: 60px;
        font-weight: bold;
    }

    #ai-matching-widget .recommendation-text {
        font-size: 0.75rem;
        font-style: italic;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        loadJobMatches();
    });

    function loadJobMatches() {
        const limit = <?php echo $limit; ?>;

        fetch(`<?php echo BASE_URL; ?>api/matching/driver/matches?limit=${limit}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('API Response:', data);
                if (data.success && data.data.matches.length > 0) {
                    displayMatches(data.data.matches);
                    document.getElementById('match-count').textContent = data.data.count;
                } else {
                    displayNoMatches();
                }
            })
            .catch(error => {
                console.error('Error loading matches:', error);
                displayError();
            });
    }

    function displayMatches(matches) {
        const container = document.getElementById('job-matches-container');
        let html = '<div class="list-group list-group-flush">';

        matches.forEach(match => {
            const score = match.match_score / 100;
            const scoreClass = score >= 0.7 ? 'success' :
                score >= 0.5 ? 'warning' : 'secondary';

            html += `
            <a href="<?php echo BASE_URL; ?>job-listings/show/${match.job_id}" 
               class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${match.title || 'Αγγελία #' + match.job_id}</h6>
                        <p class="mb-1 text-muted small">
                            <i class="fas fa-building"></i> ${match.company || 'Ιδιώτης'}
                        </p>
                        <p class="mb-0 text-muted small">
                            <i class="fas fa-map-marker-alt"></i> ${match.location || 'Δεν έχει οριστεί'}
                        </p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-${scoreClass} match-score-badge">
                            ${match.match_score}%
                        </span>
                        <p class="mb-0 mt-1 text-muted recommendation-text">
                            ${match.recommendation}
                        </p>
                    </div>
                </div>
            </a>
        `;
        });

        html += '</div>';
        container.innerHTML = html;
    }

    function displayNoMatches() {
        const container = document.getElementById('job-matches-container');
        container.innerHTML = `
        <div class="text-center p-4">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <p class="text-muted">Δεν βρέθηκαν προτάσεις αυτή τη στιγμή.</p>
            <p class="text-muted small">Ενημερώστε το προφίλ σας για καλύτερα αποτελέσματα.</p>
            <a href="<?php echo BASE_URL; ?>drivers/edit-profile" class="btn btn-sm btn-outline-primary mt-2">
                <i class="fas fa-edit"></i> Ενημέρωση Προφίλ
            </a>
        </div>
    `;
    }

    function displayError() {
        const container = document.getElementById('job-matches-container');
        container.innerHTML = `
        <div class="alert alert-danger m-3">
            <i class="fas fa-exclamation-triangle"></i>
            Σφάλμα κατά τη φόρτωση των προτάσεων. 
            <a href="#" onclick="loadJobMatches(); return false;">Δοκιμάστε ξανά</a>
        </div>
    `;
    }
</script>