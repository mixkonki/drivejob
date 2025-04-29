<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/job-listings.css">

<main>
    <div class="container">
        <h1>Προτεινόμενοι Οδηγοί για τις Αγγελίες σας</h1>
        
        <div class="matches-header">
            <div class="matches-info">
                <p><strong><?php echo isset($matchedDrivers['pagination']) ? $matchedDrivers['pagination']['total'] : 0; ?></strong> οδηγοί ταιριάζουν με τις αγγελίες σας.</p>
            </div>
            <div class="matches-actions">
                <a href="<?php echo BASE_URL; ?>drivers/search" class="btn-secondary">Αναζήτηση Οδηγών</a>
                <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn-primary">Νέα Αγγελία</a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success_message'])) : ?>
            <div class="success-message">
                <?php echo $_SESSION['success_message']; ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])) : ?>
            <div class="error-message">
                <?php echo $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Σύνοψη Αγγελιών -->
        <div class="company-listings-summary">
            <h3>Οι Ενεργές Αγγελίες σας</h3>
            <div class="company-listings-grid">
                <?php foreach ($companyListings['results'] as $index => $listing) : ?>
                    <?php if ($index < 4 && $listing['is_active']) : ?>
                        <div class="company-listing-item">
                            <h4><?php echo htmlspecialchars($listing['title']); ?></h4>
                            <div class="listing-meta">
                                <span class="job-type-badge <?php echo $listing['job_type']; ?>">
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
                                <span class="location">
                                    <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                                    <?php echo htmlspecialchars($listing['location']); ?>
                                </span>
                            </div>
                            <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>" class="listing-link">Προβολή</a>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <?php if (
                count(array_filter($companyListings['results'], function ($l) {
                    return $l['is_active'];
                })) > 4
) : ?>
                    <div class="company-listing-more">
                        <a href="<?php echo BASE_URL; ?>job-listings/my-listings">
                            + <?php echo count(array_filter($companyListings['results'], function ($l) {
    return $l['is_active'];
                              })) - 4; ?> ακόμα αγγελίες
                        </a>
                    </div>
                <?php elseif (
                count(array_filter($companyListings['results'], function ($l) {
                    return $l['is_active'];
                })) === 0
) : ?>
                    <div class="company-listing-none">
                        <p>Δεν έχετε ενεργές αγγελίες.</p>
                        <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn-primary">Δημοσίευση Αγγελίας</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Λίστα Οδηγών -->
        <?php if (isset($matchedDrivers['results']) && count($matchedDrivers['results']) > 0) : ?>
            <div class="matched-drivers">
                <h3>Οδηγοί που ταιριάζουν με τις Αγγελίες σας</h3>
                
                <div class="driver-matches-grid">
                    <?php foreach ($matchedDrivers['results'] as $driver) : ?>
                        <div class="driver-match-card">
                            <div class="driver-match-header">
                                <div class="driver-photo">
                                    <?php if (isset($driver['profile_image']) && $driver['profile_image']) : ?>
                                        <img src="<?php echo BASE_URL . htmlspecialchars($driver['profile_image']); ?>" alt="Φωτογραφία οδηγού">
                                    <?php else : ?>
                                        <img src="<?php echo BASE_URL; ?>img/default_profile.png" alt="Προεπιλεγμένη φωτογραφία">
                                    <?php endif; ?>
                                </div>
                                <div class="driver-meta">
                                    <h4><?php echo htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']); ?></h4>
                                    <?php if (isset($driver['city']) && $driver['city']) : ?>
                                        <div class="driver-location">
                                            <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                                            <?php echo htmlspecialchars($driver['city'] . ', ' . $driver['country']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="driver-match-stats">
                                        <?php if (isset($driver['experience_years']) && $driver['experience_years']) : ?>
                                            <span class="driver-experience" title="Εμπειρία">
                                                <img src="<?php echo BASE_URL; ?>img/experience_icon.png" alt="Εμπειρία">
                                                <?php echo $driver['experience_years']; ?> έτη
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($driver['rating']) && $driver['rating']) : ?>
                                            <span class="driver-rating" title="Αξιολόγηση">
                                                <img src="<?php echo BASE_URL; ?>img/rating_icon.png" alt="Αξιολόγηση">
                                                <?php echo number_format($driver['rating'], 1); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="driver-match-qualifications">
                                <?php if (isset($driver['driving_license']) && $driver['driving_license']) : ?>
                                    <span class="qualification" title="Άδεια Οδήγησης">
                                        <img src="<?php echo BASE_URL; ?>img/license_icon.png" alt="Άδεια">
                                        Άδεια
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (isset($driver['adr_certificate']) && $driver['adr_certificate']) : ?>
                                    <span class="qualification" title="Πιστοποιητικό ADR">
                                        <img src="<?php echo BASE_URL; ?>img/adr_icon.png" alt="ADR">
                                        ADR
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (isset($driver['operator_license']) && $driver['operator_license']) : ?>
                                    <span class="qualification" title="Άδεια Χειριστή">
                                        <img src="<?php echo BASE_URL; ?>img/operator_icon.png" alt="Χειριστής">
                                        Χειριστής
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (isset($driver['preferred_job_type']) || isset($driver['preferred_vehicle_type'])) : ?>
                                <div class="driver-match-preferences">
                                    <?php if (isset($driver['preferred_job_type']) && $driver['preferred_job_type']) : ?>
                                        <div class="preference">
                                            <strong>Προτιμώμενη Απασχόληση:</strong>
                                            <?php
                                            switch ($driver['preferred_job_type']) {
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
                                                default:
                                                    echo htmlspecialchars($driver['preferred_job_type']);
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($driver['preferred_vehicle_type']) && $driver['preferred_vehicle_type']) : ?>
                                        <div class="preference">
                                            <strong>Προτιμώμενο Όχημα:</strong>
                                            <?php
                                            switch ($driver['preferred_vehicle_type']) {
                                                case 'car':
                                                    echo 'Αυτοκίνητο';
                                                    break;
                                                case 'van':
                                                    echo 'Βαν';
                                                    break;
                                                case 'truck':
                                                    echo 'Φορτηγό';
                                                    break;
                                                case 'bus':
                                                    echo 'Λεωφορείο';
                                                    break;
                                                case 'machinery':
                                                    echo 'Μηχάνημα Έργου';
                                                    break;
                                                default:
                                                    echo htmlspecialchars($driver['preferred_vehicle_type']);
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="driver-match-actions">
                                <a href="<?php echo BASE_URL; ?>drivers/profile/<?php echo $driver['id']; ?>" class="btn-secondary">Προβολή Προφίλ</a>
                                <a href="<?php echo BASE_URL; ?>messages/create/<?php echo $driver['id']; ?>" class="btn-primary">Επικοινωνία</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Σελιδοποίηση -->
                <?php if (isset($matchedDrivers['pagination']) && $matchedDrivers['pagination']['pages'] > 1) : ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $matchedDrivers['pagination']['pages']; $i++) : ?>
                            <a href="?page=<?php echo $i; ?>" class="pagination-btn <?php echo $i === $matchedDrivers['pagination']['page'] ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php else : ?>
            <div class="no-results">
                <p>Δεν βρέθηκαν οδηγοί που να ταιριάζουν με τις αγγελίες σας.</p>
                <p>Δοκιμάστε να τροποποιήσετε τις αγγελίες σας ή να αναζητήσετε όλους τους διαθέσιμους οδηγούς.</p>
                <div class="no-results-actions">
                    <a href="<?php echo BASE_URL; ?>job-listings/my-listings" class="btn-secondary">Επεξεργασία Αγγελιών</a>
                    <a href="<?php echo BASE_URL; ?>drivers/search" class="btn-primary">Αναζήτηση Οδηγών</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
    .matches-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .matches-info {
        font-size: 16px;
    }
    
    .matches-actions {
        display: flex;
        gap: 10px;
    }
    
    .company-listings-summary {
        background-color: #f9f9f9;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
    }
    
    .company-listings-summary h3 {
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 18px;
    }
    
    .company-listings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }
    
    .company-listing-item {
        background-color: #fff;
        border-radius: 6px;
        padding: 15px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .company-listing-item h4 {
        margin-top: 0;
        margin-bottom: 10px;
        font-size: 16px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .listing-meta {
        margin-bottom: 15px;
    }
    
    .job-type-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 12px;
        margin-bottom: 5px;
    }
    
    .job-type-badge.full_time {
        background-color: #d4edda;
        color: #155724;
    }
    
    .job-type-badge.part_time {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .job-type-badge.contract {
        background-color: #d1ecf1;
        color: #0c5460;
    }
    
    .job-type-badge.temporary {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .location {
        display: flex;
        align-items: center;
        font-size: 12px;
        color: #666;
    }
    
    .location img {
        width: 14px;
        height: 14px;
        margin-right: 5px;
    }
    
    .listing-link {
        display: inline-block;
        color: #aa3636;
        text-decoration: none;
        font-size: 14px;
    }
    
    .listing-link:hover {
        text-decoration: underline;
    }
    
    .company-listing-more {
        background-color: #fff;
        border-radius: 6px;
        padding: 15px;
        display: flex;
        justify-content: center;
        align-items: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .company-listing-more a {
        color: #aa3636;
        text-decoration: none;
    }
    
    .company-listing-more a:hover {
        text-decoration: underline;
    }
    
    .company-listing-none {
        grid-column: 1 / -1;
        background-color: #fff;
        border-radius: 6px;
        padding: 30px;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .company-listing-none p {
        margin-bottom: 15px;
        color: #666;
    }
    
    .matched-drivers h3 {
        margin-bottom: 20px;
    }
    
    .driver-matches-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    
    .driver-match-card {
        background-color: #fff;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .driver-match-header {
        display: flex;
        margin-bottom: 15px;
    }
    
    .driver-photo {
        margin-right: 15px;
    }
    
    .driver-photo img {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .driver-meta {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .driver-meta h4 {
        margin: 0 0 5px 0;
        font-size: 18px;
    }
    
    .driver-location {
        display: flex;
        align-items: center;
        font-size: 14px;
        color: #666;
        margin-bottom: 8px;
    }
    
    .driver-location img {
        width: 14px;
        height: 14px;
        margin-right: 5px;
    }
    
    .driver-match-stats {
        display: flex;
        gap: 15px;
    }
    
    .driver-experience, .driver-rating {
        display: flex;
        align-items: center;
        font-size: 14px;
        color: #666;
    }
    
    .driver-experience img, .driver-rating img {
        width: 14px;
        height: 14px;
        margin-right: 5px;
    }
    
    .driver-match-qualifications {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .qualification {
        display: flex;
        align-items: center;
        background-color: #f0f0f0;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
    }
    
    .qualification img {
        width: 14px;
        height: 14px;
        margin-right: 5px;
    }
    
    .driver-match-preferences {
        background-color: #f9f9f9;
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 15px;
        font-size: 14px;
    }
    
    .preference {
        margin-bottom: 5px;
    }
    
    .preference:last-child {
        margin-bottom: 0;
    }
    
    .driver-match-actions {
        display: flex;
        justify-content: space-between;
    }
    
    .no-results {
        text-align: center;
        padding: 40px 0;
    }
    
    .no-results p {
        margin-bottom: 10px;
        color: #666;
    }
    
    .no-results-actions {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 20px;
    }
</style>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php';
?>
