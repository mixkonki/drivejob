<?php
require_once __DIR__ . '/../partials/header.php';

// Check if user is logged in as driver
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'driver') {
    header('Location: /login');
    exit;
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-robot"></i> Προτεινόμενες Θέσεις Εργασίας</h2>
                <div>
                    <button class="btn btn-primary" onclick="refreshMatches()">
                        <i class="fas fa-sync"></i> Ανανέωση
                    </button>
                    <a href="<?php echo BASE_URL; ?>drivers/profile" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Πίσω στο Προφίλ
                    </a>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter"></i> Φίλτρα Αναζήτησης
                        <button class="btn btn-sm btn-link float-right" onclick="toggleFilters()">
                            <i class="fas fa-chevron-down" id="filter-toggle-icon"></i>
                        </button>
                    </h5>
                </div>
                <div class="card-body" id="filters-section" style="display: none;">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Ελάχιστη Συμβατότητα</label>
                                <input type="range" class="custom-range" id="min-score" min="0" max="100" value="0">
                                <small class="form-text text-muted">
                                    <span id="min-score-value">0</span>%
                                </small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Τοποθεσία</label>
                                <select class="form-control" id="location-filter">
                                    <option value="">Όλες οι τοποθεσίες</option>
                                    <option value="athens">Αθήνα</option>
                                    <option value="thessaloniki">Θεσσαλονίκη</option>
                                    <option value="patra">Πάτρα</option>
                                    <option value="heraklion">Ηράκλειο</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Τύπος Απασχόλησης</label>
                                <select class="form-control" id="employment-filter">
                                    <option value="">Όλοι οι τύποι</option>
                                    <option value="full_time">Πλήρης Απασχόληση</option>
                                    <option value="part_time">Μερική Απασχόληση</option>
                                    <option value="flexible">Ευέλικτο Ωράριο</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Ταξινόμηση</label>
                                <select class="form-control" id="sort-filter">
                                    <option value="score_desc">Συμβατότητα (Υψηλή → Χαμηλή)</option>
                                    <option value="score_asc">Συμβατότητα (Χαμηλή → Υψηλή)</option>
                                    <option value="date_desc">Ημερομηνία (Νέες πρώτα)</option>
                                    <option value="salary_desc">Μισθός (Υψηλός → Χαμηλός)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <button class="btn btn-secondary btn-sm" onclick="resetFilters()">
                            <i class="fas fa-undo"></i> Επαναφορά
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="applyFilters()">
                            <i class="fas fa-check"></i> Εφαρμογή
                        </button>
                    </div>
                </div>
            </div>

            <!-- Results Section -->
            <div id="matches-container">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Φόρτωση...</span>
                    </div>
                    <p class="mt-2">Αναζήτηση προτεινόμενων θέσεων...</p>
                </div>
            </div>

            <!-- Pagination -->
            <nav id="pagination-container" style="display: none;">
                <ul class="pagination justify-content-center">
                    <!-- Pagination will be populated here -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<style>
    .job-match-card {
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        transition: all 0.3s ease;
        background: white;
    }

    .job-match-card:hover {
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .match-score-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        font-weight: bold;
        color: white;
    }

    .match-score-badge.high {
        background: linear-gradient(135deg, #28a745, #20c997);
    }

    .match-score-badge.medium {
        background: linear-gradient(135deg, #ffc107, #fd7e14);
    }

    .match-score-badge.low {
        background: linear-gradient(135deg, #dc3545, #e83e8c);
    }

    .match-score-badge .score {
        font-size: 28px;
    }

    .match-score-badge .label {
        font-size: 11px;
        opacity: 0.9;
    }

    .score-factors {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #e0e0e0;
    }

    .score-factor {
        text-align: center;
    }

    .score-factor-label {
        font-size: 12px;
        color: #666;
        margin-bottom: 5px;
    }

    .score-factor-bar {
        height: 6px;
        background: #e0e0e0;
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 5px;
    }

    .score-factor-fill {
        height: 100%;
        transition: width 0.3s ease;
    }

    .score-factor-fill.high {
        background: #28a745;
    }

    .score-factor-fill.medium {
        background: #ffc107;
    }

    .score-factor-fill.low {
        background: #dc3545;
    }

    .job-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 15px;
    }

    .job-tag {
        display: inline-block;
        padding: 5px 12px;
        background: #f0f0f0;
        border-radius: 15px;
        font-size: 13px;
        color: #666;
    }

    .job-tag.primary {
        background: #007bff;
        color: white;
    }

    .insights-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
    }

    .insight-item {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .insight-item:last-child {
        margin-bottom: 0;
    }

    .insight-item i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }

    .insight-success {
        color: #28a745;
    }

    .insight-warning {
        color: #ffc107;
    }

    .insight-info {
        color: #17a2b8;
    }
</style>

<script>
    let allMatches = [];
    let filteredMatches = [];
    let currentPage = 1;
    const matchesPerPage = 10;

    // Load matches on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadAllMatches();
        setupEventListeners();
    });

    function setupEventListeners() {
        // Range slider
        const minScoreSlider = document.getElementById('min-score');
        const minScoreValue = document.getElementById('min-score-value');

        minScoreSlider.addEventListener('input', function() {
            minScoreValue.textContent = this.value;
        });
    }

    function toggleFilters() {
        const filtersSection = document.getElementById('filters-section');
        const toggleIcon = document.getElementById('filter-toggle-icon');

        if (filtersSection.style.display === 'none') {
            filtersSection.style.display = 'block';
            toggleIcon.className = 'fas fa-chevron-up';
        } else {
            filtersSection.style.display = 'none';
            toggleIcon.className = 'fas fa-chevron-down';
        }
    }

    function loadAllMatches() {
        fetch('<?php echo BASE_URL; ?>api/matching/driver/matches?limit=100')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    allMatches = data.data.matches;
                    filteredMatches = [...allMatches];
                    displayMatches();
                } else {
                    showError('Σφάλμα κατά τη φόρτωση των προτάσεων.');
                }
            })
            .catch(error => {
                console.error('Error loading matches:', error);
                showError('Σφάλμα σύνδεσης. Παρακαλώ δοκιμάστε ξανά.');
            });
    }

    function applyFilters() {
        const minScore = parseInt(document.getElementById('min-score').value) / 100;
        const location = document.getElementById('location-filter').value;
        const employment = document.getElementById('employment-filter').value;
        const sortBy = document.getElementById('sort-filter').value;

        // Filter matches
        filteredMatches = allMatches.filter(match => {
            if (match.score < minScore) return false;
            if (location && !match.job.location?.toLowerCase().includes(location)) return false;
            if (employment && match.job.employment_type !== employment) return false;
            return true;
        });

        // Sort matches
        switch (sortBy) {
            case 'score_asc':
                filteredMatches.sort((a, b) => a.score - b.score);
                break;
            case 'score_desc':
                filteredMatches.sort((a, b) => b.score - a.score);
                break;
            case 'date_desc':
                filteredMatches.sort((a, b) => new Date(b.job.created_at) - new Date(a.job.created_at));
                break;
            case 'salary_desc':
                filteredMatches.sort((a, b) => (b.job.salary_max || 0) - (a.job.salary_max || 0));
                break;
        }

        currentPage = 1;
        displayMatches();
    }

    function resetFilters() {
        document.getElementById('min-score').value = 0;
        document.getElementById('min-score-value').textContent = '0';
        document.getElementById('location-filter').value = '';
        document.getElementById('employment-filter').value = '';
        document.getElementById('sort-filter').value = 'score_desc';

        filteredMatches = [...allMatches];
        currentPage = 1;
        displayMatches();
    }

    function displayMatches() {
        const container = document.getElementById('matches-container');
        const startIndex = (currentPage - 1) * matchesPerPage;
        const endIndex = startIndex + matchesPerPage;
        const matchesToDisplay = filteredMatches.slice(startIndex, endIndex);

        if (matchesToDisplay.length === 0) {
            container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <p class="text-muted">Δεν βρέθηκαν προτεινόμενες θέσεις με τα κριτήρια που επιλέξατε.</p>
                <button class="btn btn-primary" onclick="resetFilters()">
                    <i class="fas fa-undo"></i> Καθαρισμός Φίλτρων
                </button>
            </div>
        `;
            document.getElementById('pagination-container').style.display = 'none';
            return;
        }

        container.innerHTML = matchesToDisplay.map(match => createMatchCard(match)).join('');

        // Update pagination
        updatePagination();
    }

    function createMatchCard(match) {
        const scoreClass = match.score >= 0.8 ? 'high' : match.score >= 0.6 ? 'medium' : 'low';

        return `
        <div class="job-match-card position-relative">
            <div class="match-score-badge ${scoreClass}">
                <div class="score">${Math.round(match.score * 100)}%</div>
                <div class="label">MATCH</div>
            </div>
            
            <div class="row">
                <div class="col-md-8">
                    <h4 class="mb-2">${match.job.title}</h4>
                    <p class="text-muted mb-3">
                        <i class="fas fa-building"></i> ${match.job.company_name || 'Εταιρεία'}
                        <span class="mx-2">|</span>
                        <i class="fas fa-map-marker-alt"></i> ${match.job.location || match.job.company_city || 'Τοποθεσία'}
                        <span class="mx-2">|</span>
                        <i class="fas fa-calendar"></i> ${formatDate(match.job.created_at)}
                    </p>
                    
                    <p class="mb-3">${match.job.description ? truncateText(match.job.description, 200) : 'Δεν υπάρχει διαθέσιμη περιγραφή.'}</p>
                    
                    <div class="job-tags">
                        ${match.job.required_license ? `<span class="job-tag primary"><i class="fas fa-id-card"></i> ${match.job.required_license}</span>` : ''}
                        ${match.job.employment_type ? `<span class="job-tag">${getEmploymentTypeLabel(match.job.employment_type)}</span>` : ''}
                        ${match.job.salary_min ? `<span class="job-tag"><i class="fas fa-euro-sign"></i> €${match.job.salary_min}-${match.job.salary_max}</span>` : ''}
                        ${match.job.is_urgent ? `<span class="job-tag" style="background: #dc3545; color: white;"><i class="fas fa-bolt"></i> Επείγον</span>` : ''}
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="score-factors">
                        ${Object.entries(match.details).map(([key, value]) => createScoreFactor(key, value)).join('')}
                    </div>
                    
                    <div class="text-center mt-4">
                        <button class="btn btn-primary btn-block" onclick="viewJobDetails(${match.job.id})">
                            <i class="fas fa-eye"></i> Λεπτομέρειες
                        </button>
                        <button class="btn btn-success btn-block mt-2" onclick="applyForJob(${match.job.id})">
                            <i class="fas fa-paper-plane"></i> Υποβολή Αίτησης
                        </button>
                    </div>
                </div>
            </div>
            
            ${match.insights && match.insights.length > 0 ? createInsightsSection(match.insights) : ''}
        </div>
    `;
    }

    function createScoreFactor(key, value) {
        const labels = {
            'skill_match': 'Δεξιότητες',
            'location_match': 'Τοποθεσία',
            'experience_match': 'Εμπειρία',
            'availability_match': 'Διαθεσιμότητα'
        };

        const fillClass = value >= 0.8 ? 'high' : value >= 0.6 ? 'medium' : 'low';

        return `
        <div class="score-factor">
            <div class="score-factor-label">${labels[key] || key}</div>
            <div class="score-factor-bar">
                <div class="score-factor-fill ${fillClass}" style="width: ${value * 100}%"></div>
            </div>
            <small>${Math.round(value * 100)}%</small>
        </div>
    `;
    }

    function createInsightsSection(insights) {
        return `
        <div class="insights-section">
            <h6 class="mb-3"><i class="fas fa-lightbulb"></i> Insights</h6>
            ${insights.map(insight => `
                <div class="insight-item insight-${insight.type}">
                    <i class="fas fa-${insight.type === 'success' ? 'check-circle' : insight.type === 'warning' ? 'exclamation-circle' : 'info-circle'}"></i>
                    ${insight.message}
                </div>
            `).join('')}
        </div>
    `;
    }

    function updatePagination() {
        const totalPages = Math.ceil(filteredMatches.length / matchesPerPage);
        const paginationContainer = document.getElementById('pagination-container');

        if (totalPages <= 1) {
            paginationContainer.style.display = 'none';
            return;
        }

        paginationContainer.style.display = 'block';
        const pagination = paginationContainer.querySelector('.pagination');

        let paginationHTML = '';

        // Previous button
        paginationHTML += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="goToPage(${currentPage - 1}); return false;">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `;

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                paginationHTML += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="goToPage(${i}); return false;">${i}</a>
                </li>
            `;
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        // Next button
        paginationHTML += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="goToPage(${currentPage + 1}); return false;">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `;

        pagination.innerHTML = paginationHTML;
    }

    function goToPage(page) {
        const totalPages = Math.ceil(filteredMatches.length / matchesPerPage);
        if (page < 1 || page > totalPages) return;

        currentPage = page;
        displayMatches();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    function viewJobDetails(jobId) {
        window.location.href = `/jobs/view/${jobId}`;
    }

    function applyForJob(jobId) {
        window.location.href = `/jobs/apply/${jobId}`;
    }

    function refreshMatches() {
        loadAllMatches();
    }

    function showError(message) {
        const container = document.getElementById('matches-container');
        container.innerHTML = `
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> ${message}
        </div>
    `;
    }

    function truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substr(0, maxLength) + '...';
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays === 0) return 'Σήμερα';
        if (diffDays === 1) return 'Χθες';
        if (diffDays < 7) return `${diffDays} ημέρες πριν`;
        if (diffDays < 30) return `${Math.floor(diffDays / 7)} εβδομάδες πριν`;

        return date.toLocaleDateString('el-GR');
    }

    function getEmploymentTypeLabel(type) {
        const types = {
            'full_time': 'Πλήρης Απασχόληση',
            'part_time': 'Μερική Απασχόληση',
            'flexible': 'Ευέλικτο Ωράριο'
        };
        return types[type] || type;
    }
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>