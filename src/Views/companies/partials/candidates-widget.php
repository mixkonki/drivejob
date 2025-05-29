<?php

/**
 * AI Candidates Widget for Company Dashboard
 */
?>
<div class="card mb-4" id="ai-candidates-widget">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            <i class="fas fa-users"></i> Προτεινόμενοι Υποψήφιοι
            <button class="btn btn-sm btn-light float-right" onclick="showJobSelector()">
                <i class="fas fa-briefcase"></i> Επιλογή Θέσης
            </button>
        </h5>
    </div>
    <div class="card-body">
        <div id="job-selector" class="mb-3" style="display: none;">
            <label for="job-select">Επιλέξτε θέση εργασίας:</label>
            <select class="form-control" id="job-select" onchange="loadCandidates()">
                <option value="">-- Επιλέξτε θέση --</option>
            </select>
        </div>

        <div id="candidates-loading" class="text-center py-4" style="display: none;">
            <div class="spinner-border text-success" role="status">
                <span class="sr-only">Φόρτωση...</span>
            </div>
            <p class="mt-2">Αναζήτηση κατάλληλων υποψηφίων...</p>
        </div>

        <div id="candidates-results" style="display: none;">
            <!-- Results will be populated here -->
        </div>

        <div id="candidates-empty" class="text-center py-4">
            <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
            <p class="text-muted">Επιλέξτε μια θέση εργασίας για να δείτε προτεινόμενους υποψήφιους.</p>
        </div>

        <div id="candidates-error" class="alert alert-danger" style="display: none;">
            <i class="fas fa-exclamation-triangle"></i>
            <span id="error-message"></span>
        </div>
    </div>
</div>

<!-- Candidate Details Modal -->
<div class="modal fade" id="candidateDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Προφίλ Υποψηφίου</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="candidate-details-content">
                <!-- Details will be populated here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Κλείσιμο</button>
                <button type="button" class="btn btn-success" id="contact-driver-btn">
                    <i class="fas fa-envelope"></i> Επικοινωνία
                </button>
                <button type="button" class="btn btn-primary" id="invite-driver-btn">
                    <i class="fas fa-user-plus"></i> Πρόσκληση σε Συνέντευξη
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .candidate-card {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .candidate-card:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .candidate-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #666;
    }

    .match-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        color: white;
    }

    .match-badge.high {
        background: #28a745;
    }

    .match-badge.medium {
        background: #ffc107;
    }

    .match-badge.low {
        background: #dc3545;
    }

    .skill-tag {
        display: inline-block;
        padding: 3px 8px;
        margin: 2px;
        background: #e9ecef;
        border-radius: 12px;
        font-size: 12px;
    }

    .skill-tag.matched {
        background: #d4edda;
        color: #155724;
    }

    .experience-bar {
        height: 6px;
        background: #e0e0e0;
        border-radius: 3px;
        overflow: hidden;
        margin-top: 5px;
    }

    .experience-fill {
        height: 100%;
        background: #28a745;
        transition: width 0.3s ease;
    }

    .candidate-stats {
        display: flex;
        justify-content: space-around;
        padding: 15px 0;
        border-top: 1px solid #e0e0e0;
        margin-top: 15px;
    }

    .stat-item {
        text-align: center;
    }

    .stat-value {
        font-size: 24px;
        font-weight: bold;
        color: #333;
    }

    .stat-label {
        font-size: 12px;
        color: #666;
    }
</style>

