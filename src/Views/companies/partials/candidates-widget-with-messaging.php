<?php
// AI Candidates Widget with Messaging for Company Dashboard
?>

<div class="candidates-widget">
    <div class="widget-header">
        <h3><i class="fas fa-users"></i> Προτεινόμενοι Υποψήφιοι</h3>
        <p class="text-muted">Βρείτε τους καλύτερους οδηγούς με AI matching</p>
    </div>

    <div class="job-selector mb-3">
        <label for="job-select" class="form-label">Επιλέξτε αγγελία:</label>
        <select id="job-select" class="form-select">
            <option value="">-- Επιλέξτε αγγελία --</option>
            <?php
            try {
                // Get company jobs
                $companyId = $_SESSION['user_id'] ?? 0;
                $pdo = \Drivejob\Core\Database::getInstance()->getConnection();
                $stmt = $pdo->prepare("
                    SELECT id, title 
                    FROM job_listings 
                    WHERE company_id = ? AND is_active = 1 
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$companyId]);
                $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($jobs as $job) {
                    echo '<option value="' . $job['id'] . '">' . htmlspecialchars($job['title']) . '</option>';
                }
            } catch (Exception $e) {
                echo '<option value="">Σφάλμα φόρτωσης αγγελιών</option>';
            }
            ?>
        </select>
    </div>

    <div id="candidates-container">
        <div class="text-center text-muted">
            <i class="fas fa-arrow-up fa-2x mb-2"></i>
            <p>Επιλέξτε μια αγγελία για να δείτε τους προτεινόμενους υποψήφιους</p>
        </div>
    </div>
</div>

<style>
.candidates-widget {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
}

.candidate-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.candidate-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.match-score {
    font-size: 24px;
    font-weight: bold;
}

.match-score.high { color: #28a745; }
.match-score.medium { color: #ffc107; }
.match-score.low { color: #dc3545; }
</style>

<script>
document.getElementById('job-select').addEventListener('change', function() {
    const jobId = this.value;
    const container = document.getElementById('candidates-container');
    
    if (!jobId) {
        container.innerHTML = `
            <div class="text-center text-muted">
                <i class="fas fa-arrow-up fa-2x mb-2"></i>
                <p>Επιλέξτε μια αγγελία για να δείτε τους προτεινόμενους υποψήφιους</p>
            </div>
        `;
        return;
    }
    
    // Show loading
    container.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Φόρτωση...</span>
            </div>
            <p class="mt-2">Αναζήτηση υποψηφίων...</p>
        </div>
    `;
    
    // Fetch candidates
    fetch(`<?php echo BASE_URL; ?>api/matching/job/candidates?job_id=${jobId}&limit=5`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.candidates.length > 0) {
                displayCandidates(data.data.candidates);
            } else {
                container.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Δεν βρέθηκαν υποψήφιοι για αυτή την αγγελία.
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Σφάλμα φόρτωσης υποψηφίων.
                </div>
            `;
        });
});

function displayCandidates(candidates) {
    const container = document.getElementById('candidates-container');
    let html = '';
    
    candidates.forEach(candidate => {
        const score = Math.round(candidate.score * 100);
        const scoreClass = score >= 70 ? 'high' : score >= 50 ? 'medium' : 'low';
        
        html += `
            <div class="candidate-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5>${candidate.driver.first_name} ${candidate.driver.last_name}</h5>
                        <p class="mb-1">
                            <i class="fas fa-map-marker-alt"></i> ${candidate.driver.city || 'Δεν έχει οριστεί'}
                            <span class="ms-3"><i class="fas fa-briefcase"></i> ${candidate.driver.experience_years || 0} έτη εμπειρίας</span>
                        </p>
                        <div class="mt-2">
                            <a href="<?php echo BASE_URL; ?>drivers/profile/${candidate.driver.id}" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="fas fa-user"></i> Προφίλ
                            </a>
                            <button class="btn btn-sm btn-primary ms-2" onclick="openMessageModal(${candidate.driver.id}, '${candidate.driver.first_name} ${candidate.driver.last_name}', ${document.getElementById('job-select').value})">
                                <i class="fas fa-envelope"></i> Μήνυμα
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="match-score ${scoreClass}">${score}%</div>
                        <small class="text-muted">Ταίριασμα</small>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Message modal functionality
function openMessageModal(driverId, driverName, jobId) {
    // Implementation for opening message modal
    alert(`Αποστολή μηνύματος σε ${driverName}`);
}
</script>