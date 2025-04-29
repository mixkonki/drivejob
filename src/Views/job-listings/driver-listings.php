<?php 
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php'; 
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/job-listings.css">

<main>
    <div class="container">
        <h1>Αγγελίες Οδηγού</h1>
        
        <div class="driver-profile-header">
            <div class="driver-info">
                <?php if (isset($driver['profile_image']) && $driver['profile_image']): ?>
                    <img src="<?php echo BASE_URL . htmlspecialchars($driver['profile_image']); ?>" alt="Φωτογραφία προφίλ" class="driver-image">
                <?php else: ?>
                    <img src="<?php echo BASE_URL; ?>img/default_profile.png" alt="Προεπιλεγμένη φωτογραφία" class="driver-image">
                <?php endif; ?>
                <div class="driver-details">
                    <h2><?php echo htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']); ?></h2>
                    <?php if (isset($driver['city']) && $driver['city']): ?>
                        <p class="driver-location">
                            <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                            <?php echo htmlspecialchars($driver['city'] . ', ' . $driver['country']); ?>
                        </p>
                    <?php endif; ?>
                    <div class="driver-meta">
                        <?php if (isset($driver['experience_years']) && $driver['experience_years']): ?>
                            <span class="driver-experience">
                                <img src="<?php echo BASE_URL; ?>img/experience_icon.png" alt="Εμπειρία">
                                <?php echo $driver['experience_years']; ?> έτη εμπειρίας
                            </span>
                        <?php endif; ?>
                        <?php if (isset($driver['rating']) && $driver['rating']): ?>
                            <span class="driver-rating">
                                <img src="<?php echo BASE_URL; ?>img/rating_icon.png" alt="Αξιολόγηση">
                                <?php echo number_format($driver['rating'], 1); ?>/5
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="driver-actions">
                <a href="<?php echo BASE_URL; ?>drivers/profile/<?php echo $driver['id']; ?>" class="btn-secondary">Προβολή Προφίλ</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'company'): ?>
                    <a href="<?php echo BASE_URL; ?>messages/create/<?php echo $driver['id']; ?>" class="btn-primary">Επικοινωνία</a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                <?php echo $_SESSION['success_message']; ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message">
                <?php echo $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
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
                        <a href="?page=<?php echo $i; ?>" class="pagination-btn <?php echo $i === $listings['pagination']['page'] ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-results">
                <p>Ο οδηγός δεν έχει δημοσιεύσει αγγελίες.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
    .driver-profile-header {
        background-color: #f9f9f9;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .driver-info {
        display: flex;
        align-items: center;
    }
    
    .driver-image {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 20px;
    }
    
    .driver-details h2 {
        margin: 0 0 5px 0;
        font-size: 24px;
    }
    
    .driver-location {
        display: flex;
        align-items: center;
        color: #666;
        margin: 0 0 10px 0;
    }
    
    .driver-location img {
        width: 16px;
        height: 16px;
        margin-right: 5px;
    }
    
    .driver-meta {
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
        width: 16px;
        height: 16px;
        margin-right: 5px;
    }
    
    .driver-actions {
        display: flex;
        gap: 10px;
    }
</style>

<?php 
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php'; 
?>