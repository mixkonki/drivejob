<?php

/**
 * AI Candidates Widget για το Company Dashboard - Version 4
 * Χρησιμοποιεί το direct.php endpoint
 */

// Λήψη των αγγελιών από το parent scope
$availableListings = $listings['results'] ?? [];
?>

<div class="card shadow-sm mb-4" id="ai-candidates-widget">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-robot"></i> Προτεινόμενοι Υποψήφιοι με AI
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($availableListings)): ?>
            <div class="mb-3">
                <label for="job-selector" class="form-label">Επιλέξτε Αγγελία:</label>
                <select class="form-select" id="job-selector">
                    <option value="">-- Επιλέξτε μια αγγελία --</option>
                    <?php foreach ($availableListings as $listing): ?>
                        <?php if ($listing['is_active']): ?>
                            <option value="<?php echo $listing['id']; ?>">
                                <?php echo htmlspecialchars($listing['title']); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="candidates-container">
                <div class="text-center text-muted p-4">
                    <i class="fas fa-arrow-up fa-2x mb-2"></i>
                    <p>Επιλέξτε μια αγγελία για να δείτε τους προτεινόμενους υποψήφιους</p>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Δεν έχετε ενεργές αγγελίες.
                <a href="<?php echo BASE_URL; ?>job-listings/create" class="alert-link">Δημιουργήστε μια νέα αγγελία</a>
                για να δείτε προτεινόμενους υποψήφιους.
            </div>
        <?php endif; ?>
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

    #ai-candidates-widget .loading-spinner {
        text-align: center;
        padding: 40px 20px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const jobSelector = document.getElementById('job-selector');
        const candidatesContainer = document.getElementById('candidates-container');

        if (jobSelector) {
            jobSelector.addEventListener('change', function() {
                const jobId = this.value;
                if (jobId) {
                    loadCandidates(jobId);
                } else {
                    candidatesContainer.innerHTML = `
                    <div class="text-center text-muted p-4">
                        <i class="fas fa-arrow-up fa-2x mb-2"></i>
                        <p>Επιλέξτε μια αγγελία για να δείτε τους προτεινόμενους υποψήφιους</p>
                    </div>
                `;
                }
            });
        }

        function loadCandidates(jobId) {
            // Show loading spinner
            candidatesContainer.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Φόρτωση...</span>
                </div>
                <p class="mt-2 text-muted">Αναζήτηση υποψηφίων με AI...</p>
            </div>
        `;

            // Χρήση του direct.php endpoint
            fetch(`<?php echo BASE_URL; ?>api/matching/job/candidates/direct.php?job_id=${jobId}&limit=5`, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    console.log('Response status:', response.status);

                    return response.json().then(data => {
                        if (!response.ok) {
                            throw new Error(data.error || `HTTP error! status: ${response.status}`);
                        }
                        return data;
                    });
                })
                .then(data => {
                    console.log('API Response:', data);
                    if (data.success && data.data && data.data.candidates && data.data.candidates.length > 0) {
                        displayCandidates(data.data.candidates);
                    } else if (data.error) {
                        console.error('API Error:', data.error);
                        displayError(data.error);
                    } else {
                        displayNoCandidates();
                    }
                })
                .catch(error => {
                    console.error('Error loading candidates:', error);
                    displayError(error.message);
                });
        }

        function displayCandidates(candidates) {
            let html = '<div class="candidates-list">';

            candidates.forEach(candidate => {
                const score = candidate.match_score || 0;
                const scoreClass = score >= 70 ? 'high' : score >= 50 ? 'medium' : 'low';

                html += `
                <div class="candidate-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="candidate-name">
                                <a href="<?php echo BASE_URL; ?>drivers/profile/${candidate.driver_id}">
                                    ${candidate.name || 'Ανώνυμος Οδηγός'}
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
                                Ταίριασμα
                            </div>
                            <div class="text-muted small mt-1">
                                ${candidate.recommendation || ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            });

            html += '</div>';
            candidatesContainer.innerHTML = html;
        }

        function displayNoCandidates() {
            candidatesContainer.innerHTML = `
            <div class="text-center text-muted p-4">
                <i class="fas fa-users-slash fa-3x mb-3"></i>
                <p>Δεν βρέθηκαν υποψήφιοι για αυτή την αγγελία.</p>
                <p class="small">Δοκιμάστε να τροποποιήσετε τις απαιτήσεις της αγγελίας.</p>
            </div>
        `;
        }

        function displayError(errorMessage = '') {
            candidatesContainer.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                Σφάλμα κατά τη φόρτωση των υποψηφίων. 
                ${errorMessage ? `<br><small>${errorMessage}</small>` : ''}
                <br>
                <a href="#" onclick="location.reload(); return false;">
                    Δοκιμάστε ξανά
                </a>
            </div>
        `;
        }

        window.contactCandidate = function(driverId) {
            alert('Η λειτουργία επικοινωνίας θα υλοποιηθεί σύντομα.');
        }
    });
</script>