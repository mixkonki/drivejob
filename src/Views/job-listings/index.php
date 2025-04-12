<?php
// Συμπερίληψη του config.php για να οριστούν οι σταθερές
require_once __DIR__ . '/../../../config/config.php';

// Συμπερίληψη του database.php για σύνδεση με τη βάση δεδομένων
require_once ROOT_DIR . '/config/database.php';

// Ξεκίνημα ή συνέχιση session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Δημιουργία του controller και κλήση της μεθόδου index
// Δε χρειάζεται να το κάνουμε εδώ, γίνεται ήδη στο Router
// $controller = new \Drivejob\Controllers\JobListingController($pdo);
// $controller->index();

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php'; 

// Ορισμός των επιπλέον CSS αρχείων
$css_files = ['css/job-listings.css'];

// Ανάκτηση σφαλμάτων και παλιών τιμών από το session
use Drivejob\Core\Session;

$errors = Session::get('errors', []);
$oldInput = Session::get('old_input', []);
Session::remove('errors');
Session::remove('old_input');

// Βοηθητική συνάρτηση για την εμφάνιση των παλιών τιμών
function old($field, $default = '') {
    global $oldInput;
    return $oldInput[$field] ?? $default;
}

// Βοηθητική συνάρτηση για την εμφάνιση των σφαλμάτων
function hasError($field) {
    global $errors;
    return isset($errors[$field]);
}

