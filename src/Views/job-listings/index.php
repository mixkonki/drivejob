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
include ROOT_DIR . '/src/Views/partials/header.php';

// Ορισμός των επιπλέον CSS αρχείων
$css_files = ['css/job-listings.css'];

// Ανάκτηση σφαλμάτων και παλιών τιμών από το session
use Drivejob\Core\Session;

$errors = Session::get('errors', []);
$oldInput = Session::get('old_input', []);
Session::remove('errors');
Session::remove('old_input');

?>
<?= \Drivejob\Helpers\Asset::css('css/job-listings.css') ?>
<main>
    <div class="container">
        <h1>Αγγελίες Εργασίας</h1>

        <?php if (isset($_SESSION['error_message'])) : ?>
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
                <?php if (!isset($_SESSION['user_id'])) : ?>
                <a href="<?php echo BASE_URL; ?>auth/login" class="btn-primary">Συνδεθείτε για να δημιουργήσετε αγγελία</a>
                <?php endif; ?>
        </div>

        <!-- Λίστα Αγγελιών -->
        <?php if (isset($listings) && count($listings) > 0) : ?>
            <div class="job-listings">
                <?php foreach ($listings as $listing) : ?>
                    <div class="job-listing-card">
                        <div class="job-listing-header">
                            <h3><a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h3>

                            <?php
                            /*
                             * ΠΟΙΟΣ ΔΗΜΟΣΙΕΥΣΕ ΤΗΝ ΑΓΓΕΛΙΑ.
                             *
                             * Το όνομα έχει ήδη περάσει από τον Visibility: ο ανώνυμος
                             * επισκέπτης παίρνει «Εταιρεία μεταφορών», ο συνδεδεμένος
                             * οδηγός την πραγματική επωνυμία με σύνδεσμο στο προφίλ.
                             * Το `company_identity_hidden` το ορίζει ο sanitiser.
                             */
                            $companyLabel = trim((string) ($listing['company_name'] ?? ''));
                            $identityHidden = !empty($listing['company_identity_hidden']);
                            ?>
                            <?php if ($companyLabel !== '') : ?>
                                <p class="job-listing-company">
                                    <?php if ($identityHidden) : ?>
                                        <span class="job-listing-company-masked"
                                              title="Συνδέσου για να δεις ποια εταιρεία δημοσίευσε την αγγελία">
                                            <?php echo htmlspecialchars($companyLabel, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php elseif (!empty($listing['company_id'])) : ?>
                                        <a href="<?php echo BASE_URL; ?>companies/profile/<?php echo (int) $listing['company_id']; ?>">
                                            <?php echo htmlspecialchars($companyLabel, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    <?php else : ?>
                                        <?php echo htmlspecialchars($companyLabel, ENT_QUOTES, 'UTF-8'); ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>

                            <div>
                                <span class="job-type <?php echo $listing['job_type']; ?>">
                                    <?php
                                    switch ($listing['job_type']) {
                                        case 'full_time':
                                            echo 'Πλήρης Απασχόληση';
                                            break;
                                        case 'part_time':
                                            echo 'Μερική Απασχόληση';
                                            break;
                                        case 'contract':
                                            echo 'Σύμβαση Έργου';
                                            break;
                                        case 'temporary':
                                            echo 'Προσωρινή Απασχόληση';
                                            break;
                                    }
                                    ?>
                                </span>
                                <span class="listing-type <?php echo $listing['listing_type']; ?>">
                                    <?php echo $listing['listing_type'] === 'job_offer' ? 'Προσφορά Εργασίας' : 'Αναζήτηση Εργασίας'; ?>
                                </span>
                            </div>
                        </div>

                        <div class="job-listing-details">
                            <?php
                            /*
                             * ΤΥΠΟΣ ΟΧΗΜΑΤΟΣ — εικονίδιο που αλλάζει ανά τύπο.
                             *
                             * Εδώ υπήρχε ένα σταθερό PNG φορτηγού για ΚΑΘΕ αγγελία, και
                             * δίπλα του ένα switch με πέντε μόνο περιπτώσεις (car, van,
                             * truck, bus, machinery). Η βάση όμως κρατά έντεκα τύπους:
                             * ό,τι δεν ήταν σε αυτές τις πέντε — βυτιοφόρο, ψυγείο,
                             * συρμός, μίνι πούλμαν — έβγαινε ΚΕΝΟ. Λεωφορείο και
                             * νταλίκα ήταν οπτικά ίδια, και ο οδηγός δεν ξεχώριζε με μια
                             * ματιά τι είδους θέση είναι.
                             *
                             * Τώρα η ονοματολογία περνά από τον VehicleTypes (μία πηγή
                             * αλήθειας, με μετάφραση παλιών τιμών) και το εικονίδιο από
                             * το partial vehicle-icon.
                             */
                            $vType = $listing['vehicle_type']
                                ?? (is_array($listing['vehicle_types'] ?? null)
                                    ? ($listing['vehicle_types'][0] ?? '')
                                    : ($listing['vehicle_types'] ?? ''));
                            ?>
                            <div class="job-listing-detail">
                                <?php
                                $vehicleIcon = (string) $vType;
                                $vehicleIconSize = 20;
                                include ROOT_DIR . '/src/Views/partials/vehicle-icon.php';
                                ?>
                                <span><?php echo htmlspecialchars(\Drivejob\Helpers\VehicleTypes::label((string) $vType), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>

                            <?php
                            /*
                             * ΕΔΡΑ ΤΗΣ ΕΤΑΙΡΕΙΑΣ.
                             *
                             * Η κάρτα δεν έδειχνε ΚΑΘΟΛΟΥ τοποθεσία — ο οδηγός έπρεπε να
                             * ανοίξει κάθε αγγελία για να δει αν είναι στην πόλη του.
                             *
                             * Η τιμή έρχεται ήδη γενικευμένη σε επίπεδο πόλης από τον
                             * Visibility::sanitiseListing() στον controller. Το view ΔΕΝ
                             * αποφασίζει τι επιτρέπεται να φανεί· απλώς το εμφανίζει.
                             */
                            $place = trim((string) ($listing['location'] ?? ''));
                            ?>
                            <?php if ($place !== '' && $place !== 'Δεν καθορίστηκε') : ?>
                                <div class="job-listing-detail">
                                    <svg class="dj-place-icon" viewBox="0 0 24 24" width="20" height="20"
                                         fill="none" stroke="currentColor" stroke-width="1.6"
                                         stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="Έδρα">
                                        <path d="M12 21s-6.5-5.4-6.5-10a6.5 6.5 0 1 1 13 0c0 4.6-6.5 10-6.5 10Z"/>
                                        <circle cx="12" cy="10.6" r="2.4"/>
                                    </svg>
                                    <span><?php echo htmlspecialchars($place, ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($listing['salary_min'] || $listing['salary_max']) : ?>
                                <div class="job-listing-detail">
                                    <img src="<?= \Drivejob\Helpers\Asset::url('img/salary_icon.png') ?>" alt="Αμοιβή">
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
                            <?php echo nl2br(htmlspecialchars(mb_substr($listing['description'], 0, 150, 'UTF-8') . (mb_strlen($listing['description'], 'UTF-8') > 150 ? '...' : ''))); ?>
                        </div>

                        <div class="job-listing-footer">
                            <span class="job-listing-date">Δημοσιεύτηκε: <?php echo date('d/m/Y', strtotime($listing['created_at'])); ?></span>
                            <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>" class="btn-primary">Περισσότερα</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Σελιδοποίηση -->
            <?php if (isset($pagination) && $pagination['pages'] > 1) : ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $pagination['pages']; $i++) : ?>
                        <a href="?page=<?php echo $i; ?><?php echo isset($_GET['listing_type']) ? '&listing_type=' . htmlspecialchars($_GET['listing_type']) : ''; ?><?php echo isset($_GET['job_type']) ? '&job_type=' . htmlspecialchars($_GET['job_type']) : ''; ?><?php echo isset($_GET['vehicle_type']) ? '&vehicle_type=' . htmlspecialchars($_GET['vehicle_type']) : ''; ?>" class="pagination-btn <?php echo $i === $pagination['page'] ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

        <?php else : ?>
            <div class="no-results">
                <p>Δεν βρέθηκαν αγγελίες που να ταιριάζουν με τα κριτήρια αναζήτησης.</p>
                <?php if (isset($_SESSION['user_id'])) : ?>
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
include ROOT_DIR . '/src/Views/partials/footer.php';
?>
