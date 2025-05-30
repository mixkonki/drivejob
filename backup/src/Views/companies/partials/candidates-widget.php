<?php

/**
 * AI Candidates Widget για το Company Dashboard
 * Εμφανίζει τους top υποψήφιους οδηγούς για μια αγγελία
 */

$jobId = $jobId ?? null;
$limit = $limit ?? 5;
?>

<div class="card shadow-sm mb-4" id="ai-candidates-widget">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-robot"></i> Προτεινόμενοι Υποψήφιοι
            <span class="badge bg-light text-primary float-end" id="candidates-count">0</span>
        </h5>
    </div>
    <div class="card-body p-0">
        <div id="candidates-container">
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Φόρτωση...</span>
                </div>
                <p class="mt-2 text-muted">Αναζήτηση υποψηφίων με AI...</p>
            </div>
        </div>
    </div>
    <div class="card-footer text-center">
        <a href="#" class="btn btn-sm btn-primary" id="view-all-candidates">
            Δείτε όλους τους υποψήφιους <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</div>

<style>
    #ai-candidates-widget .candidate-item {
        border-bottom: 1px solid #e0e0e0;
        padding: 15px;
        transition: all 0.3s ease;
    }

    #ai-candidates-widget .candidate-item:last-child {
        border-bottom: none;
    }

    #ai-candidates-widget .candidate-item:hover {
        background-color: #f8f9fa;
    }

    #ai-candidates-widget .candidate-name {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    #ai-candidates-widget .candidate-details {
        font-size: 0.9rem;
        color: #7f8c8d;
    }

    #ai-candidates-widget .match-score {
        font-weight: bold;
        font-size: 1.1rem;
    }

    #ai-candidates-widget .match-score.high {
        color: #27ae60;
    }

    #ai-candidates-widget .match-score.medium {
        color: #f39c12;
    }

    #ai-candidates-widget .match-score.low {
        color: #e74c3c;
    }

    #ai-candidates-widget .candidate-actions {
        margin-top: 10px;
    }

    #ai-candidates-widget .no-candidates {
        text-align: center;
        color: #7f8c8d;
        padding: 40px 20px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const jobId = <?php echo json_encode($jobId); ?>;
        if (jobId) {
            loadCandidates(jobId);
        } else {
            displayNoCandidates('Παρακαλώ επιλέξτε μια αγγελία');
        }
    });

    function loadCandidates(jobId) {
        const limit = <?php echo $limit; ?>;

        fetch(`<?php echo BASE_URL; ?>api/matching/job/candidates?job_id=${jobId}&limit=${limit}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('API Response:', data);
                if (data.success && data.data.candidates.length > 0) {
                    displayCandidates(data.data.candidates);
                    document.getElementById('candidates-count').textContent = data.data.count;

                    // Update view all link
                    document.getElementById('view-all-candidates').href =
                        `<?php echo BASE_URL; ?>companies/job-candidates/${jobId}`;
                } else {
                    displayNoCandidates();
                }
            })
            .catch(error => {
                console.error('Error loading candidates:', error);
                displayError();
            });
    }

    function displayCandidates(candidates) {
        const container = document.getElementById('candidates-container');
        let html = '';

        candidates.forEach(candidate => {
            const score = candidate.match_score;
            const scoreClass = score >= 70 ? 'high' : score >= 50 ? 'medium' : 'low';

            html += `
                <div class="candidate-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="candidate-name">
                                <a href="<?php echo BASE_URL; ?>drivers/profile/${candidate.driver_id}">
                                    ${candidate.name}
                                </a>
                            </div>
                            <div class="candidate-details">
                                <div><i class="fas fa-map-marker-alt"></i> ${candidate.city || 'Δεν έχει οριστεί'}</div>
                                <div><i class="fas fa-briefcase"></i> ${candidate.experience_years || 0} έτη εμπειρίας</div>
                                <div><i class="fas fa-star"></i> Βαθμολογία: ${candidate.rating || 'N/A'}/5</div>
                            </div>
                            <div class="candidate-actions">
                                <a href="<?php echo BASE_URL; ?>drivers/profile/${candidate.driver_id}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> Προφίλ
                                </a>
                                <button class="btn btn-sm btn-success" 
                                        onclick="contactCandidate(${candidate.driver_id})">
                                    <i class="fas fa-envelope"></i> Επικοινωνία
                                </button>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="match-score ${scoreClass}">
                                ${score}%
                            </div>
                            <div class="text-muted small">
                                ${candidate.recommendation}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    function displayNoCandidates(message = 'Δεν βρέθηκαν υποψήφιοι για αυτή την αγγελία.') {
        const container = document.getElementById('candidates-container');
        container.innerHTML = `
            <div class="no-candidates">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <p>${message}</p>
                <p class="small">Δοκιμάστε να τροποποιήσετε τις απαιτήσεις της αγγελίας.</p>
            </div>
        `;
    }

    function displayError() {
        const container = document.getElementById('candidates-container');
        container.innerHTML = `
            <div class="alert alert-danger m-3">
                <i class="fas fa-exclamation-triangle"></i>
                Σφάλμα κατά τη φόρτωση των υποψηφίων. 
                <a href="#" onclick="loadCandidates(<?php echo json_encode($jobId); ?>); return false;">
                    Δοκιμάστε ξανά
                </a>
            </div>
        `;
    }

    function contactCandidate(driverId) {
        // TODO: Implement contact functionality
        alert('Η λειτουργία επικοινωνίας θα υλοποιηθεί σύντομα.');
    }
</script>