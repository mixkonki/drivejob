<?php

/**
 * AI Matching Widget για το Driver Profile
 * Εμφανίζει τις top 3 προτεινόμενες θέσεις εργασίας
 */
?>

<section class="profile-section ai-matching-widget">
    <h3><i class="fas fa-robot"></i> AI Προτάσεις Εργασίας</h3>

    <div id="ai-matches-container">
        <div class="text-center p-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Φόρτωση...</span>
            </div>
            <p class="mt-2 text-muted small">Αναζήτηση προτάσεων...</p>
        </div>
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
        float: right;
        font-weight: bold;
        font-size: 0.9rem;
    }

    .ai-matching-widget .match-score.high {
        color: #27ae60;
    }

    .ai-matching-widget .match-score.medium {
        color: #f39c12;
    }

    .ai-matching-widget .match-score.low {
        color: #e74c3c;
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
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        loadAIMatches();
    });

    function loadAIMatches() {
        fetch(`<?php echo BASE_URL; ?>api/matching/driver/matches?limit=3`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data.matches.length > 0) {
                    displayAIMatches(data.data.matches);
                } else {
                    displayNoAIMatches();
                }
            })
            .catch(error => {
                console.error('Error loading AI matches:', error);
                displayAIMatchError();
            });
    }

    function displayAIMatches(matches) {
        const container = document.getElementById('ai-matches-container');
        let html = '';

        matches.forEach(match => {
            const score = match.score;
            const scoreClass = score >= 0.7 ? 'high' : score >= 0.5 ? 'medium' : 'low';
            const scorePercent = Math.round(score * 100);

            html += `
            <div class="match-item">
                <div class="match-score ${scoreClass}">${scorePercent}%</div>
                <div class="match-title">
                    <a href="<?php echo BASE_URL; ?>job-listings/show/${match.job.id}">
                        ${match.job.title || 'Αγγελία #' + match.job.id}
                    </a>
                </div>
                <div class="match-company">
                    <i class="fas fa-building"></i> ${match.job.company_name || 'Ιδιώτης'}
                </div>
                <div class="match-location">
                    <i class="fas fa-map-marker-alt"></i> ${match.job.location || match.job.company_city || 'Δεν έχει οριστεί'}
                </div>
            </div>
        `;
        });

        container.innerHTML = html;
    }

    function displayNoAIMatches() {
        const container = document.getElementById('ai-matches-container');
        container.innerHTML = `
        <div class="no-matches">
            <i class="fas fa-search fa-2x text-muted mb-2"></i>
            <p>Δεν βρέθηκαν προτάσεις αυτή τη στιγμή.</p>
            <p class="small">Ενημερώστε το προφίλ σας για καλύτερα αποτελέσματα.</p>
        </div>
    `;
    }

    function displayAIMatchError() {
        const container = document.getElementById('ai-matches-container');
        container.innerHTML = `
        <div class="alert alert-warning small">
            <i class="fas fa-exclamation-triangle"></i>
            Προσωρινό πρόβλημα φόρτωσης προτάσεων.
            <a href="#" onclick="loadAIMatches(); return false;">Δοκιμάστε ξανά</a>
        </div>
    `;
    }
</script>