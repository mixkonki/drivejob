<?php
// Driver Matching Widget
use Drivejob\Core\Session;

// Check if user is logged in as driver
if (!Session::get('user_id') || Session::get('user_role') !== 'driver') {
    return;
}

$driverId = Session::get('user_id');
?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-briefcase"></i> Προτεινόμενες Θέσεις Εργασίας
            <span class="badge bg-light text-primary float-end" id="match-count">0</span>
        </h5>
    </div>
    <div class="card-body p-0">
        <div id="job-matches-container">
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Φόρτωση...</span>
                </div>
                <p class="mt-2 text-muted">Αναζήτηση προτάσεων...</p>
            </div>
        </div>
    </div>
    <div class="card-footer text-center">
        <a href="<?php echo BASE_URL; ?>drivers/job-matches" class="btn btn-sm btn-primary">
            Δείτε όλες τις προτάσεις <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        loadJobMatches();
    });

    function loadJobMatches() {
        fetch('<?php echo BASE_URL; ?>api/matching/driver/matches?limit=5')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
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
            const scoreClass = match.overall_score >= 0.7 ? 'success' :
                match.overall_score >= 0.5 ? 'warning' : 'secondary';

            html += `
            <a href="${BASE_URL}job-listings/show/${match.job_id}" 
               class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${match.job_title}</h6>
                        <p class="mb-1 text-muted small">
                            <i class="fas fa-building"></i> ${match.company_name || 'Ιδιώτης'}
                        </p>
                        <p class="mb-0 text-muted small">
                            <i class="fas fa-map-marker-alt"></i> ${match.location || 'Δεν έχει οριστεί'}
                        </p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-${scoreClass}">
                            ${Math.round(match.overall_score * 100)}%
                        </span>
                        <p class="mb-0 mt-1 text-muted small">Ταίριασμα</p>
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
        </div>
    `;
    }

    function displayError() {
        const container = document.getElementById('job-matches-container');
        container.innerHTML = `
        <div class="alert alert-danger m-3">
            <i class="fas fa-exclamation-triangle"></i>
            Σφάλμα κατά τη φόρτωση των προτάσεων. Παρακαλώ δοκιμάστε ξανά.
        </div>
    `;
    }

    // Define BASE_URL for JavaScript
    const BASE_URL = '<?php echo BASE_URL; ?>';
</script>