<script>
    let currentCandidates = [];
    let companyJobs = [];

    // Load company jobs on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadCompanyJobs();
    });

    function loadCompanyJobs() {
        // This should be replaced with actual API call to get company's job listings
        fetch('<?php echo BASE_URL; ?>api/company/jobs')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    companyJobs = data.data;
                    populateJobSelector();
                }
            })
            .catch(error => {
                console.error('Error loading jobs:', error);
                // For testing, use mock data
                companyJobs = [{
                        id: 1,
                        title: 'Οδηγός Φορτηγού C+E'
                    },
                    {
                        id: 2,
                        title: 'Οδηγός Βυτίου'
                    },
                    {
                        id: 3,
                        title: 'Οδηγός Διανομής'
                    }
                ];
                populateJobSelector();
            });
    }

    function populateJobSelector() {
        const select = document.getElementById('job-select');
        select.innerHTML = '<option value="">-- Επιλέξτε θέση --</option>';

        companyJobs.forEach(job => {
            const option = document.createElement('option');
            option.value = job.id;
            option.textContent = job.title;
            select.appendChild(option);
        });
    }

    function showJobSelector() {
        const selector = document.getElementById('job-selector');
        selector.style.display = selector.style.display === 'none' ? 'block' : 'none';
    }

    function loadCandidates() {
        const jobId = document.getElementById('job-select').value;
        if (!jobId) return;

        const loadingEl = document.getElementById('candidates-loading');
        const resultsEl = document.getElementById('candidates-results');
        const emptyEl = document.getElementById('candidates-empty');
        const errorEl = document.getElementById('candidates-error');

        // Show loading
        loadingEl.style.display = 'block';
        resultsEl.style.display = 'none';
        emptyEl.style.display = 'none';
        errorEl.style.display = 'none';

        fetch(`<?php echo BASE_URL; ?>api/matching/job/candidates?job_id=${jobId}&limit=10`)
            .then(response => response.json())
            .then(data => {
                loadingEl.style.display = 'none';

                if (data.success && data.data.candidates.length > 0) {
                    currentCandidates = data.data.candidates;
                    displayCandidates(data.data.candidates);
                } else {
                    emptyEl.innerHTML = `
                        <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Δεν βρέθηκαν κατάλληλοι υποψήφιοι για αυτή τη θέση.</p>
                        <button class="btn btn-success btn-sm" onclick="loadCandidates()">
                            <i class="fas fa-sync"></i> Ανανέωση
                        </button>
                    `;
                    emptyEl.style.display = 'block';
                }
            })
            .catch(error => {
                loadingEl.style.display = 'none';
                errorEl.style.display = 'block';
                document.getElementById('error-message').textContent =
                    'Σφάλμα κατά τη φόρτωση των υποψηφίων. Παρακαλώ δοκιμάστε ξανά.';
                console.error('Error loading candidates:', error);
            });
    }

    function displayCandidates(candidates) {
        const resultsEl = document.getElementById('candidates-results');
        resultsEl.innerHTML = '';
        resultsEl.style.display = 'block';

        candidates.forEach((candidate, index) => {
            const driver = candidate.driver;
            const scoreClass = candidate.score >= 0.8 ? 'high' :
                candidate.score >= 0.6 ? 'medium' : 'low';

            const candidateCard = document.createElement('div');
            candidateCard.className = 'candidate-card';
            candidateCard.onclick = () => showCandidateDetails(index);

            // Get initials for avatar
            const initials = (driver.first_name ? driver.first_name[0] : '') +
                (driver.last_name ? driver.last_name[0] : '');

            candidateCard.innerHTML = `
                <div class="row">
                    <div class="col-md-2">
                        <div class="position-relative">
                            <div class="candidate-avatar">
                                ${initials || 'U'}
                            </div>
                            <div class="match-badge ${scoreClass}">
                                ${Math.round(candidate.score * 100)}%
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <h6 class="mb-1">${driver.first_name || ''} ${driver.last_name || 'Ανώνυμος'}</h6>
                        <p class="text-muted mb-1">
                            <i class="fas fa-map-marker-alt"></i> ${driver.city || 'Άγνωστη τοποθεσία'}
                            <span class="mx-2">|</span>
                            <i class="fas fa-briefcase"></i> ${driver.years_experience || 0} έτη εμπειρίας
                        </p>
                        <div class="mt-2">
                            ${getLicenseTags(driver).map(license => 
                                `<span class="skill-tag ${isLicenseMatched(license) ? 'matched' : ''}">${license}</span>`
                            ).join('')}
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="mb-2">
                            <small class="text-muted">Διαθεσιμότητα</small><br>
                            <span class="badge badge-${driver.is_available ? 'success' : 'warning'}">
                                ${driver.is_available ? 'Άμεσα Διαθέσιμος' : 'Μη Διαθέσιμος'}
                            </span>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); showCandidateDetails(${index})">
                            <i class="fas fa-eye"></i> Λεπτομέρειες
                        </button>
                    </div>
                </div>
            `;

            resultsEl.appendChild(candidateCard);
        });
    }

    function getLicenseTags(driver) {
        // This should extract licenses from driver data
        // For now, return mock data
        return ['C', 'CE', 'ADR'];
    }

    function isLicenseMatched(license) {
        // Check if license matches job requirements
        return true; // Mock implementation
    }

    function showCandidateDetails(index) {
        const candidate = currentCandidates[index];
        const modal = $('#candidateDetailsModal');

        displayCandidateDetails(candidate);
        modal.modal('show');
    }

    function displayCandidateDetails(candidate) {
        const contentEl = document.getElementById('candidate-details-content');
        const driver = candidate.driver;

        contentEl.innerHTML = `
            <div class="row">
                <div class="col-md-8">
                    <div class="d-flex align-items-center mb-3">
                        <div class="candidate-avatar mr-3" style="width: 80px; height: 80px; font-size: 32px;">
                            ${(driver.first_name ? driver.first_name[0] : '') + (driver.last_name ? driver.last_name[0] : '')}
                        </div>
                        <div>
                            <h4 class="mb-1">${driver.first_name || ''} ${driver.last_name || 'Ανώνυμος'}</h4>
                            <p class="text-muted mb-0">
                                <i class="fas fa-map-marker-alt"></i> ${driver.city || 'Άγνωστη τοποθεσία'}
                                <span class="mx-2">|</span>
                                <i class="fas fa-phone"></i> ${driver.phone || 'Μη διαθέσιμο'}
                            </p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6>Εμπειρία & Δεξιότητες</h6>
                        <div class="experience-bar">
                            <div class="experience-fill" style="width: ${Math.min(driver.years_experience * 10, 100)}%"></div>
                        </div>
                        <small class="text-muted">${driver.years_experience || 0} έτη εμπειρίας</small>
                        
                        <div class="mt-3">
                            <strong>Διπλώματα:</strong><br>
                            ${getLicenseTags(driver).map(license => 
                                `<span class="skill-tag matched">${license}</span>`
                            ).join('')}
                        </div>
                    </div>

                    <div class="candidate-stats">
                        <div class="stat-item">
                            <div class="stat-value">${driver.total_jobs || 0}</div>
                            <div class="stat-label">Θέσεις Εργασίας</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${driver.avg_rating || 0}/5</div>
                            <div class="stat-label">Μέση Βαθμολογία</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${driver.certifications_count || 0}</div>
                            <div class="stat-label">Πιστοποιητικά</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="text-center mb-3">
                        <div class="match-score ${candidate.score >= 0.8 ? 'high' : candidate.score >= 0.6 ? 'medium' : 'low'}" style="font-size: 48px;">
                            ${Math.round(candidate.score * 100)}%
                        </div>
                        <p class="text-muted">Συμβατότητα με τη θέση</p>
                    </div>

                    <div class="score-details">
                        ${Object.entries(candidate.details).map(([key, value]) => `
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>${getScoreLabel(key)}:</span>
                                <span class="badge badge-${value >= 0.8 ? 'success' : value >= 0.6 ? 'warning' : 'danger'}">
                                    ${Math.round(value * 100)}%
                                </span>
                            </div>
                        `).join('')}
                    </div>

                    <div class="mt-4">
                        <h6>Στοιχεία Επικοινωνίας</h6>
                        <p class="small">
                            <i class="fas fa-envelope"></i> ${driver.email || 'Μη διαθέσιμο'}<br>
                            <i class="fas fa-phone"></i> ${driver.phone || 'Μη διαθέσιμο'}
                        </p>
                    </div>
                </div>
            </div>
        `;

        // Set button actions
        document.getElementById('contact-driver-btn').onclick = () => contactDriver(driver.id);
        document.getElementById('invite-driver-btn').onclick = () => inviteDriver(driver.id);
    }

    function getScoreLabel(key) {
        const labels = {
            'skill_match': 'Δεξιότητες',
            'location_match': 'Τοποθεσία',
            'experience_match': 'Εμπειρία',
            'availability_match': 'Διαθεσιμότητα'
        };
        return labels[key] || key;
    }

    function contactDriver(driverId) {
        // Implement contact functionality
        window.location.href = `/companies/contact-driver/${driverId}`;
    }

    function inviteDriver(driverId) {
        // Implement invite functionality
        const jobId = document.getElementById('job-select').value;
        window.location.href = `/companies/invite-driver/${driverId}?job_id=${jobId}`;
    }
</script>