function getError($field) {
    global $errors;
    return $errors[$field] ?? '';
}
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/job-listings.css">
<main>
    <div class="container">
        <h1>Αγγελίες Εργασίας</h1>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message">
                <?php echo $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Φίλτρα αναζήτησης -->
        <div class="search-filters">
            <form action="" method="GET">
                <div class="filter-group">
                    <label for="listing_type">Τύπος Αγγελίας</label>
                    <select id="listing_type" name="listing_type">
                        <option value="">Όλοι οι τύποι</option>
                        <option value="job_offer" <?php echo isset($_GET['listing_type']) && $_GET['listing_type'] === 'job_offer' ? 'selected' : ''; ?>>Προσφορά Εργασίας</option>
                        <option value="job_search" <?php echo isset($_GET['listing_type']) && $_GET['listing_type'] === 'job_search' ? 'selected' : ''; ?>>Αναζήτηση Εργασίας</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="job_type">Τύπος Απασχόλησης</label>
                    <select id="job_type" name="job_type">
                        <option value="">Όλοι οι τύποι</option>
                        <option value="full_time" <?php echo isset($_GET['job_type']) && $_GET['job_type'] === 'full_time' ? 'selected' : ''; ?>>Πλήρης Απασχόληση</option>
                        <option value="part_time" <?php echo isset($_GET['job_type']) && $_GET['job_type'] === 'part_time' ? 'selected' : ''; ?>>Μερική Απασχόληση</option>
                        <option value="contract" <?php echo isset($_GET['job_type']) && $_GET['job_type'] === 'contract' ? 'selected' : ''; ?>>Σύμβαση Έργου</option>
                        <option value="temporary" <?php echo isset($_GET['job_type']) && $_GET['job_type'] === 'temporary' ? 'selected' : ''; ?>>Προσωρινή Απασχόληση</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="vehicle_type">Τύπος Οχήματος</label>
                    <select id="vehicle_type" name="vehicle_type">
                        <option value="">Όλοι οι τύποι</option>
                        <option value="car" <?php echo isset($_GET['vehicle_type']) && $_GET['vehicle_type'] === 'car' ? 'selected' : ''; ?>>Αυτοκίνητο</option>
                        <option value="van" <?php echo isset($_GET['vehicle_type']) && $_GET['vehicle_type'] === 'van' ? 'selected' : ''; ?>>Βαν</option>
                        <option value="truck" <?php echo isset($_GET['vehicle_type']) && $_GET['vehicle_type'] === 'truck' ? 'selected' : ''; ?>>Φορτηγό</option>
                        <option value="bus" <?php echo isset($_GET['vehicle_type']) && $_GET['vehicle_type'] === 'bus' ? 'selected' : ''; ?>>Λεωφορείο</option>
                        <option value="machinery" <?php echo isset($_GET['vehicle_type']) && $_GET['vehicle_type'] === 'machinery' ? 'selected' : ''; ?>>Μηχάνημα Έργου</option>
                    </select>
                </div>
                
                <div class="filter-group checkbox-group">
                    <label>
                        <input type="checkbox" name="adr_certificate" value="1" <?php echo isset($_GET['adr_certificate']) ? 'checked' : ''; ?>>
                        ADR Πιστοποίηση
                    </label>
                </div>
                
                <div class="filter-group checkbox-group">
                    <label>
                        <input type="checkbox" name="operator_license" value="1" <?php echo isset($_GET['operator_license']) ? 'checked' : ''; ?>>
                        Άδεια Χειριστή
                    </label>
                </div>
                
                <div class="filter-group">
                    <label for="location">Τοποθεσία</label>
                    <input type="text" id="location" name="location" value="<?php echo isset($_GET['location']) ? htmlspecialchars($_GET['location']) : ''; ?>" placeholder="Πόλη ή Περιοχή">
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn-primary">Αναζήτηση</button>
                    <a href="<?php echo BASE_URL; ?>job-listings" class="btn-secondary">Καθαρισμός</a>
                </div>
            </form>
        </div>
        
        <!-- Επικεφαλίδα αγγελιών -->
        <div class="job-listings-header">
            <h2>Αποτελέσματα Αναζήτησης</h2>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn-primary">Νέα Αγγελία</a>
            <?php endif; ?>
        </div>
        
        <!-- Λίστα Αγγελιών -->
        <?php if (isset($listings) && count($listings['results']) > 0): ?>
            <div class="job-listings">
                <?php foreach ($listings['results'] as $listing): ?>
                    <div class="job-listing-card">
                        <div class="job-listing-header">
                            <h3><a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h3>
                            <div>
                                <span class="job-type <?php echo $listing['job_type']; ?>">
                                    <?php 
                                    switch ($listing['job_type']) {
                                        case 'full_time': echo 'Πλήρης Απασχόληση'; break;
                                        case 'part_time': echo 'Μερική Απασχόληση'; break;
                                        case 'contract': echo 'Σύμβαση Έργου'; break;
                                        case 'temporary': echo 'Προσωρινή Απασχόληση'; break;
                                    }
                                    ?>
                                </span>
                                <span class="listing-type <?php echo $listing['listing_type']; ?>">
                                    <?php echo $listing['listing_type'] === 'job_offer' ? 'Προσφορά Εργασίας' : 'Αναζήτηση Εργασίας'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="job-listing-details">
                            <div class="job-listing-detail">
                                <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                                <span><?php echo htmlspecialchars($listing['location']); ?></span>
                            </div>
                            
                            <div class="job-listing-detail">
                                <img src="<?php echo BASE_URL; ?>img/vehicle_icon.png" alt="Όχημα">
                                <span>
                                    <?php 
                                    switch ($listing['vehicle_type']) {
                                        case 'car': echo 'Αυτοκίνητο'; break;
                                        case 'van': echo 'Βαν'; break;
                                        case 'truck': echo 'Φορτηγό'; break;
                                        case 'bus': echo 'Λεωφορείο'; break;
                                        case 'machinery': echo 'Μηχάνημα Έργου'; break;
                                    }
                                    ?>
                                </span>
                            </div>
                            
                            <?php if ($listing['salary_min'] || $listing['salary_max']): ?>
                                <div class="job-listing-detail">
                                    <img src="<?php echo BASE_URL; ?>img/salary_icon.png" alt="Αμοιβή">
                                    <span>
                                        <?php 
                                        if ($listing['salary_min'] && $listing['salary_max']) {
                                            echo number_format($listing['salary_min']) . '€ - ' . number_format($listing['salary_max']) . '€';
                                        } elseif ($listing['salary_min']) {
                                            echo 'Από ' . number_format($listing['salary_min']) . '€';
                                        } elseif ($listing['salary_max']) {
                                            echo 'Έως ' . number_format($listing['salary_max']) . '€';
                                        }
                                        ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="job-listing-description">
                            <?php echo nl2br(htmlspecialchars(substr($listing['description'], 0, 150) . (strlen($listing['description']) > 150 ? '...' : ''))); ?>
                        </div>
                        
                        <div class="job-listing-footer">
                            <span class="job-listing-date">Δημοσιεύτηκε: <?php echo date('d/m/Y', strtotime($listing['created_at'])); ?></span>
                            <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>" class="btn-primary">Περισσότερα</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Σελιδοποίηση -->
            <?php if ($listings['pagination']['pages'] > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $listings['pagination']['pages']; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo isset($_GET['listing_type']) ? '&listing_type=' . htmlspecialchars($_GET['listing_type']) : ''; ?><?php echo isset($_GET['job_type']) ? '&job_type=' . htmlspecialchars($_GET['job_type']) : ''; ?><?php echo isset($_GET['vehicle_type']) ? '&vehicle_type=' . htmlspecialchars($_GET['vehicle_type']) : ''; ?>" class="pagination-btn <?php echo $i === $listings['pagination']['page'] ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-results">
                <p>Δεν βρέθηκαν αγγελίες που να ταιριάζουν με τα κριτήρια αναζήτησης.</p>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn-primary">Δημιουργήστε μια νέα αγγελία</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCgZpJWVYyrY0U8U1jBGelEWryur3vIrzc&libraries=places"></script>
<script>
    // Αυτόματη συμπλήρωση τοποθεσίας
    function initAutocomplete() {
        const locationInput = document.getElementById('location');
        if (locationInput) {
            const autocomplete = new google.maps.places.Autocomplete(locationInput, {
                types: ['(cities)']
            });
        }
    }
    
    // Φόρτωση του Google Places API
    document.addEventListener('DOMContentLoaded', initAutocomplete);
</script>

<?php 
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php'; 
?>