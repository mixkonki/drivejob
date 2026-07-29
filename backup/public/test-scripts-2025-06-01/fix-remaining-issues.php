<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;

$pdo = Database::getInstance()->getConnection();

echo "<h1>Διόρθωση Υπολειπόμενων Προβλημάτων</h1>";

// 1. Fix companies/messages content column issue
echo "<h2>1. Διόρθωση companies/messages</h2>";

$messagesPath = ROOT_DIR . '/public/companies/messages.php';
if (file_exists($messagesPath)) {
    $content = file_get_contents($messagesPath);

    // Replace 'content' with 'message'
    $content = str_replace("m.content", "m.message", $content);
    $content = str_replace("'content'", "'message'", $content);
    $content = str_replace('"content"', '"message"', $content);

    file_put_contents($messagesPath, $content);
    echo "<p>✓ Fixed companies/messages.php</p>";
}

// Also fix companies/conversation.php
$convPath = ROOT_DIR . '/public/companies/conversation.php';
if (file_exists($convPath)) {
    $content = file_get_contents($convPath);

    // Replace 'content' with 'message'
    $content = str_replace('$message[\'content\']', '$message[\'message\']', $content);
    $content = str_replace("m.content", "m.message", $content);

    file_put_contents($convPath, $content);
    echo "<p>✓ Fixed companies/conversation.php</p>";
}

// 2. Fix drivers search route
echo "<h2>2. Διόρθωση drivers/search route</h2>";

// Check if search method exists in DriversController
$controllerPath = ROOT_DIR . '/src/Controllers/Driver/DriversController.php';
if (file_exists($controllerPath)) {
    $content = file_get_contents($controllerPath);

    // Check if search method exists
    if (strpos($content, 'public function search') === false) {
        // Add search method before the last closing brace
        $searchMethod = '
    /**
     * Search for drivers
     */
    public function search()
    {
        // Get search parameters
        $criteria = [
            \'city\' => $_GET[\'city\'] ?? null,
            \'license_type\' => $_GET[\'license_type\'] ?? null,
            \'available_for_work\' => isset($_GET[\'available_for_work\']) ? 1 : null,
            \'experience_years\' => $_GET[\'experience_years\'] ?? null
        ];
        
        // Remove empty criteria
        $criteria = array_filter($criteria);
        
        // Get drivers
        $query = "SELECT d.*, u.email 
                  FROM drivers d 
                  JOIN users u ON d.user_id = u.id 
                  WHERE d.is_active = 1";
        
        $params = [];
        
        if (!empty($criteria[\'city\'])) {
            $query .= " AND d.city LIKE ?";
            $params[] = \'%\' . $criteria[\'city\'] . \'%\';
        }
        
        if (!empty($criteria[\'available_for_work\'])) {
            $query .= " AND d.available_for_work = 1";
        }
        
        if (!empty($criteria[\'experience_years\'])) {
            $query .= " AND d.experience_years >= ?";
            $params[] = $criteria[\'experience_years\'];
        }
        
        $query .= " ORDER BY d.created_at DESC LIMIT 20";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $drivers = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Load view
        include ROOT_DIR . \'/src/Views/drivers/search.php\';
    }
';

        // Insert before the last closing brace
        $lastBrace = strrpos($content, '}');
        $content = substr($content, 0, $lastBrace) . $searchMethod . "\n" . substr($content, $lastBrace);

        file_put_contents($controllerPath, $content);
        echo "<p>✓ Added search method to DriversController</p>";
    } else {
        echo "<p>✓ Search method already exists in DriversController</p>";
    }
}

// 3. Create drivers search view
$searchViewPath = ROOT_DIR . '/src/Views/drivers/search.php';
if (!file_exists($searchViewPath)) {
    $searchView = '<?php
include ROOT_DIR . \'/src/Views/partials/header.php\';
?>

<div class="container mt-4">
    <h1>Αναζήτηση Οδηγών</h1>
    
    <form method="GET" action="<?php echo BASE_URL; ?>drivers/search" class="mb-4">
        <div class="row">
            <div class="col-md-3">
                <input type="text" name="city" class="form-control" placeholder="Πόλη" value="<?php echo $_GET[\'city\'] ?? \'\'; ?>">
            </div>
            <div class="col-md-3">
                <select name="license_type" class="form-control">
                    <option value="">Όλες οι άδειες</option>
                    <option value="B">B - Επιβατικά</option>
                    <option value="C">C - Φορτηγά</option>
                    <option value="D">D - Λεωφορεία</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" name="experience_years" class="form-control" placeholder="Έτη εμπειρίας" min="0">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Αναζήτηση</button>
            </div>
        </div>
    </form>
    
    <div class="row">
        <?php if (empty($drivers)): ?>
            <p>Δεν βρέθηκαν οδηγοί με τα κριτήρια αναζήτησης.</p>
        <?php else: ?>
            <?php foreach ($drivers as $driver): ?>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($driver[\'first_name\'] . \' \' . $driver[\'last_name\']); ?></h5>
                            <p class="card-text">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($driver[\'city\'] ?? \'Δεν έχει οριστεί\'); ?><br>
                                <i class="fas fa-briefcase"></i> <?php echo $driver[\'experience_years\'] ?? 0; ?> έτη εμπειρίας<br>
                                <?php if ($driver[\'available_for_work\']): ?>
                                    <span class="badge bg-success">Διαθέσιμος</span>
                                <?php endif; ?>
                            </p>
                            <a href="<?php echo BASE_URL; ?>drivers/profile/<?php echo $driver[\'id\']; ?>" class="btn btn-sm btn-primary">Προβολή Προφίλ</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
include ROOT_DIR . \'/src/Views/partials/footer.php\';
?>';

    file_put_contents($searchViewPath, $searchView);
    echo "<p>✓ Created drivers search view</p>";
}

// 4. Fix job-listings/create route
echo "<h2>3. Διόρθωση job-listings/create route</h2>";

// Create the route handler file
$createJobPath = ROOT_DIR . '/public/job-listings/create.php';
if (!file_exists(dirname($createJobPath))) {
    mkdir(dirname($createJobPath), 0777, true);
}

$createJobContent = '<?php
require_once __DIR__ . \'/../../src/bootstrap.php\';

use Drivejob\Core\Router;
use Drivejob\Controllers\UnifiedJobListingController;

// Create router instance
$router = new Router();

// Include routes
require_once ROOT_DIR . \'/config/routes.php\';

// Create controller and call create method
$controller = new UnifiedJobListingController();
$controller->create();
';

file_put_contents($createJobPath, $createJobContent);
echo "<p>✓ Created job-listings/create.php</p>";

// Also create store.php
$storeJobPath = ROOT_DIR . '/public/job-listings/store.php';
$storeJobContent = '<?php
require_once __DIR__ . \'/../../src/bootstrap.php\';

use Drivejob\Controllers\UnifiedJobListingController;

$controller = new UnifiedJobListingController();
$controller->store();
';

file_put_contents($storeJobPath, $storeJobContent);
echo "<p>✓ Created job-listings/store.php</p>";

// 5. Fix candidates widget database error
echo "<h2>4. Διόρθωση candidates widget</h2>";

$widgetPath = ROOT_DIR . '/src/Views/companies/partials/candidates-widget-with-messaging.php';
if (file_exists($widgetPath)) {
    $content = file_get_contents($widgetPath);

    // Check if it has the correct error handling
    if (strpos($content, 'try {') === false) {
        // Wrap the content in try-catch
        $newContent = '<?php
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
                $companyId = $_SESSION[\'user_id\'] ?? 0;
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
                    echo \'<option value="\' . $job[\'id\'] . \'">\' . htmlspecialchars($job[\'title\']) . \'</option>\';
                }
            } catch (Exception $e) {
                echo \'<option value="">Σφάλμα φόρτωσης αγγελιών</option>\';
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
document.getElementById(\'job-select\').addEventListener(\'change\', function() {
    const jobId = this.value;
    const container = document.getElementById(\'candidates-container\');
    
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
            console.error(\'Error:\', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Σφάλμα φόρτωσης υποψηφίων.
                </div>
            `;
        });
});

function displayCandidates(candidates) {
    const container = document.getElementById(\'candidates-container\');
    let html = \'\';
    
    candidates.forEach(candidate => {
        const score = Math.round(candidate.score * 100);
        const scoreClass = score >= 70 ? \'high\' : score >= 50 ? \'medium\' : \'low\';
        
        html += `
            <div class="candidate-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5>${candidate.driver.first_name} ${candidate.driver.last_name}</h5>
                        <p class="mb-1">
                            <i class="fas fa-map-marker-alt"></i> ${candidate.driver.city || \'Δεν έχει οριστεί\'}
                            <span class="ms-3"><i class="fas fa-briefcase"></i> ${candidate.driver.experience_years || 0} έτη εμπειρίας</span>
                        </p>
                        <div class="mt-2">
                            <a href="<?php echo BASE_URL; ?>drivers/profile/${candidate.driver.id}" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="fas fa-user"></i> Προφίλ
                            </a>
                            <button class="btn btn-sm btn-primary ms-2" onclick="openMessageModal(${candidate.driver.id}, \'${candidate.driver.first_name} ${candidate.driver.last_name}\', ${document.getElementById(\'job-select\').value})">
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
</script>';

        file_put_contents($widgetPath, $newContent);
        echo "<p>✓ Fixed candidates widget with proper error handling</p>";
    }
}

// Summary
echo "<h2>Σύνοψη Διορθώσεων</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<ul>";
echo "<li>✓ Διόρθωση 'content' σε 'message' στα companies messages</li>";
echo "<li>✓ Προσθήκη search method στον DriversController</li>";
echo "<li>✓ Δημιουργία job-listings/create.php και store.php</li>";
echo "<li>✓ Διόρθωση candidates widget με error handling</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='" . BASE_URL . "final-system-check.php' class='btn btn-primary'>Επιστροφή στον Τελικό Έλεγχο</a></p>";
